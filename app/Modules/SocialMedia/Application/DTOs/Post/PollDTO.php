<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

class PollDTO
{
    /**
     * @param string $question
     * @param PollOptionDTO[] $options
     */
    public function __construct(
        public readonly string $question,
        public readonly array $options
    ) {}

    public static function fromArray(array $data): self
    {
        $options = array_map(
            fn(array $option) => PollOptionDTO::fromArray([
                'content' => $option,
                'vote_count' => 0
            ]),
            $data['options']
        );

        return new self(
            question: $data['question'],
            options: $options
        );
    }
}
