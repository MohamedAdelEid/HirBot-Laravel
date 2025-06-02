<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\NewPostEvent;
use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNewPostNotification implements ShouldQueue
{
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
            $user = $post->user;

            // Get all connected users (followers/connections)
            $connectedUserIds = ConnectionFacade::getConnectedUserIds($user->Id);

            if (empty($connectedUserIds)) {
                Log::info('No connected users found for post notification', [
                    'post_id' => $post->id,
                    'user_id' => $user->Id
                ]);
                return;
            }

            // Create the notification message
            $message = "{$user->FullName} has published a new post";
            
            // Create and send the notification with the specific type
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
                    'user_id' => $user->Id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception in CreateNotificationListener: ' . $e->getMessage(), [
                'exception' => $e,
                'post_id' => $event->post->id ?? null
            ]);
        }
    }
}
