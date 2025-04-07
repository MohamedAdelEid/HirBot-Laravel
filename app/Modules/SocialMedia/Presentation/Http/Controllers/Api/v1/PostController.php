<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\PostFacade;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\CreatePostRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\GetAllPostsRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\UpdatePostRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Post\PostResource;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use App\Shared\Controllers\Controller;

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

}
