<?php

namespace App\Shared\Services;

use App\Shared\DTOs\GetUserNotificationsDTO;
use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Events\NewNotificationEvent;
use App\Shared\Models\Notification;
use App\Shared\Models\NotificationReceiver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
     * @param NotificationActionEnum $action The notification action
     * @param string $message The notification message
     * @param array|Collection $receiverIds Array or collection of user IDs who should receive the notification
     * @param bool $broadcast Whether to broadcast the notification in real-time
     * @return Notification|null
     */
    public function createNotification(
        Model $notifiable,
        NotificationActionEnum $action,
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
                'type' => $action->value,
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
                'action' => $action->value,
                'message' => $message,
            ]);

            return null;
        }
    }

    /**
     * Get notifications for a user with cursor-based pagination and filtering.
     *
     * @param GetUserNotificationsDTO $dto
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(GetUserNotificationsDTO $dto): LengthAwarePaginator
    {
        // Base query
        $query = NotificationReceiver::where('ReciverID', $dto->userId)
            ->with(['notification' => function ($query) use ($dto) {
                $query->with('notifiable');
                if ($dto->types) {
                    $query->whereIn('Notifiable_Type', $dto->types);
                }
            }])
            ->whereHas('notification', function ($query) use ($dto) {
                if ($dto->types) {
                    $query->whereIn('Notifiable_Type', $dto->types);
                }
            });

        // Apply cursor-based pagination
        if ($dto->after) {
            try {
                $afterDate = Carbon::parse($dto->after);
                $query->where('CreationDate', '<', $afterDate);
            } catch (\Exception $e) {
                Log::warning("Invalid cursor format: {$dto->after}", ['exception' => $e->getMessage()]);
            }
        }

        // Apply read status filter
        if ($dto->isRead !== null) {
            if ($dto->isRead) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Apply search filter
        if ($dto->search) {
            $query->whereHas('notification', function (Builder $q) use ($dto) {
                $q->where('massage', 'like', "%{$dto->search}%");
            });
        }

        // Order by creation date (newest first)
        $query->orderBy('CreationDate', 'desc');

        return $query->paginate($dto->limit);
    }

    /**
     * Get unread notification counts by category for a user.
     *
     * @param string $userId
     * @return array
     */
    public function getUnreadCountsByCategoies(string $userId , array $types) : array
    {
        try {
            // Initialize counts for all categories
            $counts = [
                'post' => 0,
                'connection' => 0,
                'comment' => 0,
                'poll' => 0,
            ];

            // Get unread counts grouped by notification type
            $unreadCounts = NotificationReceiver::where('ReciverID', $userId)
                ->whereNull('read_at')
                ->whereHas('notification', function (Builder $query) use ($types) {
                    $query->whereIn('Notifiable_Type', $types);
                })
                ->join('Notifications', 'NotificationRecivers.NotificationID', '=', 'Notifications.ID')
                ->select('Notifications.Notifiable_Type', DB::raw('COUNT(*) as count'))
                ->groupBy('Notifications.Notifiable_Type')
                ->get();

            // Map the counts to categories
            foreach ($unreadCounts as $unreadCount) {
                $type = NotifiableTypeEnum::tryFrom($unreadCount->Notifiable_Type)->category();

                if (isset($counts[$type])) {
                    $counts[$type] += $unreadCount->count;
                }
            }

            return $counts;
        } catch (\Exception $e) {
            Log::error('Failed to get unread counts by category: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $userId,
            ]);

            // Return default counts on error
            return [
                'post' => 0,
                'connection' => 0,
                'comment' => 0,
                'poll' => 0,
            ];
        }
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
