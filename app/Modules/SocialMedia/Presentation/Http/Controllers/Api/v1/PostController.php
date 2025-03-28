<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\PostFacade;
use App\Modules\SocialMedia\Presentation\Http\Requests\Post\CreatePostRequest;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use App\Shared\Controllers\Controller;

class PostController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    public function store(CreatePostRequest $request): JsonResponse
    {
        try {
            $post = PostFacade::createPost($request->validated());

            return $this->response->success($post, 'Post created successfully');

        } catch (\Exception $e) {
            return $this->response->error('Error creating post', $e->getMessage());
        }
    }
}
