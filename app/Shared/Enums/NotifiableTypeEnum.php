<?php

namespace App\Shared\Enums;

enum NotifiableTypeEnum: int
{
    // Legacy types (keeping for backward compatibility)
    case JOB = 2;
    case INTERVIEW = 3;
    case APPLICATION = 4;

    // Post-related notifications
    case POST_CREATED = 5;
    case POST_COMMENTED = 6;
    case POST_LIKED = 7;
    case POST_SHARED = 8;

    // Connection-related notifications
    case CONNECTION_REQUEST_SENT = 9;
    case CONNECTION_REQUEST_ACCEPTED = 10;
    case CONNECTION_REQUEST_REJECTED = 11;

    // Comment-related notifications
    case COMMENT_LIKED = 12;
    case COMMENT_REPLIED = 13;

    // Poll-related notifications
    case POLL_VOTED = 14;
    case POLL_ENDED = 15;

    /**
     * Get the string representation of the enum value.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::JOB => 'Job',
            self::INTERVIEW => 'Interview',
            self::APPLICATION => 'Application',
            self::POST_CREATED => 'Post Created',
            self::POST_COMMENTED => 'Post Commented',
            self::POST_LIKED => 'Post Liked',
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
     * Get the category of the notification type.
     *
     * @return string
     */
    public function category(): string
    {
        return match($this) {
            self::JOB => 'job',
            self::INTERVIEW => 'interview',
            self::APPLICATION => 'application',
            self::POST_CREATED, self::POST_COMMENTED, self::POST_LIKED, self::POST_SHARED => 'post',
            self::CONNECTION_REQUEST_SENT, self::CONNECTION_REQUEST_ACCEPTED, self::CONNECTION_REQUEST_REJECTED => 'connection',
            self::COMMENT_LIKED, self::COMMENT_REPLIED => 'comment',
            self::POLL_VOTED, self::POLL_ENDED => 'poll',
        };
    }

    /**
     * Get the action of the notification type.
     *
     * @return string
     */
    public function action(): string
    {
        return match($this) {
            self::JOB => 'general',
            self::INTERVIEW => 'general',
            self::APPLICATION => 'general',
            self::POST_CREATED => 'created',
            self::POST_COMMENTED => 'commented',
            self::POST_LIKED => 'liked',
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
     * Get the notifiable entity type for this notification type.
     * This maps to the Notifiable_Type column in the database.
     *
     * @return int
     */
    public function getNotifiableEntityType(): int
    {
        return match($this) {
            self::JOB => 2,
            self::INTERVIEW => 3,
            self::APPLICATION => 4,
            self::POST_CREATED, self::POST_COMMENTED, self::POST_LIKED, self::POST_SHARED => 5,
            self::CONNECTION_REQUEST_SENT, self::CONNECTION_REQUEST_ACCEPTED, self::CONNECTION_REQUEST_REJECTED => 6,
            self::COMMENT_LIKED, self::COMMENT_REPLIED => 7,
            self::POLL_VOTED, self::POLL_ENDED => 8,
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
     * Get all social media related notification types.
     *
     * @return array
     */
    public static function socialMediaTypes(): array
    {
        return [
            self::POST_CREATED->value,
            self::POST_COMMENTED->value,
            self::POST_LIKED->value,
            self::POST_SHARED->value,
            self::CONNECTION_REQUEST_SENT->value,
            self::CONNECTION_REQUEST_ACCEPTED->value,
            self::CONNECTION_REQUEST_REJECTED->value,
            self::COMMENT_LIKED->value,
            self::COMMENT_REPLIED->value,
            self::POLL_VOTED->value,
            self::POLL_ENDED->value,
        ];
    }

    /**
     * Get notification types by category.
     *
     * @param string $category
     * @return array
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), function($case) use ($category) {
            return $case->category() === $category;
        });
    }

    /**
     * Map from integer value to model class.
     *
     * @return array
     */
    public static function getMorphMap(): array
    {
        return [
            // 2 => \App\Models\Job::class,
            // 3 => \App\Models\Interview::class,
            // 4 => \App\Models\Application::class,
            5 => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel::class,
            6 => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel::class,
            7 => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel::class,
            8 => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel::class,
        ];
    }
}
