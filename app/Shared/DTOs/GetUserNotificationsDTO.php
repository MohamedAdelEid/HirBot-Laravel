<?php

namespace App\Shared\DTOs;

class GetUserNotificationsDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly array $types,
        public readonly ?string $after = null,
        public readonly int $limit = 15,
        public readonly ?bool $isRead = null,
        public readonly ?string $search = null
    ) {}

    /**
     * Create DTO from request parameters.
     *
     * @param array $params
     * @return self
     */
    public static function fromArray(array $params): self
    {
        return new self(
            userId: $params['userId'],
            types: $params['types'] ?? [],
            after: $params['after'] ?? null,
            limit: $params['limit'] ?? 15,
            isRead: $params['isRead'] ?? null,
            search: $params['search'] ?? null
        );
    }
}
