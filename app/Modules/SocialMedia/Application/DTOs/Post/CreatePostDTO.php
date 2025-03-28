<?php

namespace App\Modules\SocialMedia\Application\DTOs\Post;

use App\Modules\SocialMedia\Application\DTOs\Post\PollDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\MediaDTO;
use App\Modules\SocialMedia\Domain\Enums\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\PrivacyCommentsEnum;
use Illuminate\Support\Facades\Auth;

class CreatePostDTO
{
    /**
     * @param string $userId
     * @param string $content
     * @param PrivacyCommentsEnum $privacyComments
     * @param PostVisibilityEnum $visibility
     * @param array<MediaDTO>|null $media
     * @param PollDTO|null $pollData
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $content,
        public readonly PrivacyCommentsEnum $privacyComments,
        public readonly PostVisibilityEnum $visibility,
        public readonly ?array $media = null,
        public readonly ?PollDTO $pollData = null
    ) {
        if ($this->media !== null) {
            foreach ($this->media as $mediaItem) {
                if (!$mediaItem instanceof MediaDTO) {
                    throw new \InvalidArgumentException('Media items must be instances of MediaDTO');
                }
            }
        }
    }

    public static function fromRequest(array $data): self
    {
        $mediaItems = [];
        if (isset($data['media']) && is_array($data['media'])) {
            foreach ($data['media'] as $mediaItem) {
                $mediaItems[] = MediaDTO::fromArray($mediaItem);
            }
        }

        $pollData = isset($data['poll_data'])
            ? PollDTO::fromArray($data['poll_data'])
            : null;

        return new self(
            userId: Auth::user()->Id,
            content: $data['content'],
            privacyComments: PrivacyCommentsEnum::from($data['privacy_comments']),
            visibility: PostVisibilityEnum::from($data['visibility']),
            media: !empty($mediaItems) ? $mediaItems : null,
            pollData: $pollData
        );
    }
}
