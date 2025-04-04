<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

class PollOptionDTO
{
    public function __construct(
        public readonly string $content,
        public readonly int $voteCount = 0
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            voteCount: $data['vote_count'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'vote_count' => $this->voteCount
        ];
    }
}
