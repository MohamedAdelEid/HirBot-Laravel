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
     * @param array<PollDTO>|null $pollData
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $content,
        public readonly PrivacyCommentsEnum $privacyComments,
        public readonly PostVisibilityEnum $visibility,
        public readonly ?array $media = null,
        public readonly ?array $pollData = null
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
            content: $data['content'],
            privacyComments: PrivacyCommentsEnum::from($data['privacy_comments']),
            visibility: PostVisibilityEnum::from($data['visibility']),
            media: !empty($mediaItems) ? $mediaItems : null,
            pollData: !empty($pollItems) ? $pollItems : null
        );
    }
}
