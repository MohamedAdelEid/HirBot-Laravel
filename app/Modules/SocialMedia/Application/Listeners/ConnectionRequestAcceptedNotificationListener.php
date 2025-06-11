<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\ConnectionRequestAcceptedEvent;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ConnectionRequestAcceptedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ConnectionRequestAcceptedEvent $event): void
    {
        try {
            $connection = $event->connection;

            // Create notification message
            $message = "{$connection->receiver->FullName} accepted your connection request";

            // Create notification for the requester
            $notification = NotificationFacade::createNotification(
                $connection,
                NotificationActionEnum::CONNECTION_REQUEST_ACCEPTED,
                $message,
                [$connection->requester_id]
            );

            if ($notification) {
                Log::info('Connection accepted notification sent successfully', [
                    'connection_id' => $connection->id,
                    'notification_id' => $notification->ID,
                    'requester' => $connection->requester_id,
                    'receiver' => $connection->receiver_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating connection accepted notification: ' . $e->getMessage(), [
                'exception' => $e,
                'connection_id' => $event->connection->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
