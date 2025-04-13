<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\CommentFacade;
use App\Modules\SocialMedia\Application\Facades\InteractionFacade;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Presentation\Http\Requests\Comment\CreateCommentRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Comment\GetCommentsRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Interaction\CreateInteractionRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\CommentResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\InteractionResource;
use App\Shared\Controllers\Controller;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Get comments for a post
     *
     * @param GetCommentsRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    public function index(GetCommentsRequest $request, int $postId): JsonResponse
    {
        try {
            $comments = CommentFacade::getPostComments($postId, $request->validated());

            return $this->response->paginated(
                CommentResource::collection($comments),
                'Comments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving comments', $e->getMessage());
        }
    }

    /**
     * Create a new comment
     *
     * @param CreateCommentRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    public function store(CreateCommentRequest $request, int $postId): JsonResponse
    {
        try {
            $comment = CommentFacade::createComment($request->validated(), $postId);

            return $this->response->success(
                new CommentResource($comment),
                'Comment created successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error creating comment', $e->getMessage());
        }
    }

    /**
     * Create a comment interaction
     *
     * @param CreateInteractionRequest $request
     * @param int $commentId
     * @return JsonResponse
     */
    public function interact(CreateInteractionRequest $request, int $commentId): JsonResponse
    {
        try {
            $interaction = InteractionFacade::createInteraction(
                $request->validated(),
                $commentId,
                InteractableTargetTypeEnum::COMMENT
            );

            return $this->response->success(
                new InteractionResource($interaction),
                'Comment interaction created successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error creating comment interaction', $e->getMessage());
        }
    }

    /**
     * Delete a comment
     *
     * @param int $commentId
     * @return JsonResponse
     */
    public function destroy(int $commentId): JsonResponse
    {
        try {
            $result = CommentFacade::deleteComment($commentId, Auth::user()->Id);

            return $this->response->success(
                null,
                'Comment deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error deleting comment', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Delete a comment interaction
     *
     * @param int $commentId
     * @return JsonResponse
     */
    public function removeInteraction(int $commentId): JsonResponse
    {
        try {
            $result = InteractionFacade::deleteInteraction(
                $commentId,
                InteractableTargetTypeEnum::COMMENT,
                Auth::user()->Id
            );

            return $this->response->success(
                null,
                'Comment interaction removed successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error removing comment interaction', $e->getMessage(), $e->getCode());
        }
    }
}
