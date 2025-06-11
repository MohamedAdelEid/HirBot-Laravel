<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\NewPostEvent;
use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param NewPostEvent $event
     * @return void
     */
    public function handle(NewPostEvent $event): void
    {
        try {
            $post = $event->post;

            // Get all connected users for the post author
            $connectedUserIds = ConnectionFacade::getConnectedUserIds($post->user_id);

            if (empty($connectedUserIds)) {
                Log::info('No connected users found for post author', ['user_id' => $post->user_id]);
                return;
            }

            // Create notification message
            $message = "{$post->user->FullName} has published a new post";

            // Create notification for all connected users
            $notification = NotificationFacade::createNotification(
                $post,
                NotificationActionEnum::POST_CREATED,
                $message,
                $connectedUserIds
            );

            if ($notification) {
                Log::info('Post creation notification sent successfully', [
                    'post_id' => $post->id,
                    'notification_id' => $notification->ID,
                    'recipients_count' => count($connectedUserIds)
                ]);
            } else {
                Log::error('Failed to create post notification', [
                    'post_id' => $post->id,
                    'user_id' => $post->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating post notification: ' . $e->getMessage(), [
                'exception' => $e,
                'post_id' => $event->post->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
