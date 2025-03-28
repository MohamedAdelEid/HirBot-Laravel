<?php

namespace App\Modules\SocialMedia\Domain\Enums;

enum PostMediaTypeEnum: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
