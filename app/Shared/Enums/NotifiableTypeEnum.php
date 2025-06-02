<?php

namespace App\Shared\Enums;

enum NotifiableTypeEnum: int
{
    // Legacy types (keeping for backward compatibility)
    case JOB = 2;
    case INTERVIEW = 3;
    case APPLICATION = 4;

    // Social Media types
    case POST = 5;
    case CONNECTION = 6;
    case COMMENT = 7;
    case POLL = 8;

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
            self::POST => 'Post',
            self::CONNECTION => 'Connection',
            self::COMMENT => 'Comment',
            self::POLL => 'Poll',
        };
    }

    /**
     * Get the category name of the notification type.
     *
     * @return string
     */
    public function category(): string
    {
        return match($this) {
            self::JOB => 'job',
            self::INTERVIEW => 'interview',
            self::APPLICATION => 'application',
            self::POST => 'post',
            self::CONNECTION => 'connection',
            self::COMMENT => 'comment',
            self::POLL => 'poll',
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
            self::POST->value,
            self::CONNECTION->value,
            self::COMMENT->value,
            self::POLL->value,
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
     * Get enum case from category name.
     *
     * @param string $category
     * @return self|null
     */
    public static function fromCategory(string $category): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->category() === $category) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Get all social media categories.
     *
     * @return array
     */
    public static function socialMediaCategories(): array
    {
        return [
            self ::POST->category(),
            self::CONNECTION->category(),
            self::COMMENT->category(),
            self::POLL->category()
        ];
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
            self::POST->value => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel::class,
            self::CONNECTION->value => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel::class,
            self::COMMENT->value => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel::class,
            self::POLL->value => \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel::class,
        ];
    }
}
