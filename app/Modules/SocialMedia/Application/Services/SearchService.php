<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\Search\SearchTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Shared\Enums\UserRoleEnum;
use App\Shared\Models\Company;
use App\Shared\Models\User;
// use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    protected $currentUser;
    protected int $defaultPerPage;
    protected int $maxPerPage;

    public function __construct()
    {
        $this->currentUser = Auth::user();
        $this->defaultPerPage = config('socialmedia.search.pagination.default_per_page', 15);
        $this->maxPerPage = config('socialmedia.search.pagination.max_per_page', 50);
    }

    /**
     * Perform unified search across multiple content types
     * Returns a structure compatible with paginated responses
     */
    public function search(
        string $query,
        ?SearchTypeEnum $type = null,
        ?int $perPage = null,
        int $page = 1
    ): LengthAwarePaginator {
        // Validate and set per page
        $perPage = $this->validatePerPage($perPage);

        // Validate query length
        if (strlen(trim($query)) < config('socialmedia.search.limits.query_min_length', 2)) {
            return $this->createEmptyPaginator($perPage, $page);
        }

        $results = [];

        try {
            // Search based on type filter
            if (!$type || $type === SearchTypeEnum::USER) {
                $userResults = $this->searchUsers($query, $perPage, $page);
                $results['users'] = $userResults;
            }

            if (!$type || $type === SearchTypeEnum::COMPANY) {
                $companyResults = $this->searchCompanies($query, $perPage, $page);
                $results['companies'] = $companyResults;
            }

            if (!$type || $type === SearchTypeEnum::POST) {
                $postResults = $this->searchPosts($query, $perPage, $page);
                $results['posts'] = $postResults;
            }

            // If searching for a specific type, return only that type's results
            if ($type) {
                return $this->getSingleTypePaginator($results, $type);
            }

            // For unified search, combine and sort results
            return $this->getUnifiedPaginator($results, $perPage, $page);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'query' => $query,
                'type' => $type?->value,
                'user_id' => $this->currentUser?->Id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->createEmptyPaginator($perPage, $page);
        }
    }

    /**
     * Extract items from single type search for resource collection
     */
    protected function extractItemsFromSingleType(array $results, SearchTypeEnum $type): Collection
    {
        $key = match($type) {
            SearchTypeEnum::USER => 'users',
            SearchTypeEnum::COMPANY => 'companies',
            SearchTypeEnum::POST => 'posts',
            default => null
        };

        if (!$key || !isset($results[$key])) {
            return collect([]);
        }

        $paginatedResult = $results[$key];
        return $paginatedResult->getCollection();
    }

    /**
     * Extract items from unified search for resource collection
     */
    protected function extractItemsFromUnified(array $results, int $perPage, int $page): Collection
    {
        $allItems = collect();

        // Safely merge items from each result type
        foreach ($results as $typeKey => $typeResults) {
            if ($typeResults instanceof LengthAwarePaginator) {
                $allItems = $allItems->merge($typeResults->getCollection());
            }
        }

        // Sort by relevance score
        $sortedItems = $allItems->sortByDesc('relevance_score');

        // Manual pagination for unified results
        $offset = ($page - 1) * $perPage;
        return $sortedItems->slice($offset, $perPage)->values();
    }

    /**
     * Search users with proper pagination
     */
    public function searchUsers(string $query, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('users', $query, $perPage, $page);

        if (config('socialmedia.search.cache.enabled', false) && Cache::has($cacheKey)) {
            $paginatedUsers = Cache::get($cacheKey);
        } else {
            $paginatedUsers = $this->buildUserQuery($query)
                ->paginate($perPage, ['*'], 'page', $page);

            if (config('socialmedia.search.cache.enabled', false)) {
                Cache::put($cacheKey, $paginatedUsers, config('socialmedia.search.cache.ttl', 300));
            }
        }

        return $this->transformUserResults($paginatedUsers, $query);
    }

    /**
     * Search companies with proper pagination
     */
    public function searchCompanies(string $query, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('companies', $query, $perPage, $page);

        if (config('socialmedia.search.cache.enabled', false) && Cache::has($cacheKey)) {
            $paginatedCompanies = Cache::get($cacheKey);
        } else {
            $paginatedCompanies = $this->buildCompanyQuery($query)
                ->paginate($perPage, ['*'], 'page', $page);

            if (config('socialmedia.search.cache.enabled', false)) {
                Cache::put($cacheKey, $paginatedCompanies, config('socialmedia.search.cache.ttl', 300));
            }
        }

        return $this->transformCompanyResults($paginatedCompanies, $query);
    }

    /**
     * Search posts with proper pagination
     */
    public function searchPosts(string $query, int $perPage, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('posts', $query, $perPage, $page);

        if (config('socialmedia.search.cache.enabled', false) && Cache::has($cacheKey)) {
            $paginatedPosts = Cache::get($cacheKey);
        } else {
            $paginatedPosts = $this->buildPostQuery($query)
                ->paginate($perPage, ['*'], 'page', $page);

            if (config('socialmedia.search.cache.enabled', false)) {
                Cache::put($cacheKey, $paginatedPosts, config('socialmedia.search.cache.ttl', 300));
            }
        }

        return $this->transformPostResults($paginatedPosts, $query);
    }

    /**
     * Build user search query with correct column names
     */
    protected function buildUserQuery(string $query): Builder
    {
        return User::query()
            ->where('role',UserRoleEnum::USER->value)
            ->with(['portfolio', 'currentExperience.company.user', 'skills'])
            ->where('Id', '!=', $this->currentUser->Id)
            ->where(function ($q) use ($query) {
                $q->where('FullName', 'LIKE', "%{$query}%")
                  ->orWhere('UserName', 'LIKE', "%{$query}%")
                  ->orWhere('Email', 'LIKE', "%{$query}%")
                  ->orWhereHas('portfolio', function ($portfolioQuery) use ($query) {
                      $portfolioQuery->where('Title', 'LIKE', "%{$query}%")
                                   ->orWhere('PortfolioUrl', 'LIKE', "%{$query}%")
                                   ->orWhere('location', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('skills', function ($skillQuery) use ($query) {
                      $skillQuery->where('Name', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('experiences', function ($expQuery) use ($query) {
                      $expQuery->where('Title', 'LIKE', "%{$query}%")
                             ->orWhere('companyName', 'LIKE', "%{$query}%");
                  });
            })
            ->orderByRaw($this->buildUserOrderByClause($query));
    }

    /**
     * Build company search query
     */
    protected function buildCompanyQuery(string $query): Builder
    {
        return Company::query()
            ->with(['user', 'jobs' => function ($q) {
                $q->where('status', 'open')->limit(5);
            }])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('FullName', 'LIKE', "%{$query}%")
                            ->orWhere('UserName', 'LIKE', "%{$query}%");
                })
                ->orWhere('CompanyType', 'LIKE', "%{$query}%")
                ->orWhere('Comments', 'LIKE', "%{$query}%")
                ->orWhere('Name', 'LIKE', "%{$query}%")
                ->orWhere('country', 'LIKE', "%{$query}%")
                ->orWhere('Governate', 'LIKE', "%{$query}%");
            })
            ->orderByRaw($this->buildCompanyOrderByClause($query));
    }

    /**
     * Build post search query
     */
    protected function buildPostQuery(string $query): Builder
    {
        return PostModel::query()
            ->with([
                'user',
                'media',
                'poll.options',
                'comments' => function ($q) {
                    $q->with(['user', 'media', 'interactions'])
                      ->latest()
                      ->limit(2);
                },
                'interactions' => function ($q) {
                    $q->latest()->limit(5);
                }
            ])
            ->where(function ($q) use ($query) {
                $q->where('content', 'LIKE', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('FullName', 'LIKE', "%{$query}%")
                              ->orWhere('UserName', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('poll', function ($pollQuery) use ($query) {
                      $pollQuery->where('question', 'LIKE', "%{$query}%");
                  });
            })
            ->latest();
    }

    /**
     * Transform user results with search metadata
     */
    protected function transformUserResults(LengthAwarePaginator $paginatedUsers, string $query): LengthAwarePaginator
    {
        $userIds = $paginatedUsers->pluck('Id')->toArray();
        $connections = $this->getConnectionStatuses($userIds);

        $paginatedUsers->getCollection()->transform(function ($user) use ($query, $connections) {
            // Create a stdClass object that the resource can access
            $searchResult = new \stdClass();
            $searchResult->type = SearchTypeEnum::USER->value;
            $searchResult->id = $user->Id;
            $searchResult->data = $user;
            $searchResult->isConnected = in_array($user->Id, $connections);
            $searchResult->relevance_score = $this->calculateUserRelevanceScore($user, $query);
            $searchResult->match_type = $this->getUserMatchType($user, $query);

            return $searchResult;
        });

        return $paginatedUsers;
    }

    /**
     * Transform company results with search metadata
     */
    protected function transformCompanyResults(LengthAwarePaginator $paginatedCompanies, string $query): LengthAwarePaginator
    {
        $companyUserIds = $paginatedCompanies->pluck('UserID')->toArray();
        $followedCompanies = $this->getFollowStatuses($companyUserIds);

        $paginatedCompanies->getCollection()->transform(function ($company) use ($query, $followedCompanies) {
            // Create a stdClass object that the resource can access
            $searchResult = new \stdClass();
            $searchResult->type = SearchTypeEnum::COMPANY->value;
            $searchResult->id = $company->ID;
            $searchResult->data = $company;
            $searchResult->isFollowed = in_array($company->UserID, $followedCompanies);
            $searchResult->relevance_score = $this->calculateCompanyRelevanceScore($company, $query);
            $searchResult->match_type = $this->getCompanyMatchType($company, $query);

            return $searchResult;
        });

        return $paginatedCompanies;
    }

    /**
     * Transform post results with search metadata
     */
    protected function transformPostResults(LengthAwarePaginator $paginatedPosts, string $query): LengthAwarePaginator
    {
        $paginatedPosts->getCollection()->transform(function ($post) use ($query) {
            // Create a stdClass object that the resource can access
            $searchResult = new \stdClass();
            $searchResult->type = SearchTypeEnum::POST->value;
            $searchResult->id = $post->id;
            $searchResult->data = $post;
            $searchResult->relevance_score = $this->calculatePostRelevanceScore($post, $query);
            $searchResult->match_type = $this->getPostMatchType($post, $query);

            return $searchResult;
        });

        return $paginatedPosts;
    }

    // ... (keep all your existing helper methods exactly as they are)

    protected function getConnectionStatuses(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return ConnectionModel::where('requester_id', $this->currentUser->Id)
            ->whereIn('receiver_id', $userIds)
            ->where('type', ConnectionTypeEnum::CONNECTION)
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->pluck('receiver_id')
            ->toArray();
    }

    protected function getFollowStatuses(array $companyUserIds): array
    {
        if (empty($companyUserIds)) {
            return [];
        }

        return ConnectionModel::where('requester_id', $this->currentUser->Id)
            ->whereIn('receiver_id', $companyUserIds)
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->pluck('receiver_id')
            ->toArray();
    }

    protected function validatePerPage(?int $perPage): int
    {
        if ($perPage === null) {
            return $this->defaultPerPage;
        }

        $minPerPage = config('socialmedia.search.pagination.min_per_page', 5);
        return max($minPerPage, min($perPage, $this->maxPerPage));
    }

    protected function generateCacheKey(string $type, string $query, int $perPage, int $page): string
    {
        $prefix = config('socialmedia.search.cache.prefix', 'search:');
        $userId = $this->currentUser->Id;
        return $prefix . md5("{$type}:{$query}:{$perPage}:{$page}:{$userId}");
    }

    protected function buildUserOrderByClause(string $query): string
    {
        $query = addslashes($query);
        return "CASE
            WHEN LOWER(FullName) = LOWER('{$query}') THEN 1
            WHEN LOWER(UserName) = LOWER('{$query}') THEN 2
            WHEN FullName LIKE '%{$query}%' THEN 3
            WHEN UserName LIKE '%{$query}%' THEN 4
            ELSE 5
        END, FullName DESC";
    }

    protected function buildCompanyOrderByClause(string $query): string
    {
        $query = addslashes($query);
        return "CASE
            WHEN Name LIKE '%{$query}%' THEN 1
            WHEN CompanyType LIKE '%{$query}%' THEN 2
            WHEN country LIKE '%{$query}%' THEN 3
            ELSE 4
        END";
    }

    protected function calculateUserRelevanceScore($user, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        if (stripos($user->FullName, $query) !== false) {
            $score += config('socialmedia.search.scoring.partial_match_base', 100);
            if (strtolower($user->FullName) === $query) {
                $score += config('socialmedia.search.scoring.exact_match_bonus', 50);
            }
        }

        if (stripos($user->UserName, $query) !== false) {
            $score += 80;
            if (strtolower($user->UserName) === $query) {
                $score += 40;
            }
        }

        if ($user->portfolio) {
            if ($user->portfolio->Title && stripos($user->portfolio->Title, $query) !== false) {
                $score += 30;
            }
            if ($user->portfolio->PortfolioUrl && stripos($user->portfolio->PortfolioUrl, $query) !== false) {
                $score += 40;
            }
            if ($user->portfolio->location && stripos($user->portfolio->location, $query) !== false) {
                $score += 20;
            }
        }

        if ($user->skills) {
            foreach ($user->skills as $skill) {
                if (stripos($skill->Name, $query) !== false) {
                    $score += config('socialmedia.search.scoring.skill_match_points', 25);
                }
            }
        }

        if ($user->experiences) {
            foreach ($user->experiences as $experience) {
                if (stripos($experience->Title, $query) !== false) {
                    $score += 20;
                }
                if (stripos($experience->companyName, $query) !== false) {
                    $score += 15;
                }
            }
        }

        return round($score, 2);
    }

    protected function calculateCompanyRelevanceScore($company, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        if ($company->user) {
            if (stripos($company->user->FullName, $query) !== false) {
                $score += 100;
                if (strtolower($company->user->FullName) === $query) {
                    $score += 50;
                }
            }
        }

        if ($company->CompanyType && stripos($company->CompanyType, $query) !== false) {
            $score += 80;
        }

        if ($company->description && stripos($company->description, $query) !== false) {
            $score += 40;
        }

        if ($company->industry && stripos($company->industry, $query) !== false) {
            $score += 60;
        }

        if ($company->country && stripos($company->country, $query) !== false) {
            $score += 30;
        }
        if ($company->Governate && stripos($company->Governate, $query) !== false) {
            $score += 25;
        }

        if ($company->jobs) {
            $jobCount = $company->jobs->count();
            $score += $jobCount * 5;
        }

        return round($score, 2);
    }

    protected function calculatePostRelevanceScore($post, string $query): float
    {
        $score = 0;
        $query = strtolower($query);

        if (stripos($post->content, $query) !== false) {
            $score += 100;
            if (strpos(strtolower($post->content), $query) !== false) {
                $score += 30;
            }
        }

        if (stripos($post->user->FullName, $query) !== false) {
            $score += 60;
        }

        if ($post->poll && stripos($post->poll->question, $query) !== false) {
            $score += 50;
        }

        if ($post->interactions) {
            $interactionCount = $post->interactions->count();
            $score += $interactionCount * 2;
        }

        if ($post->comments) {
            $commentCount = $post->comments->count();
            $score += $commentCount * 3;
        }

        $daysOld = $post->created_at->diffInDays();
        if ($daysOld < 1) {
            $score += 20;
        } elseif ($daysOld < 7) {
            $score += 10;
        }

        return round($score, 2);
    }

    protected function getUserMatchType($user, string $query): string
    {
        $query = strtolower($query);

        if (strtolower($user->FullName) === $query) return 'exact_name';
        if (strtolower($user->UserName) === $query) return 'exact_username';
        if (stripos($user->FullName, $query) !== false) return 'name_partial';
        if (stripos($user->UserName, $query) !== false) return 'username_partial';
        if ($user->portfolio && $user->portfolio->headline && stripos($user->portfolio->headline, $query) !== false) return 'headline';
        if ($user->skills && $user->skills->where('name', 'LIKE', "%{$query}%")->count() > 0) return 'skill';

        return 'other';
    }

    protected function getCompanyMatchType($company, string $query): string
    {
        $query = strtolower($query);

        if ($company->user && strtolower($company->user->FullName) === $query) return 'exact_name';
        if ($company->CompanyType && stripos($company->CompanyType, $query) !== false) return 'company_type';
        if ($company->industry && stripos($company->industry, $query) !== false) return 'industry';
        if ($company->description && stripos($company->description, $query) !== false) return 'description';

        return 'other';
    }

    protected function getPostMatchType($post, string $query): string
    {
        $query = strtolower($query);

        if (strpos(strtolower($post->content), $query) !== false) return 'exact_content';
        if (stripos($post->content, $query) !== false) return 'content_partial';
        if (stripos($post->user->FullName, $query) !== false) return 'author';
        if ($post->poll && stripos($post->poll->question, $query) !== false) return 'poll';

        return 'other';
    }

    /**
     * Get single type paginator
     */
    protected function getSingleTypePaginator(array $results, SearchTypeEnum $type): LengthAwarePaginator
    {
        $key = match($type) {
            SearchTypeEnum::USER => 'users',
            SearchTypeEnum::COMPANY => 'companies',
            SearchTypeEnum::POST => 'posts',
            default => null
        };

        if (!$key || !isset($results[$key])) {
            return $this->createEmptyPaginator($this->defaultPerPage, 1);
        }

        return $results[$key];
    }

    /**
     * Get unified paginator for all types combined
     */
    protected function getUnifiedPaginator(array $results, int $perPage, int $page): LengthAwarePaginator
    {
        $allItems = collect();

        // Safely merge items from each result type
        foreach ($results as $typeKey => $typeResults) {
            if ($typeResults instanceof LengthAwarePaginator) {
                $allItems = $allItems->merge($typeResults->getCollection());
            }
        }

        // Sort by relevance score
        $sortedItems = $allItems->sortByDesc('relevance_score');

        // Create manual paginator for unified results
        $total = $sortedItems->count();
        $offset = ($page - 1) * $perPage;
        $items = $sortedItems->slice($offset, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
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
     * Create empty paginator
     */
    protected function createEmptyPaginator(int $perPage, int $page): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]),
            0,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
}
