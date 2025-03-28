<?php

namespace App\Modules\SocialMedia\Domain\Interfaces\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;

interface PostServiceInterface
{
    /**
     * Create a new post
     *
     * @param CreatePostDTO $dto
     * @return array
     */
    public function createPost(CreatePostDTO $dto): array;
}