<?php

namespace App\Shared\Facades;

use Illuminate\Support\Facades\Facade;

class FileUploader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'FileUploader';
    }
}