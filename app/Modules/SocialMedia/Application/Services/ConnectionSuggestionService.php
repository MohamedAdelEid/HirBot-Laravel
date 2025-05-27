<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Shared\Models\User;
use App\Shared\Models\Experience;
use App\Shared\Models\Education;
use App\Shared\Models\Skill;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ConnectionSuggestionService
{
    private ConnectionService $connectionService;

    // Define weights for different similarity factors
    private const WEIGHTS = [
        'title' => 0.15,
        'location' => 0.15,
        'mutual_connections' => 0.25,
        'skills' => 0.20,
        'education' => 0.15,
        'experience' => 0.10
    ];

    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Get connection suggestions for a user
     *
     * @param string $userId
     * @param int $perPage
     * @param array $filters Optional filters to apply
     * @return LengthAwarePaginator
     */
    public function getSuggestions(string $userId, int $perPage = 15 , array $filters = []): LengthAwarePaginator
    {
        // Get current user with relationships
        $currentUser = User::with(['skills', 'experiences', 'educations'])
            ->find($userId);

        if (!$currentUser) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        // Get IDs of users who are already connected or have pending requests
        $connectedUserIds = $this->getConnectedAndPendingUserIds($userId);

        // Add current user to the exclusion list
        $connectedUserIds[] = $userId;

        // Get potential connections (users who are not already connected)
        $potentialConnections = User::whereNotIn('Id', $connectedUserIds)
            ->with(['skills', 'experiences', 'educations', 'currentExperience.company'])
            ->get();

        if ($potentialConnections->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        // Calculate similarity scores for each potential connection
        $scoredConnections = $this->calculateSimilarityScores($currentUser, $potentialConnections);

        // Apply any additional filters
        if (!empty($filters)) {
            $scoredConnections = $this->applyFilters($scoredConnections, $filters);
        }

        // Sort by similarity score (descending)
        $scoredConnections = $scoredConnections->sortByDesc('similarity_score');

        // Paginate the results
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginatedConnections = new LengthAwarePaginator(
            $scoredConnections->slice($offset, $perPage)->values(),
            $scoredConnections->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginatedConnections;
    }

    /**
     * Get IDs of users who are already connected or have pending requests
     *
     * @param string $userId
     * @return array
     */
    private function getConnectedAndPendingUserIds(string $userId): array
    {
        // Get accepted connections
        $connectedUserIds = $this->connectionService->getConnectedUserIds($userId);

        // Get pending connections (both sent and received)
        $pendingConnections = ConnectionModel::where(function($query) use ($userId) {
            $query->where('requester_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
        ->where('status', ConnectionStatusEnum::PENDING)
        ->get();

        foreach ($pendingConnections as $connection) {
            if ($connection->requester_id === $userId) {
                $connectedUserIds[] = $connection->receiver_id;
            } else {
                $connectedUserIds[] = $connection->requester_id;
            }
        }

        return array_unique($connectedUserIds);
    }

    /**
     * Calculate similarity scores for potential connections
     *
     * @param User $currentUser
     * @param Collection $potentialConnections
     * @return Collection
     */
    private function calculateSimilarityScores(User $currentUser, Collection $potentialConnections): Collection
    {
        $scoredConnections = collect();

        foreach ($potentialConnections as $potentialConnection) {
            // Initialize scores for each factor
            $scores = [
                'title' => 0,
                'location' => 0,
                'mutual_connections' => 0,
                'skills' => 0,
                'education' => 0,
                'experience' => 0
            ];

            // Calculate title similarity
            $scores['title'] = $this->calculateTitleSimilarity($currentUser, $potentialConnection);

            // Calculate location similarity
            $scores['location'] = $this->calculateLocationSimilarity($currentUser, $potentialConnection);

            // Calculate mutual connections
            $mutualConnections = $this->getMutualConnections($currentUser->Id, $potentialConnection->Id);
            $scores['mutual_connections'] = $this->calculateMutualConnectionsScore($mutualConnections->count());

            // Calculate skills similarity
            $scores['skills'] = $this->calculateSkillsSimilarity($currentUser, $potentialConnection);

            // Calculate education similarity
            $scores['education'] = $this->calculateEducationSimilarity($currentUser, $potentialConnection);

            // Calculate experience similarity
            $scores['experience'] = $this->calculateExperienceSimilarity($currentUser, $potentialConnection);

            // Calculate weighted total score
            $totalScore = $this->calculateWeightedScore($scores);

            // Add user with score to results
            $potentialConnection->similarity_score = $totalScore;
            $potentialConnection->similarity_factors = $scores;
            $potentialConnection->mutual_connections = $mutualConnections;

            $scoredConnections->push($potentialConnection);
        }

        return $scoredConnections;
    }

    /**
     * Calculate similarity between job titles
     *
     * @param User $user1
     * @param User $user2
     * @return float
     */
    private function calculateTitleSimilarity(User $user1, User $user2): float
    {
        // Get current job titles
        $title1 = $user1->portfolio ? $user1->portfolio->Title : '';
        $title2 = $user2->portfolio ? $user2->portfolio->Title : '';

        if (empty($title1) || empty($title2)) {
            return 0;
        }

        // Calculate text similarity using Levenshtein distance
        $maxLength = max(strlen($title1), strlen($title2));
        if ($maxLength === 0) {
            return 0;
        }

        $levenshtein = levenshtein(strtolower($title1), strtolower($title2));
        $similarity = 1 - ($levenshtein / $maxLength);

        // Boost score for common keywords in tech roles
        $techKeywords = ['developer', 'engineer', 'programmer', 'architect', 'designer', 'analyst', 'manager', 'lead', 'php', 'javascript', 'python', 'java', 'c#', 'ruby', 'laravel', 'react', 'angular', 'vue', 'node', 'frontend', 'backend', 'fullstack', 'devops', 'data', 'ai', 'ml', 'cloud'];

        $keywordBoost = 0;
        foreach ($techKeywords as $keyword) {
            if (stripos($title1, $keyword) !== false && stripos($title2, $keyword) !== false) {
                $keywordBoost += 0.1; // Add 0.1 for each matching keyword
            }
        }

        // Cap the total at 1.0
        return min(1.0, $similarity + $keywordBoost);
    }

    /**
     * Calculate similarity between locations
     *
     * @param User $user1
     * @param User $user2
     * @return float
     */
    private function calculateLocationSimilarity(User $user1, User $user2): float
    {
        // Get locations
        $location1 = $user1->portfolio ? $user1->portfolio->location : '';
        $location2 = $user2->portfolio ? $user2->portfolio->location : '';

        if (empty($location1) || empty($location2)) {
            return 0;
        }

        // Exact match gets full score
        if (strtolower($location1) === strtolower($location2)) {
            return 1.0;
        }

        // Split locations into parts (city, state, country)
        $parts1 = array_map('trim', explode(',', $location1));
        $parts2 = array_map('trim', explode(',', $location2));

        // Count matching parts
        $matchingParts = 0;
        $totalParts = max(count($parts1), count($parts2));

        foreach ($parts1 as $part1) {
            foreach ($parts2 as $part2) {
                if (strtolower($part1) === strtolower($part2)) {
                    $matchingParts++;
                    break;
                }
            }
        }

        return $totalParts > 0 ? $matchingParts / $totalParts : 0;
    }

    /**
     * Calculate score based on number of mutual connections
     *
     * @param int $mutualCount
     * @return float
     */
    private function calculateMutualConnectionsScore(int $mutualCount): float
    {
        // Logarithmic scale to prevent too much weight for users with many mutual connections
        if ($mutualCount === 0) {
            return 0;
        }

        // Cap at 20 mutual connections for scoring purposes
        $cappedCount = min($mutualCount, 20);

        return log10($cappedCount + 1) / log10(21); // Normalized to [0,1]
    }

    /**
     * Get mutual connections between two users
     *
     * @param string $userId1
     * @param string $userId2
     * @return Collection
     */
    private function getMutualConnections(string $userId1, string $userId2): Collection
    {
        // Get user1's connections
        $user1Connections = ConnectionModel::where(function($q) use ($userId1) {
                $q->where('status' ,ConnectionTypeEnum::CONNECTION)
                        ->where('requester_id', $userId1)
                        ->orWhere('receiver_id', $userId1);
            })
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->get()
            ->map(function($connection) use ($userId1) {
                return $connection->requester_id == $userId1
                    ? $connection->receiver_id
                    : $connection->requester_id;
            });

        // Get user2's connections
        $user2Connections = ConnectionModel::where(function($q) use ($userId2) {
                $q->where('status' ,ConnectionTypeEnum::CONNECTION)
                    ->where('requester_id', $userId2)
                    ->orWhere('receiver_id', $userId2);
            })
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->get()
            ->map(function($connection) use ($userId2) {
                return $connection->requester_id == $userId2
                    ? $connection->receiver_id
                    : $connection->requester_id;
            });

        // Find mutual connections
        $mutualConnectionIds = array_intersect($user1Connections->toArray(), $user2Connections->toArray());

        // Get the user data for mutual connections
        return User::whereIn('Id', $mutualConnectionIds)->get();
    }

    /**
     * Calculate similarity between skills
     *
     * @param User $user1
     * @param User $user2
     * @return float
     */
    private function calculateSkillsSimilarity(User $user1, User $user2): float
    {
        $skills1 = $user1->skills;
        $skills2 = $user2->skills;

        if ($skills1->isEmpty() || $skills2->isEmpty()) {
            return 0;
        }

        // Get skill IDs
        $skillIds1 = $skills1->pluck('ID')->toArray();
        $skillIds2 = $skills2->pluck('ID')->toArray();

        // Calculate Jaccard similarity coefficient
        $intersection = count(array_intersect($skillIds1, $skillIds2));
        $union = count(array_unique(array_merge($skillIds1, $skillIds2)));

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Calculate similarity between education backgrounds
     *
     * @param User $user1
     * @param User $user2
     * @return float
     */
    private function calculateEducationSimilarity(User $user1, User $user2): float
    {
        $educations1 = $user1->educations;
        $educations2 = $user2->educations;

        if ($educations1->isEmpty() || $educations2->isEmpty()) {
            return 0;
        }

        $maxSimilarity = 0;

        // Compare each education pair and find the highest similarity
        foreach ($educations1 as $edu1) {
            foreach ($educations2 as $edu2) {
                $similarity = 0;

                // Same institution
                if (strtolower($edu1->InstituationName) === strtolower($edu2->InstituationName)) {
                    $similarity += 0.5;
                }

                // Similar degree
                $degree1 = strtolower($edu1->degree);
                $degree2 = strtolower($edu2->degree);

                if ($degree1 === $degree2) {
                    $similarity += 0.3;
                } elseif (
                    (strpos($degree1, 'bachelor') !== false && strpos($degree2, 'bachelor') !== false) ||
                    (strpos($degree1, 'master') !== false && strpos($degree2, 'master') !== false) ||
                    (strpos($degree1, 'phd') !== false && strpos($degree2, 'phd') !== false) ||
                    (strpos($degree1, 'doctor') !== false && strpos($degree2, 'doctor') !== false)
                ) {
                    $similarity += 0.2;
                }

                // Similar field of study
                if (strpos($degree1, $degree2) !== false || strpos($degree2, $degree1) !== false) {
                    $similarity += 0.2;
                }

                // Same graduation status
                if ($edu1->isGraduated === $edu2->isGraduated) {
                    $similarity += 0.1;
                }

                // Update max similarity if this pair has higher similarity
                $maxSimilarity = max($maxSimilarity, $similarity);
            }
        }

        // Normalize to [0,1]
        return min(1.0, $maxSimilarity);
    }

    /**
     * Calculate similarity between work experiences
     *
     * @param User $user1
     * @param User $user2
     * @return float
     */
    private function calculateExperienceSimilarity(User $user1, User $user2): float
    {
        $experiences1 = $user1->experiences;
        $experiences2 = $user2->experiences;

        if ($experiences1->isEmpty() || $experiences2->isEmpty()) {
            return 0;
        }

        $maxSimilarity = 0;

        // Compare each experience pair and find the highest similarity
        foreach ($experiences1 as $exp1) {
            foreach ($experiences2 as $exp2) {
                $similarity = 0;

                // Same company
                if ($exp1->CompanyID && $exp2->CompanyID && $exp1->CompanyID === $exp2->CompanyID) {
                    $similarity += 0.6;
                } elseif (strtolower($exp1->companyName) === strtolower($exp2->companyName)) {
                    $similarity += 0.5;
                }

                // Similar position
                $position1 = strtolower($exp1->Title);
                $position2 = strtolower($exp2->Title);

                if ($position1 === $position2) {
                    $similarity += 0.3;
                } elseif (
                    (strpos($position1, 'developer') !== false && strpos($position2, 'developer') !== false) ||
                    (strpos($position1, 'engineer') !== false && strpos($position2, 'engineer') !== false) ||
                    (strpos($position1, 'manager') !== false && strpos($position2, 'manager') !== false) ||
                    (strpos($position1, 'designer') !== false && strpos($position2, 'designer') !== false) ||
                    (strpos($position1, 'analyst') !== false && strpos($position2, 'analyst') !== false)
                ) {
                    $similarity += 0.2;
                }

                // Similar work type
                if ($exp1->workType === $exp2->workType) {
                    $similarity += 0.1;
                }

                // Update max similarity if this pair has higher similarity
                $maxSimilarity = max($maxSimilarity, $similarity);
            }
        }

        // Normalize to [0,1]
        return min(1.0, $maxSimilarity);
    }

    /**
     * Calculate weighted total score from individual factor scores
     *
     * @param array $scores
     * @return float
     */
    private function calculateWeightedScore(array $scores): float
    {
        $weightedScore = 0;

        foreach ($scores as $factor => $score) {
            $weightedScore += $score * self::WEIGHTS[$factor];
        }

        return $weightedScore;
    }

    /**
     * Apply additional filters to the scored connections
     *
     * @param Collection $scoredConnections
     * @param array $filters
     * @return Collection
     */
    private function applyFilters(Collection $scoredConnections, array $filters): Collection
    {
        $filteredConnections = $scoredConnections;

        // Filter by minimum similarity score
        if (isset($filters['min_score']) && is_numeric($filters['min_score'])) {
            $filteredConnections = $filteredConnections->filter(function ($connection) use ($filters) {
                return $connection->similarity_score >= $filters['min_score'];
            });
        }

        // Filter by location
        if (isset($filters['location']) && !empty($filters['location'])) {
            $filteredConnections = $filteredConnections->filter(function ($connection) use ($filters) {
                return $connection->Address &&
                       stripos($connection->Address, $filters['location']) !== false;
            });
        }

        // Filter by skill
        if (isset($filters['skill']) && !empty($filters['skill'])) {
            $filteredConnections = $filteredConnections->filter(function ($connection) use ($filters) {
                return $connection->skills->contains(function ($skill) use ($filters) {
                    return stripos($skill->name, $filters['skill']) !== false;
                });
            });
        }

        // Filter by industry
        if (isset($filters['industry']) && !empty($filters['industry'])) {
            $filteredConnections = $filteredConnections->filter(function ($connection) use ($filters) {
                return $connection->currentExperience &&
                       $connection->currentExperience->company &&
                       stripos($connection->currentExperience->company->CompanyType, $filters['industry']) !== false;
            });
        }

        // Filter by minimum mutual connections
        if (isset($filters['min_mutual']) && is_numeric($filters['min_mutual'])) {
            $filteredConnections = $filteredConnections->filter(function ($connection) use ($filters) {
                return $connection->mutual_connections->count() >= $filters['min_mutual'];
            });
        }

        return $filteredConnections;
    }
}
