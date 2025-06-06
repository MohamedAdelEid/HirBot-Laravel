<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\Services\CompanySuggestionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LengthAwarePaginator getSuggestions(int $page = 1, int $perPage = 15, ?string $industry = null, ?string $location = null, ?float $minScore = null)
 */
class CompanySuggestionFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return CompanySuggestionService::class;
    }
}
