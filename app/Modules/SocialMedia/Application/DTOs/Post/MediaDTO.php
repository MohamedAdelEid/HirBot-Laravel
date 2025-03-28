<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

use Illuminate\Http\UploadedFile;

class MediaDTO
{
    public function __construct(
        public readonly string $type,
        public readonly UploadedFile $media
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            media: $data['file']
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'media' => $this->media
        ];
    }
}
