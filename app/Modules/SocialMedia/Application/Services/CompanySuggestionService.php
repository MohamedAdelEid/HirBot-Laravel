<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Shared\Enums\CompanyStatusEnum;
use App\Shared\Models\Company;
use App\Shared\Models\User;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CompanySuggestionService
{
    /**
     * Get company suggestions for the authenticated user
     */
    public function getSuggestions(
        int $page = 1,
        int $perPage = 15,
        ?string $industry = null,
        ?string $location = null,
        ?float $minScore = null
    ): LengthAwarePaginator {
        $user = Auth::user();

        $query = Company::query()
            ->with(['user', 'openJobs', 'currentEmployees.user'])
            ->where('UserID', '!=', $user->Id)
            ->where('status' , CompanyStatusEnum::ACCEPT)
            ->notFollowedBy($user->Id);

        // Apply filters
        if ($industry) {
            $query->where('CompanyType', 'LIKE', "%{$industry}%");
        }

        if ($location) {
            $query->where(function ($locQuery) use ($location) {
                $locQuery->where('country', 'LIKE', "%{$location}%")
                            ->orWhere('Governate', 'LIKE', "%{$location}%")
                            ->orWhere('street', 'LIKE', "%{$location}%");
            });
        }

        $companies = $query->get();

        // Calculate relevance scores for each company
        $scoredCompanies = $companies->map(function ($company) use ($user) {
            $score = $this->calculateRelevanceScore($company, $user);
            $company->relevance_score = $score;
            $company->job_openings = $company->openJobs->count();
            $company->connected_employees_count = $this->getConnectedEmployeesCount($company, $user);
            $company->connected_employees = $this->getConnectedEmployees($company, $user);
            $company->match_factors = $this->getMatchFactors($company, $user);
            return $company;
        });

        // Filter by minimum score if provided
        if ($minScore !== null) {
            $scoredCompanies = $scoredCompanies->filter(function ($company) use ($minScore) {
                return $company->relevance_score >= $minScore;
            });
        }

        // Sort by relevance score (highest first)
        $scoredCompanies = $scoredCompanies->sortByDesc('relevance_score');

        // Manual pagination
        $total = $scoredCompanies->count();
        $offset = ($page - 1) * $perPage;
        $paginatedCompanies = $scoredCompanies->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedCompanies,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Calculate relevance score for a company based on multiple factors
     */
    private function calculateRelevanceScore(Company $company, User $user): float
    {
        // Factor 1: Industry Relevance (25% weight)
        $industryScore = $this->calculateIndustryScore($company, $user) * 0.25;

        // Factor 2: Skill Match (20% weight)
        $skillScore = $this->calculateSkillScore($company, $user) * 0.20;

        // Factor 3: Location Proximity (15% weight)
        $locationScore = $this->calculateLocationScore($company, $user) * 0.15;

        // Factor 4: Network Overlap (15% weight)
        $networkScore = $this->calculateNetworkScore($company, $user) * 0.15;

        // Factor 5: Job Opportunities (15% weight)
        $jobScore = $this->calculateJobScore($company, $user) * 0.15;

        // Factor 6: Company Growth & Trending (10% weight)
        $trendingScore = $this->calculateTrendingScore($company) * 0.10;

        return $industryScore + $skillScore + $locationScore + $networkScore + $jobScore + $trendingScore;
    }

    /**
     * Calculate industry relevance score
     */
    private function calculateIndustryScore(Company $company, User $user): float
    {
        // Get user's industry from their current experience
        $userIndustry = $user->currentExperience?->CompanyType ??
                       $user->experiences()->currentlyWorking()->first()?->CompanyType;

        if (!$userIndustry || !$company->CompanyType) {
            return 30; // Base score if no industry data
        }

        // Exact match
        if (strtolower($company->CompanyType) === strtolower($userIndustry)) {
            return 100;
        }

        // Partial match (contains keywords)
        $companyKeywords = explode(' ', strtolower($company->CompanyType));
        $userKeywords = explode(' ', strtolower($userIndustry));

        $matches = array_intersect($companyKeywords, $userKeywords);
        $matchPercentage = count($matches) / max(count($userKeywords), 1);

        return 30 + ($matchPercentage * 70);
    }

    /**
     * Calculate skill match score
     */
    private function calculateSkillScore(Company $company, User $user): float
    {
        // Get user's skills
        $userSkills = $user->skills()->pluck('Name')->toArray();

        if (empty($userSkills)) {
            return 40; // Base score if no skills data
        }

        // Get skills required by the company (from job postings)
        $companyRequiredSkills = $this->getCompanyRequiredSkills($company);

        if (empty($companyRequiredSkills)) {
            return 40; // Base score if no company skills data
        }

        $matchingSkills = array_intersect(
            array_map('strtolower', $userSkills),
            array_map('strtolower', $companyRequiredSkills)
        );

        $matchPercentage = count($matchingSkills) / count($userSkills);
        return 20 + ($matchPercentage * 80);
    }

    /**
     * Calculate location proximity score
     */
    private function calculateLocationScore(Company $company, User $user): float
    {
        $userLocation = $user->portfolio?->location ?? '';
        $companyLocation = $company->full_location;

        if (empty($userLocation) || empty($companyLocation)) {
            return 50; // Base score if no location data
        }

        // Exact location match
        if (strtolower($userLocation) === strtolower($companyLocation)) {
            return 100;
        }

        // Check individual location components
        $userParts = explode(',', strtolower($userLocation));
        $companyParts = [
            strtolower($company->country ?? ''),
            strtolower($company->Governate ?? ''),
            strtolower($company->street ?? '')
        ];
        $companyParts = array_filter($companyParts);

        $matches = 0;
        foreach ($userParts as $userPart) {
            $userPart = trim($userPart);
            foreach ($companyParts as $companyPart) {
                if (str_contains($companyPart, $userPart) || str_contains($userPart, $companyPart)) {
                    $matches++;
                    break;
                }
            }
        }

        $matchPercentage = $matches / max(count($userParts), count($companyParts));
        return 30 + ($matchPercentage * 70);
    }

    /**
     * Calculate network overlap score
     */
    private function calculateNetworkScore(Company $company, User $user): float
    {
        // Use the scope we created in User model
        $connectionsAtCompany = User::connectedToCompany($company->ID, $user->Id)->count();

        // Get total connections
        $totalConnections = ConnectionModel::where(function ($query) use ($user) {
                $query->where('requester_id', $user->Id)
                      ->orWhere('receiver_id', $user->Id);
            })
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->where('type', ConnectionTypeEnum::CONNECTION)
            ->count();

        if ($totalConnections === 0) {
            return 20; // Base score if no connections
        }

        $networkPercentage = $connectionsAtCompany / $totalConnections;
        return 20 + ($networkPercentage * 80);
    }

    /**
     * Calculate job opportunities score
     */
    private function calculateJobScore(Company $company, User $user): float
    {
        $jobOpenings = $company->openJobs->count();

        if ($jobOpenings === 0) {
            return 10; // Low score if no job openings
        }

        // Higher score for more job openings (capped at reasonable number)
        $normalizedScore = min($jobOpenings / 10, 1) * 90 + 10;
        return $normalizedScore;
    }

    /**
     * Calculate trending/growth score
     */
    private function calculateTrendingScore(Company $company): float
    {
        $recentFollowers = $company->followers()
            ->wherePivot('created_at', '>=', now()->subDays(30))
            ->count();

        $totalFollowers = $company->followers()->count();

        if ($totalFollowers === 0) {
            return 30; // Base score for new companies
        }

        $growthRate = $recentFollowers / $totalFollowers;
        return 30 + (min($growthRate, 1) * 70);
    }

    /**
     * Get connected employees count
     */
    private function getConnectedEmployeesCount(Company $company, User $user): int
    {
        return User::connectedToCompany($company->ID, $user->Id)->count();
    }

    /**
     * Get connected employees details
     */
    private function getConnectedEmployees(Company $company, User $user): array
    {
        $connectedUsers = User::connectedToCompany($company->ID, $user->Id)
            ->with(['experiences' => function ($query) use ($company) {
                $query->where('CompanyID', $company->ID)->currentlyWorking();
            }])
            ->limit(5)
            ->get();

        return $connectedUsers->map(function ($connectedUser) {
            $currentExperience = $connectedUser->experiences->first();

            return [
                'id' => $connectedUser->Id,
                'name' => $connectedUser->FullName,
                'username' => $connectedUser->UserName,
                'profile_image' => $connectedUser->ImagePath,
                'current_position' => $currentExperience?->Position ?? $currentExperience?->Title ?? 'Employee',
            ];
        })->toArray();
    }

    /**
     * Get match factors for display (as percentages)
     */
    private function getMatchFactors(Company $company, User $user): array
    {
        return [
            'industry' => round($this->calculateIndustryScore($company, $user), 1),
            'skills' => round($this->calculateSkillScore($company, $user), 1),
            'location' => round($this->calculateLocationScore($company, $user), 1),
            'network' => round($this->calculateNetworkScore($company, $user), 1),
        ];
    }

    /**
     * Get company required skills from job postings
     */
    private function getCompanyRequiredSkills(Company $company): array
    {
        // Extract skills from job descriptions or requirements
        // This is a simplified implementation - in reality, you'd parse job descriptions
        $jobTitles = $company->openJobs->pluck('Title')->implode(' ');
        $jobDescriptions = $company->openJobs->pluck('Description')->implode(' ');

        $allText = strtolower($jobTitles . ' ' . $jobDescriptions);

        // Common skills to look for
        $skillsToCheck = [
            'PHP', 'JavaScript', 'Python', 'React', 'Laravel', 'Vue', 'Angular',
            'Java', 'C#', 'SQL', 'MySQL', 'PostgreSQL', 'MongoDB',
            'HTML', 'CSS', 'Bootstrap', 'Tailwind',
            'Git', 'Docker', 'AWS', 'Azure',
            'Project Management', 'Leadership', 'Communication',
            'Marketing', 'Sales', 'Customer Service',
            'Finance', 'Accounting', 'Analysis'
        ];

        $foundSkills = [];
        foreach ($skillsToCheck as $skill) {
            if (str_contains($allText, strtolower($skill))) {
                $foundSkills[] = $skill;
            }
        }

        return $foundSkills;
    }
}
