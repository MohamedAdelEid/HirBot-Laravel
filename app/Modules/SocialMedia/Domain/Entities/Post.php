<?php

namespace App\Modules\SocialMedia\Domain\Entities;

use App\Modules\SocialMedia\Domain\Enums\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\PrivacyCommentsEnum;

class Post
{
    private string $userId;
    private string $content;
    private PrivacyCommentsEnum $privacyComments;
    private PostVisibilityEnum $visibility;

    public function __construct(
        string $userId,
        string $content,
        PrivacyCommentsEnum $privacyComments,
        PostVisibilityEnum $visibility
    ) {
        $this->userId = $userId;
        $this->content = $content;
        $this->privacyComments = $privacyComments;
        $this->visibility = $visibility;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getPrivacyComments(): PrivacyCommentsEnum
    {
        return $this->privacyComments;
    }

    public function getVisibility(): PostVisibilityEnum
    {
        return $this->visibility;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'content' => $this->content,
            'privacy_comments' => $this->privacyComments->value,
            'visibility' => $this->visibility->value,
        ];
    }
}

