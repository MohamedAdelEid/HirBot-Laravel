<?php

namespace App\Modules\SocialMedia\Application\Listeners;

use App\Modules\SocialMedia\Application\Events\ConnectionRequestSentEvent;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Facades\NotificationFacade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ConnectionRequestSentNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ConnectionRequestSentEvent $event): void
    {
        try {
            $connection = $event->connection;

            // Only notify for connection requests (not follows)
            if ($connection->type->value !== 'connection') {
                return;
            }

            // Create notification message
            $message = "{$connection->requester->FullName} sent you a connection request";

            // Create notification for the receiver
            $notification = NotificationFacade::createNotification(
                $connection,
                NotificationActionEnum::CONNECTION_REQUEST_SENT,
                $message,
                [$connection->receiver_id]
            );

            if ($notification) {
                Log::info('Connection request notification sent successfully', [
                    'connection_id' => $connection->id,
                    'notification_id' => $notification->ID,
                    'requester' => $connection->requester_id,
                    'receiver' => $connection->receiver_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating connection request notification: ' . $e->getMessage(), [
                'exception' => $e,
                'connection_id' => $event->connection->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
