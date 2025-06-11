<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\InteractionAddedEvent;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InteractionAddedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(InteractionAddedEvent $event): void
    {
        try {
            $interaction = $event->interaction;
            $interactable = $interaction->interactable;

            // Only handle post interactions for now
            if ($interaction->interactable_type !== 'post') {
                return;
            }

            // Don't notify if user is interacting with their own post
            if ($interaction->user_id === $interactable->user_id) {
                return;
            }

            // Create notification message based on interaction type
            $actionType = match($interaction->type) {
                'like' => 'liked',
                'love' => 'loved',
                'celebrate' => 'celebrated',
                'support' => 'supported',
                'insightful' => 'found insightful',
                default => 'reacted to'
            };

            $message = "{$interaction->user->FullName} {$actionType} your post";

            // Create notification for the post owner
            $notification = NotificationFacade::createNotification(
                $interactable,
                NotificationActionEnum::INTERACTION_ADDED,
                $message,
                [$interactable->user_id]
            );

            if ($notification) {
                Log::info('Interaction notification sent successfully', [
                    'interaction_id' => $interaction->id,
                    'post_id' => $interactable->id,
                    'interaction_type' => $interaction->type,
                    'notification_id' => $notification->ID,
                    'recipient' => $interactable->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating interaction notification: ' . $e->getMessage(), [
                'exception' => $e,
                'interaction_id' => $event->interaction->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
