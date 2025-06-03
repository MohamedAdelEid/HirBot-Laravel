<?php

namespace App\Shared\Facades;

use App\Shared\DTOs\GetUserNotificationsDTO;
use App\Shared\Enums\NotificationActionEnum;
use App\Shared\Models\Notification;
use App\Shared\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Notification|null createNotification(Model $notifiable, NotificationActionEnum $action, string $message, array|Collection $receiverIds, bool $broadcast = true)
 * @method static LengthAwarePaginator getUserNotifications(GetUserNotificationsDTO $dto)
 * @method static array getUnreadCountsByCategory(string $userId ,array $types = [])
 * @method static bool markAsRead(int $notificationReceiverId)
 * @method static int markAllAsRead(string $userId, ?array $types = null)
 *
 * @see \App\Shared\Services\NotificationService
 */
class NotificationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return NotificationService::class;
    }

    /**
     * Get user notifications with cursor-based pagination.
     *
     * @param string $userId
     * @param array $types
     * @param string $after
     * @param int $limit
     * @param bool|null $isRead
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public static function getUserNotifications(
        string $userId,
        array $types,
        string $after = '',
        int $limit = 15,
        ?bool $isRead = null,
        ?string $search = null
    ): LengthAwarePaginator {
        $dto = new GetUserNotificationsDTO(
            $userId,
            $types,
            $after,
            $limit,
            $isRead,
            $search
        );

        return static::getFacadeRoot()->getUserNotifications($dto);
    }
}
