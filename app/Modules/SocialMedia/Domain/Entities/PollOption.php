<?php

namespace App\Modules\SocialMedia\Domain\Entities;

class PollOption
{
    private string $content;
    private int $voteCount;

    public function __construct(
        string $content,
        int $voteCount = 0
    ) {
        $this->content = $content;
        $this->voteCount = $voteCount;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getVoteCount(): int
    {
        return $this->voteCount;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'vote_count' => $this->voteCount,
        ];
    }
}

