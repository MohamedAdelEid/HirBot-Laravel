<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedService
{
    private PostViewService $postViewService;

    public function __construct(PostViewService $postViewService)
    {
        $this->postViewService = $postViewService;
    }

    /**
     * Get the feed for a user
     *
     * @param string $userId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserFeed(string $userId, array $filters = []): LengthAwarePaginator
    {
        // Get connected user IDs
        $connectedUserIds = ConnectionFacade::getConnectedUserIds($userId);

        // Get recent connections (within the last 24 hours)
        $recentConnectionIds = ConnectionModel::where(function($query) use ($userId) {
            $query->where('requester_id', $userId)
                  ->orWhere('receiver_id', $userId);
        })->where('status', ConnectionStatusEnum::ACCEPTED)
          ->where('created_at', '>=', now()->subDay())
          ->get()
          ->map(function($connection) use ($userId) {
              return $connection->requester_id === $userId ? $connection->receiver_id : $connection->requester_id;
          })
          ->toArray();

        // Get posts the user has previously interacted with
        $interactedPostIds = $this->postViewService->getInteractedPostIds($userId);

        // Start building the query
        $query = PostModel::with([
                'media',
                'poll.options',
                // Load user with company or portfolio for title
                'user' => function($query) {
                    $query->with(['company', 'portfolio', 'requestedConnections', 'receivedConnections']);
                },
                // Only load the last two comments "recent_comment Only top-level" with their users
                'comments' => function ($query) {
                    $query->whereNull('comments.parent_comment_id')
                        ->latest()
                        ->limit(2)
                        ->with([
                            // Load the user of the comment with their company and portfolio
                            'user' => function ($query) {
                                $query->with(['company', 'portfolio']);
                            },
                            // Load only first replies of the comment
                            'replies' => function ($query) {
                                $query->with([
                                    'user' => function ($query) {
                                        $query->with(['company', 'portfolio']);
                                    },
                                    'interactions'
                                ])->oldest()->limit(1);
                            },
                            // Load interactions on the comment itself
                            'interactions'
                        ]);
                },
                // Load the last interaction for notification purposes
                'interactions' => function($query) use ($connectedUserIds) {
                    $query->whereIn('user_id', $connectedUserIds)
                            ->where('created_at', '>=', now()->subDay())
                            ->latest()
                            ->limit(2)
                            ->with(['user' => function($query) {
                                $query->with(['company', 'portfolio']);
                            }]);
                }
            ])
            ->where(function($query) use ($userId, $connectedUserIds) {
                // Include public posts from any user
                $query->where('posts.visibility', PostVisibilityEnum::PUBLIC->value);

                // Or include posts from connected users with visibility 'friends' or 'public'
                if (!empty($connectedUserIds)) {
                    $query->orWhere(function($q) use ($connectedUserIds) {
                        $q->whereIn('posts.user_id', $connectedUserIds)
                        ->whereIn('posts.visibility', [
                            PostVisibilityEnum::FRIENDS->value,
                            PostVisibilityEnum::PUBLIC->value
                        ]);
                    });
                }

                // Or include posts from the authenticated user
                $query->orWhere('posts.user_id', $userId);
            });

        // Apply refresh filter (get only posts newer than the specified timestamp)
        if (isset($filters['doFeedRefresh']) && $filters['doFeedRefresh'] === true && isset($filters['lastUpdatedAt'])) {
            $query->where('created_at', '>', $filters['lastUpdatedAt']);
        }

        // Add a simpler subquery to check for new activity
        $query->leftJoin('post_views', function($join) use ($userId) {
            $join->on('posts.id', '=', 'post_views.post_id')
                 ->where('post_views.user_id', '=', $userId);
        });

        // After the left join with post_views
        $query->select('posts.*');

        // Then add the subqueries for connection_engagement_count, etc.

        // Add a subquery to check for connection engagement
        $connectionEngagementSubquery = DB::table('comments')
            ->select(DB::raw('COUNT(*)'))
            ->whereColumn('post_id', 'posts.id')
            ->whereIn('user_id', $connectedUserIds ?: [0]);

        if (!empty($connectedUserIds)) {
            $query->selectSub($connectionEngagementSubquery, 'connection_engagement_count');
        } else {
            $query->selectRaw('0 as connection_engagement_count');
        }

        // Add a subquery to check for connection interaction with other posts
        $connectionInteractionSubquery = DB::table('comments')
            ->select(DB::raw('COUNT(*)'))
            ->whereColumn('post_id', 'posts.id')
            ->whereIn('user_id', $connectedUserIds ?: [0])
            ->where('posts.user_id', '!=', $userId);

        if (!empty($connectedUserIds)) {
            $query->selectSub($connectionInteractionSubquery, 'connection_interaction_count');
        } else {
            $query->selectRaw('0 as connection_interaction_count');
        }

        // Check if post has new activity since last view
        $newActivitySubquery = DB::table('comments')
            ->select(DB::raw('COUNT(*)'))
            ->whereColumn('post_id', 'posts.id')
            ->where(function($query) {
                $query->whereNull('post_views.last_viewed_at')
                      ->orWhere('comments.created_at', '>', 'post_views.last_viewed_at');
            });

        $query->selectSub($newActivitySubquery, 'has_new_activity');

        // Select if the post has been viewed
        $query->selectRaw('CASE WHEN post_views.id IS NULL THEN 0 ELSE 1 END as is_viewed');

        // Get posts with engagement metrics
        $query->withCount(['comments']);

        // Count interactions for posts using the polymorphic relationship
        $query->withCount([
            'interactions' => function($query) {
                $query->where('interactable_type', InteractableTargetTypeEnum::POST->morphClass());
            }
        ]);

        // Build the ORDER BY clause
        $orderByClause = "
            CASE
                -- 1. User's own posts with new interactions since last view
                WHEN posts.user_id = ? AND has_new_activity > 0 THEN 1

                -- 2. Posts from recent connections
                WHEN posts.user_id IN (" . implode(',', array_map(function($id) { return "?"; }, $recentConnectionIds ?: [0])) . ") THEN 2

                -- 3. Posts where user's connections engaged with content
                WHEN connection_engagement_count > 0 THEN 3

                -- 4. Posts from connections who interacted with other posts
                WHEN connection_interaction_count > 0 AND posts.user_id IN (" . implode(',', array_map(function($id) { return "?"; }, $connectedUserIds ?: [0])) . ") THEN 4

                -- 5. Posts the user has previously interacted with
                WHEN posts.id IN (" . implode(',', array_map(function($id) { return "?"; }, $interactedPostIds ?: [0])) . ") THEN 5

                -- 6. Public posts from non-connections
                ELSE 6
            END,

            -- Unseen posts have higher priority than seen posts
            is_viewed ASC,

            -- For other posts, sort by engagement and recency
            (comments_count + interactions_count) DESC,
            posts.created_at DESC
        ";

        // Prepare the binding parameters
        $bindingParams = array_merge(
            [$userId],
            $recentConnectionIds ?: [0],
            $connectedUserIds ?: [0],
            $interactedPostIds ?: [0]
        );

        // Apply the order by with bindings
        $query->orderByRaw($orderByClause, $bindingParams);

        // For posts from recent connections, we'll randomize the results after fetching
        $isRandomizeRecentConnections = !empty($recentConnectionIds);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        // If we need to randomize posts from recent connections, do it here
        if ($isRandomizeRecentConnections) {
            $items = $results->items();

            // Separate posts from recent connections
            $recentConnectionPosts = [];
            $otherPosts = [];

            foreach ($items as $post) {
                if (in_array($post->user_id, $recentConnectionIds)) {
                    $recentConnectionPosts[] = $post;
                } else {
                    $otherPosts[] = $post;
                }
            }

            // Randomize the recent connection posts
            shuffle($recentConnectionPosts);

            // Merge the arrays back together
            $randomizedItems = array_merge($recentConnectionPosts, $otherPosts);

            // Replace the items in the paginator
            $results->setCollection(collect($randomizedItems));
        }

        return $results;
    }
}
