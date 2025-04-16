<?php

namespace App\Modules\SocialMedia\Domain\Entities;

use Illuminate\Http\UploadedFile;

class PostMedia
{
    private int $postId;
    private string $type;
    private UploadedFile $file;
    private ?string $mediaUrl;
    private ?string $posterUrl;

    public function __construct(
        int $postId,
        string $type,
        UploadedFile $file,
        ?string $mediaUrl = null,
        ?string $posterUrl = null
    ) {
        $this->postId = $postId;
        $this->type = $type;
        $this->file = $file;
        $this->mediaUrl = $mediaUrl;
        $this->posterUrl = $posterUrl;
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

    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }
    public function setPosterUrl(string $posterUrl): void
    {
        $this->posterUrl = $posterUrl;
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'type' => $this->type,
            'media_url' => $this->mediaUrl,
            'poster_url' => $this->posterUrl,
        ];
    }
}

