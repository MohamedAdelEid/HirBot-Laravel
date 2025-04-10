<?php

namespace App\Modules\SocialMedia\Domain\Enums\Connection;

enum ConnectionStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
