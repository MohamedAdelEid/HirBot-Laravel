<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

use Illuminate\Support\Facades\Auth;

class VotePollDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly int $optionId,
        public readonly int $pollId
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: Auth::user()->Id,
            optionId: $data['option_id'],
            pollId: $data['poll_id']
        );
    }
}
