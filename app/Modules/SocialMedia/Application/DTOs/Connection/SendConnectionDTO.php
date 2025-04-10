<?php

namespace App\Modules\SocialMedia\Application\DTOs\Connection;

use Illuminate\Support\Facades\Auth;

class SendConnectionDTO
{
    public function __construct(
        public readonly string $requesterId,
        public readonly string $receiverId
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            requesterId: Auth::user()->Id,
            receiverId: $data['receiver_id']
        );
    }
}
