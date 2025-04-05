<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

class PollDTO
{
    /**
     * @param string $question
     * @param array<PollOptionDTO> $options
     */
    public function __construct(
        public readonly string $question,
        public readonly array $options
    ) {}

    public static function fromArray(array $data): self
    {
        $options = [];
        foreach ($data['options'] as $key => $option) {
            // If option is an array with 'content' key
            if (is_array($option) && isset($option['content'])) {
                $optionData = [
                    'content' => $option['content'],
                    'vote_count' => 0
                ];

                // If option has an ID, it's an existing option being updated
                if (isset($option['id'])) {
                    $optionData['id'] = $option['id'];
                }

                $options[] = PollOptionDTO::fromArray($optionData);
            }
        }

        return new self(
            question: $data['question'],
            options: $options
        );
    }

    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'options' => array_map(
                fn(PollOptionDTO $option) => $option->toArray(),
                $this->options
            )
        ];
    }
}

