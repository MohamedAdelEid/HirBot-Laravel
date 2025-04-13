<?php

namespace App\Modules\SocialMedia\Domain\Entities;

class Comment
{
    private string $userId;
    private int $postId;
    private ?string $content;
    private ?string $imagePath;
    private ?int $parentCommentId;

    public function __construct(
        string $userId,
        int $postId,
        ?string $content = null,
        ?string $imagePath = null,
        ?int $parentCommentId = null
    ) {
        $this->userId = $userId;
        $this->postId = $postId;
        $this->content = $content;
        $this->imagePath = $imagePath;
        $this->parentCommentId = $parentCommentId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getParentCommentId(): ?int
    {
        return $this->parentCommentId;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'post_id' => $this->postId,
            'content' => $this->content,
            'image_path' => $this->imagePath,
            'parent_comment_id' => $this->parentCommentId,
        ];
    }
}
