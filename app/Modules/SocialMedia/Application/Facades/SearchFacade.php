<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\Services\SearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;


class SearchFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchService::class;
    }
}
