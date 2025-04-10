<?php

namespace App\Modules\SocialMedia\Domain\Enums\Connection;

enum ConnectionTypeEnum: string
{
    case FOLLOW = 'follow';
    case CONNECTION = 'connection';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
