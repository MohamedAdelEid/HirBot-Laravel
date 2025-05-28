<?php

namespace App\Shared\Services;

use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Events\NewNotificationEvent;
use App\Shared\Models\Notification;
use App\Shared\Models\NotificationReceiver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new notification.
     *
     * @param Model $notifiable The model that the notification is about
     * @param NotifiableTypeEnum $type The notification type with sub-action
     * @param string $message The notification message
     * @param array|Collection $receiverIds Array or collection of user IDs who should receive the notification
     * @param bool $broadcast Whether to broadcast the notification in real-time
     * @return Notification|null
     */
    public function createNotification(
        Model $notifiable,
        NotifiableTypeEnum $type,
        string $message,
        array|Collection $receiverIds,
        bool $broadcast = true
    ): ?Notification {
        try {
            DB::beginTransaction();

            // Get the notifiable type from the model class
            $notifiableType = $this->getNotifiableTypeFromModel($notifiable);

            // Create the notification
            $notification = new Notification([
                'type' => $type->value,
                'Notifiable_Type' => $notifiableType,
                'Notifiable_ID' => $notifiable->getKey(),
                'massage' => $message,
            ]);

            $notification->save();

            // Create notification receivers
            foreach ($receiverIds as $receiverId) {
                $receiver = new NotificationReceiver([
                    'ReciverID' => $receiverId,
                    'NotificationID' => $notification->ID,
                ]);

                $receiver->save();

                // Broadcast the notification if requested
                if ($broadcast) {
                    event(new NewNotificationEvent($notification, $receiver));
                }
            }

            DB::commit();

            return $notification;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create notification: ' . $e->getMessage(), [
                'exception' => $e,
                'notifiable' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
                'type' => $type->value,
                'message' => $message,
            ]);

            return null;
        }
    }

    /**
     * Get notifications for a user.
     *
     * @param string $userId The user ID
     * @param array|null $types Array of notification types to filter by
     * @param int $perPage Number of items per page
     * @param bool $onlyUnread Whether to only return unread notifications
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(
        string $userId,
        ?array $types = null,
        int $perPage = 15,
        bool $onlyUnread = false
    ): LengthAwarePaginator {
        $query = NotificationReceiver::where('ReciverID', $userId)
            ->with(['notification' => function ($query) use ($types) {
                $query->with('notifiable');
                if ($types) {
                    $query->whereIn('type', $types);
                }
            }])
            ->whereHas('notification', function ($query) use ($types) {
                if ($types) {
                    $query->whereIn('type', $types);
                }
            });

        if ($onlyUnread) {
            $query->unread();
        }

        return $query->orderBy('CreationDate', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark a notification as read.
     *
     * @param int $notificationReceiverId The notification receiver ID
     * @return bool
     */
    public function markAsRead(int $notificationReceiverId): bool
    {
        try {
            $receiver = NotificationReceiver::findOrFail($notificationReceiverId);

            if ($receiver->read_at) {
                return true; // Already read
            }

            $receiver->read_at = now();
            $receiver->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage(), [
                'exception' => $e,
                'notification_receiver_id' => $notificationReceiverId,
            ]);

            return false;
        }
    }

    /**
     * Mark all notifications for a user as read.
     *
     * @param string $userId The user ID
     * @param array|null $types Array of notification types to filter by
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(string $userId, ?array $types = null): int
    {
        try {
            $query = NotificationReceiver::where('ReciverID', $userId)
                ->whereNull('read_at');

            if ($types) {
                $query->whereHas('notification', function ($q) use ($types) {
                    $q->whereIn('type', $types);
                });
            }

            $count = $query->count();

            $query->update(['read_at' => now()]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $userId,
                'types' => $types,
            ]);

            return 0;
        }
    }

    /**
     * Get the notifiable type integer from a model.
     *
     * @param Model $model The model
     * @return int
     * @throws \InvalidArgumentException If the model doesn't have a corresponding notifiable type
     */
    private function getNotifiableTypeFromModel(Model $model): int
    {
        $modelClass = get_class($model);
        $morphMap = array_flip(NotifiableTypeEnum::getMorphMap());

        if (!isset($morphMap[$modelClass])) {
            throw new \InvalidArgumentException("No notifiable type defined for model: {$modelClass}");
        }

        return $morphMap[$modelClass];
    }
}
