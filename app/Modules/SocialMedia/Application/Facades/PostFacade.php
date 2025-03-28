<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Application\Services\PostService;
use Illuminate\Support\Facades\Facade;

class PostFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PostService::class;
    }

    public static function createPost(array $data): array
    {
        $dto = CreatePostDTO::fromRequest($data);
        return static::getFacadeRoot()->createPost($dto);
    }
}
