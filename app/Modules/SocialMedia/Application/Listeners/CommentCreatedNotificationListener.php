<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\CommentCreatedEvent;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CommentCreatedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentCreatedEvent $event): void
    {
        try {
            $comment = $event->comment;
            $post = $comment->post;

            // Don't notify if user is commenting on their own post
            if ($comment->user_id === $post->user_id) {
                return;
            }

            // Create notification message
            $message = "{$comment->user->FullName} commented on your post";

            // Create notification for the post owner
            $notification = NotificationFacade::createNotification(
                $comment,
                NotificationActionEnum::COMMENT_CREATED,
                $message,
                [$post->user_id]
            );

            if ($notification) {
                Log::info('Comment notification sent successfully', [
                    'comment_id' => $comment->id,
                    'post_id' => $post->id,
                    'notification_id' => $notification->ID,
                    'recipient' => $post->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating comment notification: ' . $e->getMessage(), [
                'exception' => $e,
                'comment_id' => $event->comment->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
