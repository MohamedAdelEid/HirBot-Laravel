<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\ConnectionRequestRejectedEvent;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ConnectionRequestRejectedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ConnectionRequestRejectedEvent $event): void
    {
        try {
            $connection = $event->connection;

            // Create notification message
            $message = "{$connection->receiver->FullName} declined your connection request";

            // Create notification for the requester
            $notification = NotificationFacade::createNotification(
                $connection,
                NotificationActionEnum::CONNECTION_REQUEST_REJECTED,
                $message,
                [$connection->requester_id]
            );

            if ($notification) {
                Log::info('Connection rejected notification sent successfully', [
                    'connection_id' => $connection->id,
                    'notification_id' => $notification->ID,
                    'requester' => $connection->requester_id,
                    'receiver' => $connection->receiver_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating connection rejected notification: ' . $e->getMessage(), [
                'exception' => $e,
                'connection_id' => $event->connection->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
