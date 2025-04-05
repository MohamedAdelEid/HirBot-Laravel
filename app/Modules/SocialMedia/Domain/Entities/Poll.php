<?php

namespace App\Modules\SocialMedia\Domain\Entities;

class Poll
{
    private int $postId;
    private string $question;
    private array $options;

    /**
     * @param int $postId
     * @param string $question
     * @param array<PollOption> $options
     */
    public function __construct(
        int $postId,
        string $question,
        array $options
    ) {
        $this->postId = $postId;
        $this->question = $question;
        $this->options = $options;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @return array<PollOption>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'question' => $this->question,
        ];
    }
}

