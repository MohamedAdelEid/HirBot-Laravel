<?php

namespace App\Modules\SocialMedia\Domain\Entities;

use Illuminate\Http\UploadedFile;

class PostMedia
{
    private int $postId;
    private string $type;
    private UploadedFile $file;
    private ?string $mediaUrl;

    public function __construct(
        int $postId,
        string $type,
        UploadedFile $file,
        ?string $mediaUrl = null
    ) {
        $this->postId = $postId;
        $this->type = $type;
        $this->file = $file;
        $this->mediaUrl = $mediaUrl;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    public function getMediaUrl(): ?string
    {
        return $this->mediaUrl;
    }

    public function setMediaUrl(string $mediaUrl): void
    {
        $this->mediaUrl = $mediaUrl;
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'type' => $this->type,
            'media_url' => $this->mediaUrl,
        ];
    }
}

