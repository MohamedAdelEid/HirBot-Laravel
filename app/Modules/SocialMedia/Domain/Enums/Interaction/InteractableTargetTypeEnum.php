<?php

namespace App\Modules\SocialMedia\Domain\Enums\Interaction;

enum InteractableTargetTypeEnum: string
{
    case POST = 'post';
    case COMMENT = 'comment';

    public function modelClass(): string
    {
        return match($this) {
            self::POST => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel::class,
            self::COMMENT => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel::class,
        };
    }

    public function morphClass(): string
    {
        return match($this) {
            self::POST => 'post',
            self::COMMENT => 'comment',
        };
    }
}
