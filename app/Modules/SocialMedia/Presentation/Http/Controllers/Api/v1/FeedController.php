<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\FeedFacade;
use App\Modules\SocialMedia\Application\Facades\PostViewFacade;
use App\Modules\SocialMedia\Presentation\Http\Requests\Feed\GetFeedRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\PostResource;
use App\Shared\Controllers\Controller;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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

            // Record views if it's not just a refresh
            if (!($request->input('doFeedRefresh', false))) {
                foreach ($posts as $post) {
                    PostViewFacade::recordView(Auth::user()->Id, $post->id);
                }
            }

            return $this->response->paginated(
                PostResource::collection($posts),
                'Feed retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving feed', $e->getMessage());
        }
    }
}
