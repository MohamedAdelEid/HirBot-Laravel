<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Presentation\Http\Requests\Notification\NotificationRequest;
use App\Shared\Controllers\Controller;
use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Facades\NotificationFacade;
use App\Shared\Interfaces\ResponseInterface;
use App\Shared\Resources\NotificationReceiverResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Get social media notifications for the authenticated user with cursor-based pagination.
     *
     * @param NotificationRequest $request
     * @return JsonResponse
     */
    public function index(NotificationRequest $request): JsonResponse
    {
        try {
            // Extract validated parameters from request
            $after = $request->query('after');
            $limit = (int) $request->query('limit', 15);
            $isRead = $request->has('is_read') ? filter_var($request->query('is_read'), FILTER_VALIDATE_BOOLEAN) : null;
            $search = $request->query('search');

            // Get notification types from the request
            $categories = $request->getNotificationTypes();

            // Get notifications using the facade
            $notifications = NotificationFacade::getUserNotifications(
                Auth::user()->Id,
                $categories,
                $after ?? '',
                $limit,
                $isRead,
                $search
            );

            // Get unread counts by category
            $unreadCounts = NotificationFacade::getUnreadCountsByCategoies(Auth::user()->Id, NotifiableTypeEnum::socialMediaTypes());

            // Transform the data
            $resourceCollection = NotificationReceiverResource::collection($notifications);

            // Return cursor-paginated response
            return $this->response->cursorPaginated(
                $resourceCollection,
                $unreadCounts,
                'Notifications retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error retrieving notifications: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
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
