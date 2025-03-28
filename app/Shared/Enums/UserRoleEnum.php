<?php

namespace App\Shared\Enums;

enum UserRoleEnum: int
{
    case USER = 1;
    case ADMIN = 2;
    case COMPANY = 3;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
