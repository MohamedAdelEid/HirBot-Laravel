<?php

namespace App\Modules\SocialMedia\Domain\Enums\Interaction;

enum InteractionTypeEnum: string
{
    case LIKE = 'like';
    case DISLIKE = 'dislike';
    case LOVE = 'love';
    case LAUGH = 'laugh';
    case ANGRY = 'angry';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
