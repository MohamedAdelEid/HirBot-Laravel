<?php

namespace App\Modules\SocialMedia\Application\DTOs\Comment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class CreateCommentDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly int $postId,
        public readonly ?string $content = null,
        public readonly ?UploadedFile $image = null,
        public readonly ?int $parentCommentId = null
    ) {}

    public static function fromRequest(array $data, int $postId): self
    {
        return new self(
            userId: Auth::user()->Id,
            postId: $postId,
            content: $data['content'] ?? null,
            image: $data['image'] ?? null,
            parentCommentId: $data['parent_comment_id'] ?? null
        );
    }
}
