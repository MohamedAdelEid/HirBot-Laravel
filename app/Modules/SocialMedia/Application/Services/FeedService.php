<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedService
{
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
        })->where('status', 'accepted')
          ->where('created_at', '>=', now()->subDay())
          ->get()
          ->map(function($connection) use ($userId) {
              return $connection->requester_id === $userId ? $connection->receiver_id : $connection->requester_id;
          })
          ->toArray();

        // Start building the query
        $query = PostModel::with(['media', 'poll.options', 'user', 'comments', 'interactions'])
                         ->where(function($query) use ($userId, $connectedUserIds) {
                             // Include posts from connected users
                             $query->whereIn('user_id', $connectedUserIds)
                                   // Include public posts
                                   ->orWhere('visibility', 'public');
                         });

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }

        // Apply visibility filter
        if (isset($filters['visibility']) && !empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        // Get posts with engagement metrics
        $posts = $query->withCount(['comments', 'interactions'])
                      ->orderByRaw('
                          CASE
                              WHEN user_id IN (' . implode(',', array_map(function($id) { return "'" . $id . "'"; }, $recentConnectionIds ?: ['0'])) . ') THEN 1
                              ELSE 2
                          END,
                          (comments_count + interactions_count) DESC,
                          created_at DESC
                      ');

        // Apply pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $posts->paginate($perPage, ['*'], 'page', $page);
    }
}
