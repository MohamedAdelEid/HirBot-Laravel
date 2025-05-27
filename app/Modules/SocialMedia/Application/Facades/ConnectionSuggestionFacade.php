<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\Services\ConnectionSuggestionService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

class ConnectionSuggestionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ConnectionSuggestionService::class;
    }

    /**
     * Get connection suggestions for a user
     *
     * @param string $userId
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public static function getSuggestions(string $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return static::getFacadeRoot()->getSuggestions($userId, $perPage, $filters);
    }
}
