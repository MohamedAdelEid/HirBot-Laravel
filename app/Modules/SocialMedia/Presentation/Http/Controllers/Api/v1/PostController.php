<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\InteractionFacade;
use App\Modules\SocialMedia\Application\Facades\PostFacade;
use App\Modules\SocialMedia\Application\Facades\PollFacade;
use App\Modules\SocialMedia\Application\Facades\PostViewFacade;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Modules\SocialMedia\Presentation\Http\Requests\Poll\VotePollRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Poll\PollVoteResource;
use App\Modules\SocialMedia\Presentation\Http\Requests\Interaction\CreateInteractionRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\CreatePostRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\GetAllPostsRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\GetPostCommentsRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\GetPostInteractionsRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\UpdatePostRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Post\PostResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\InteractionResource;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use App\Shared\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {
    }

    /**
     * Get all posts with pagination
     *
     * @param GetAllPostsRequest $request
     * @return JsonResponse
     */
    public function index(GetAllPostsRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $posts = PostFacade::getAllPosts($filters);

            return $this->response->paginated(
                PostResource::collection($posts),
                'Posts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving posts', $e->getMessage());
        }
    }

    public function store(CreatePostRequest $request): JsonResponse
    {
        try {
            $post = PostFacade::createPost($request->validated());

            return $this->response->success($post, 'Post created successfully');

        } catch (\Exception $e) {
            return $this->response->error('Error creating post', $e->getMessage());
        }
    }

    /**
     * Get a single post by ID
     *
     * @param PostModel $post
     * @return JsonResponse
     */
    public function show(PostModel $post): JsonResponse
    {
        try {
            $post = PostFacade::getPost($post->id);

            PostViewFacade::recordView(Auth::user()->Id, $post->id);

            return $this->response->success(new PostResource($post), 'Post retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->response->error('Post not found', $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving post', $e->getMessage());
        }
    }

    public function update(UpdatePostRequest $request, PostModel $post): JsonResponse
    {
        try {
            $post = PostFacade::updatePost($post, $request->validated());

            return $this->response->success($post, 'Post updated successfully');

        } catch (\Exception $e) {
            return $this->response->error('Error updating post', $e->getMessage());
        }
    }

    /**
     * Soft delete a post
     *
     * @param PostModel $post
     * @return JsonResponse
     */
    public function destroy(PostModel $post): JsonResponse
    {
        try {
            PostFacade::deletePost($post->id);

            return $this->response->success(null, 'Post deleted successfully');
        } catch (\Exception $e) {
            return $this->response->error('Error deleting post', $e->getMessage());
        }
    }

    /**
     * Force delete a post
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDestroy(int $id): JsonResponse
    {
        try {
            // Find the post (including trashed)
            $post = PostModel::withTrashed()->findOrFail($id);

            PostFacade::forceDeletePost($post->id);

            return $this->response->success(null, 'Post permanently deleted successfully');
        } catch (\Exception $e) {
            return $this->response->error('Error deleting post', $e->getMessage());
        }
    }

    /**
     * Get comments for a post
     *
     * @param GetPostCommentsRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    public function getComments(GetPostCommentsRequest $request, int $postId): JsonResponse
    {
        try {
            // Check if post exists
            $post = PostFacade::getPost($postId);

            $comments = PostFacade::getPostComments($postId, $request->validated());

            return $this->response->paginated($comments, 'Comments retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->response->error('Post not found', 'The requested post could not be found.', 404);
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving comments', $e->getMessage());
        }
    }

    /**
     * Get interactions for a post
     *
     * @param GetPostInteractionsRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    public function getInteractions(GetPostInteractionsRequest $request, int $postId): JsonResponse
    {
        try {
            // Check if post exists
            $post = PostFacade::getPost($postId);

            $interactions = PostFacade::getPostInteractions($postId, $request->validated());

            return $this->response->paginated($interactions, 'Interactions retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->response->error('Post not found', 'The requested post could not be found.', 404);
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving interactions', $e->getMessage());
        }
    }

        /**
     * Create a post interaction
     *
     * @param CreateInteractionRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    public function interact(CreateInteractionRequest $request, int $postId): JsonResponse
    {
        try {
            $interaction = InteractionFacade::createInteraction(
                $request->validated(),
                $postId,
                InteractableTargetTypeEnum::POST
            );

            return $this->response->success(
                new InteractionResource($interaction),
                'Post interaction created successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error creating post interaction', $e->getMessage());
        }
    }

    /**
     * Delete a post interaction
     *
     * @param int $postId
     * @return JsonResponse
     */
    public function removeInteraction(int $postId): JsonResponse
    {
        try {
            $result = InteractionFacade::deleteInteraction(
                $postId,
                InteractableTargetTypeEnum::POST,
                Auth::user()->Id
            );

            return $this->response->success(
                null,
                'Post interaction removed successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error removing post interaction', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Vote on a poll option
     *
     * @param VotePollRequest $request
     * @return JsonResponse
     */
    public function votePoll(VotePollRequest $request): JsonResponse
    {
        try {
            $vote = PollFacade::vote($request->validated());

            return $this->response->success(
                new PollVoteResource($vote),
                'Poll vote recorded successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error voting on poll', $e->getMessage());
        }
    }

    /**
     * Remove a vote from a poll
     *
     * @param int $pollId
     * @return JsonResponse
     */
    public function removeVote(int $pollId): JsonResponse
    {
        try {
            $result = PollFacade::removeVote(Auth::user()->Id, $pollId);

            return $this->response->success(
                null,
                'Poll vote removed successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error removing poll vote', $e->getMessage(), $e->getCode());
        }
    }
}
