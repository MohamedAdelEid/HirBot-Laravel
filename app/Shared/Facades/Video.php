<?php

namespace App\Shared\Facades;

use Illuminate\Support\Facades\Facade;

class Video extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Video';
    }
}
