<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

class PollOptionDTO
{
    public function __construct(
        public readonly string $content,
        public readonly int $voteCount = 0,
        public readonly ?int $id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            voteCount: $data['vote_count'] ?? 0,
            id: $data['id'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
            'content' => $this->content,
            'vote_count' => $this->voteCount
        ];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        return $data;
    }
}

