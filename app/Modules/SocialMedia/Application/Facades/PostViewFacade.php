<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\Services\PostViewService;
use Illuminate\Support\Facades\Facade;

class PostViewFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PostViewService::class;
    }
}
