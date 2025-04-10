<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\FeedFacade;
use App\Modules\SocialMedia\Presentation\Http\Requests\Feed\GetFeedRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\PostResource;
use App\Shared\Controllers\Controller;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * Get the feed for the authenticated user
     *
     * @param GetFeedRequest $request
     * @return JsonResponse
     */
    public function index(GetFeedRequest $request): JsonResponse
    {
        try {
            $posts = FeedFacade::getUserFeed($request->validated());

            return $this->response->paginated(
                PostResource::collection($posts),
                'Feed retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving feed', $e->getMessage());
        }
    }
}
