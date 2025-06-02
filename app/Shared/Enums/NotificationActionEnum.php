<?php

namespace App\Shared\Enums;

enum NotificationActionEnum: int
{
    // Post actions
    case POST_CREATED = 101;
    case POST_LIKED = 102;
    case POST_COMMENTED = 103;
    case POST_SHARED = 104;

    // Connection actions
    case CONNECTION_REQUEST_SENT = 201;
    case CONNECTION_REQUEST_ACCEPTED = 202;
    case CONNECTION_REQUEST_REJECTED = 203;

    // Comment actions
    case COMMENT_LIKED = 301;
    case COMMENT_REPLIED = 302;

    // Poll actions
    case POLL_VOTED = 401;
    case POLL_ENDED = 402;

    /**
     * Get the string representation of the enum value.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::POST_CREATED => 'Post Created',
            self::POST_LIKED => 'Post Liked',
            self::POST_COMMENTED => 'Post Commented',
            self::POST_SHARED => 'Post Shared',
            self::CONNECTION_REQUEST_SENT => 'Connection Request Sent',
            self::CONNECTION_REQUEST_ACCEPTED => 'Connection Request Accepted',
            self::CONNECTION_REQUEST_REJECTED => 'Connection Request Rejected',
            self::COMMENT_LIKED => 'Comment Liked',
            self::COMMENT_REPLIED => 'Comment Replied',
            self::POLL_VOTED => 'Poll Voted',
            self::POLL_ENDED => 'Poll Ended',
        };
    }

    /**
     * Get the action name.
     *
     * @return string
     */
    public function action(): string
    {
        return match($this) {
            self::POST_CREATED => 'created',
            self::POST_LIKED => 'liked',
            self::POST_COMMENTED => 'commented',
            self::POST_SHARED => 'shared',
            self::CONNECTION_REQUEST_SENT => 'request_sent',
            self::CONNECTION_REQUEST_ACCEPTED => 'request_accepted',
            self::CONNECTION_REQUEST_REJECTED => 'request_rejected',
            self::COMMENT_LIKED => 'liked',
            self::COMMENT_REPLIED => 'replied',
            self::POLL_VOTED => 'voted',
            self::POLL_ENDED => 'ended',
        };
    }

    /**
     * Get the category this action belongs to.
     *
     * @return NotifiableTypeEnum
     */
    public function getCategory(): NotifiableTypeEnum
    {
        return match($this) {
            self::POST_CREATED, self::POST_LIKED, self::POST_COMMENTED, self::POST_SHARED => NotifiableTypeEnum::POST,
            self::CONNECTION_REQUEST_SENT, self::CONNECTION_REQUEST_ACCEPTED, self::CONNECTION_REQUEST_REJECTED => NotifiableTypeEnum::CONNECTION,
            self::COMMENT_LIKED, self::COMMENT_REPLIED => NotifiableTypeEnum::COMMENT,
            self::POLL_VOTED, self::POLL_ENDED => NotifiableTypeEnum::POLL,
        };
    }

    /**
     * Get all values as an array.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get actions by category.
     *
     * @param NotifiableTypeEnum $category
     * @return array
     */
    public static function getByCategory(NotifiableTypeEnum $category): array
    {
        return array_filter(self::cases(), function($case) use ($category) {
            return $case->getCategory() === $category;
        });
    }
}
