<?php

namespace App\Modules\SocialMedia\Domain\Entities;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;

class Connection
{
    private string $requesterId;
    private string $receiverId;
    private ConnectionStatusEnum $status;
    private ConnectionTypeEnum $type;

    public function __construct(
        string $requesterId,
        string $receiverId,
        ConnectionStatusEnum $status = ConnectionStatusEnum::PENDING,
        ConnectionTypeEnum $type = ConnectionTypeEnum::CONNECTION
    ) {
        $this->requesterId = $requesterId;
        $this->receiverId = $receiverId;
        $this->status = $status;
        $this->type = $type;
    }

    public function getRequesterId(): string
    {
        return $this->requesterId;
    }

    public function getReceiverId(): string
    {
        return $this->receiverId;
    }

    public function getStatus(): ConnectionStatusEnum
    {
        return $this->status;
    }

    public function getType(): ConnectionTypeEnum
    {
        return $this->type;
    }

    public function setStatus(ConnectionStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'requester_id' => $this->requesterId,
            'receiver_id' => $this->receiverId,
            'status' => $this->status,
            'type' => $this->type,
        ];
    }
}
