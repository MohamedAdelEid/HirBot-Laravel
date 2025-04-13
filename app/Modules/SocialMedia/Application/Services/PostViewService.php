<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostViewModel;
use Illuminate\Support\Facades\DB;

class PostViewService
{
    /**
     * Record a post view
     *
     * @param string $userId
     * @param int $postId
     * @return PostViewModel
     */
    public function recordView(string $userId, int $postId): PostViewModel
    {
        try {
            DB::beginTransaction();

            $postView = PostViewModel::updateOrCreate(
                ['user_id' => $userId, 'post_id' => $postId],
                ['last_viewed_at' => now()]
            );

            DB::commit();

            return $postView;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if a post has new activity since last view
     *
     * @param string $userId
     * @param int $postId
     * @return bool
     */
    public function hasNewActivity(string $userId, int $postId): bool
    {
        $postView = PostViewModel::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if (!$postView) {
            return true; // Never viewed, so it has new activity
        }

        // Check if there are comments or interactions after the last view
        $newActivity = DB::table('comments')
            ->where('post_id', $postId)
            ->where('created_at', '>', $postView->last_viewed_at)
            ->exists();

        if (!$newActivity) {
            $newActivity = DB::table('interactions')
                ->where('interactable_id', $postId)
                ->where('interactable_type', InteractableTargetTypeEnum::POST->morphClass())
                ->where('created_at', '>', $postView->last_viewed_at)
                ->exists();
        }

        return $newActivity;
    }

    /**
     * Get posts the user has previously interacted with
     *
     * @param string $userId
     * @return array
     */
    public function getInteractedPostIds(string $userId): array
    {
        // Get posts the user has commented on
        $commentedPostIds = DB::table('comments')
            ->where('user_id', $userId)
            ->pluck('post_id')
            ->toArray();

        // Get posts the user has interacted with
        $interactedPostIds = DB::table('interactions')
            ->where('user_id', $userId)
            ->where('interactable_type', InteractableTargetTypeEnum::POST->morphClass())
            ->pluck('interactable_id')
            ->toArray();

        // Combine and remove duplicates
        return array_unique(array_merge($commentedPostIds, $interactedPostIds));
    }
}
