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
    private ?array $media;
    private ?array $pollData;

    public function __construct(
        string $userId,
        string $content,
        PrivacyCommentsEnum $privacyComments,
        PostVisibilityEnum $visibility,
        ?array $media = null,
        ?array $pollData = null
    ) {
        $this->userId = $userId;
        $this->content = $content;
        $this->privacyComments = $privacyComments;
        $this->visibility = $visibility;
        $this->media = $media;
        $this->pollData = $pollData;
    }

    // Getters
    public function getUserId(): string { return $this->userId; }
    public function getContent(): string { return $this->content; }
    public function getPrivacyComments(): PrivacyCommentsEnum { return $this->privacyComments; }
    public function getVisibility(): PostVisibilityEnum { return $this->visibility; }
    public function getMedia(): ?array { return $this->media; }
    public function getPollData(): ?array { return $this->pollData; }
}
