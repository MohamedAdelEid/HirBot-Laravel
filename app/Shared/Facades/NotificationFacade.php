<?php

namespace App\Shared\Facades;

use App\Shared\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Shared\Models\Notification|null createNotification(Model $notifiable, \App\Shared\Enums\NotifiableTypeEnum $type, string $message, array|Collection $receiverIds, bool $broadcast = true)
 * @method static LengthAwarePaginator getUserNotifications(string $userId, ?array $types = null, int $perPage = 15, bool $onlyUnread = false)
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
}
