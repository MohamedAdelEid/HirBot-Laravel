<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

use App\Modules\SocialMedia\Domain\Enums\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\PrivacyCommentsEnum;
use Illuminate\Support\Facades\Auth;

class UpdatePostDTO
{
    /**
     * @param string $userId
     * @param string|null $content
     * @param PrivacyCommentsEnum|null $privacyComments
     * @param PostVisibilityEnum|null $visibility
     * @param array<MediaDTO>|null $media
     * @param array<int>|null $mediaToDelete
     * @param array<PollDTO>|null $pollData
     * @param array<int>|null $optionsToDelete
     */
    public function __construct(
        public readonly string $userId,
        public readonly ?string $content = null,
        public readonly ?PrivacyCommentsEnum $privacyComments = null,
        public readonly ?PostVisibilityEnum $visibility = null,
        public readonly ?array $media = null,
        public readonly ?array $mediaToDelete = null,
        public readonly ?array $pollData = null,
        public readonly ?array $optionsToDelete = null
    ) {}

    public static function fromRequest(array $data): self
    {
        $mediaItems = [];
        if (isset($data['media'])) {
            foreach ($data['media'] as $mediaItem) {
                $mediaItems[] = MediaDTO::fromArray($mediaItem);
            }
        }

        $pollItems = [];
        if (isset($data['poll_data'])) {
            $pollItems[] = PollDTO::fromArray($data['poll_data']);
        }

        return new self(
            userId: Auth::user()->Id,
            content: $data['content'] ?? null,
            privacyComments: isset($data['privacy_comments'])
                ? PrivacyCommentsEnum::from($data['privacy_comments'])
                : null,
            visibility: isset($data['visibility'])
                ? PostVisibilityEnum::from($data['visibility'])
                : null,
            media: !empty($mediaItems) ? $mediaItems : null,
            mediaToDelete: $data['media_to_delete'] ?? null,
            pollData: !empty($pollItems) ? $pollItems : null,
            optionsToDelete: $data['options_to_delete'] ?? null
        );
    }
}

