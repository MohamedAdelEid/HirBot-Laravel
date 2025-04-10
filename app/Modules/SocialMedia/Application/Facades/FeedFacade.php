<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Feed\GetFeedDTO;
use App\Modules\SocialMedia\Application\Services\FeedService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

class FeedFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FeedService::class;
    }

    public static function getUserFeed(array $filters = []): LengthAwarePaginator
    {
        $dto = GetFeedDTO::fromRequest($filters);
        return static::getFacadeRoot()->getUserFeed(
            $dto->userId,
            [
                'page' => $dto->page,
                'per_page' => $dto->perPage,
                'search' => $dto->search,
                'visibility' => $dto->visibility
            ]
        );
    }
}
