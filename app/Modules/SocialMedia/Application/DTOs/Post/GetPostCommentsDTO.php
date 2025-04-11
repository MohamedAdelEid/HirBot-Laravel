<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

class GetPostCommentsDTO
{
    public function __construct(
        public readonly int $postId,
        public readonly ?int $page = 1,
        public readonly ?int $perPage = 15
    ) {}

    public static function fromRequest(array $data, int $postId): self
    {
        return new self(
            postId: $postId,
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 15
        );
    }
}
