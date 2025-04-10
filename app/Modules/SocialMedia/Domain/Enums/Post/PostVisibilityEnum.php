<?php

namespace App\Modules\SocialMedia\Domain\Enums\Post;

enum PostVisibilityEnum: string
{
    case PUBLIC = 'public';
    case FRIENDS = 'friends';
    case PRIVATE = 'private';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
