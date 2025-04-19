<?php

namespace App\Modules\SocialMedia\Domain\Entities;

class PollVote
{
    private string $userId;
    private int $optionId;
    private int $pollId;

    public function __construct(
        string $userId,
        int $optionId,
        int $pollId
    ) {
        $this->userId = $userId;
        $this->optionId = $optionId;
        $this->pollId = $pollId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getOptionId(): int
    {
        return $this->optionId;
    }

    public function getPollId(): int
    {
        return $this->pollId;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'option_id' => $this->optionId,
            'poll_id' => $this->pollId,
        ];
    }
}
