<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Domain\Entities\Post;
use App\Modules\SocialMedia\Domain\Interfaces\Repositories\PostRepositoryInterface;

class PostService
{
    public function __construct(
        private PostRepositoryInterface $postRepository
    ) {}

    public function createPost(CreatePostDTO $dto): array
    {

        $post = new Post(
            $dto->userId,
            $dto->content,
            $dto->privacyComments,
            $dto->visibility,
            $dto->media,
            $dto->pollData
        );

        return $this->postRepository->create($post);
    }
}
