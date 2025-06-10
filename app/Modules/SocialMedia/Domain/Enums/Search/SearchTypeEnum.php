<?php

namespace App\Modules\SocialMedia\Domain\Enums\Search;

enum SearchTypeEnum: string
{
    case USER = 'user';
    case COMPANY = 'company';
    case POST = 'post';
    case HASHTAG = 'hashtag';
    case JOB = 'job';

    /**
     * Get all available search types
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get search type labels for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::USER => 'Users',
            self::COMPANY => 'Companies',
            self::POST => 'Posts',
            self::HASHTAG => 'Hashtags',
            self::JOB => 'Jobs',
        };
    }

    /**
     * Get search type description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::USER => 'Search for users and professionals',
            self::COMPANY => 'Search for companies and organizations',
            self::POST => 'Search for posts and content',
            self::HASHTAG => 'Search for hashtags and topics',
            self::JOB => 'Search for job opportunities',
        };
    }

    /**
     * Check if search type is valid
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::getValues());
    }

    /**
     * Get enum from string value
     */
    public static function fromString(string $type): ?self
    {
        return self::tryFrom($type);
    }
}
