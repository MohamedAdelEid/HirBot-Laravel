<?php

namespace App\Modules\SocialMedia\Application\DTOs\Connection;

use Illuminate\Support\Facades\Auth;

class ProcessConnectionDTO
{
    public function __construct(
        public readonly int $connectionId,
        public readonly string $userId
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            connectionId: $data['connection_id'],
            userId: Auth::user()->Id
        );
    }
}
