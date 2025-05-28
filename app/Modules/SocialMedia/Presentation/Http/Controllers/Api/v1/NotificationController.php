<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Shared\Controllers\Controller;
use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Facades\NotificationFacade;
use App\Shared\Interfaces\ResponseInterface;
use App\Shared\Resources\NotificationReceiverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Get social media notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 15);
            $onlyUnread = $request->boolean('unread', false);

            $notifications = NotificationFacade::getUserNotifications(
                Auth::user()->Id,
                NotifiableTypeEnum::socialMediaTypes(),
                $perPage,
                $onlyUnread
            );

            return $this->response->success(
                NotificationReceiverResource::collection($notifications),
                'Notifications retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving notifications', $e->getMessage());
        }
    }

    /**
     * Mark a notification as read.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $result = NotificationFacade::markAsRead($id);

            if ($result) {
                return $this->response->success(
                    null,
                    'Notification marked as read successfully'
                );
            }

            return $this->response->error('Failed to mark notification as read');
        } catch (\Exception $e) {
            return $this->response->error('Error marking notification as read', $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read.
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $count = NotificationFacade::markAllAsRead(
                Auth::user()->Id,
                NotifiableTypeEnum::socialMediaTypes()
            );

            return $this->response->success(
                ['count' => $count],
                'All notifications marked as read successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error marking all notifications as read', $e->getMessage());
        }
    }
}
