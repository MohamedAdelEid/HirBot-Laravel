<?php

namespace App\Modules\SocialMedia\Application\DTOs\Feed;

use Illuminate\Support\Facades\Auth;

class GetFeedDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly ?int $page = 1,
        public readonly ?int $perPage = 15,
        public readonly ?string $search = null,
        public readonly ?string $visibility = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            userId: Auth::user()->Id,
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 15,
            search: $data['search'] ?? null,
            visibility: $data['visibility'] ?? null
        );
    }
}
