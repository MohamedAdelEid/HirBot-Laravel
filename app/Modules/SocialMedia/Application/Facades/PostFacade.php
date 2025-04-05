<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\UpdatePostDTO;
use App\Modules\SocialMedia\Application\Services\PostService;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
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

    public static function updatePost(PostModel $post, array $data): array
    {
        $dto = UpdatePostDTO::fromRequest($data);
        return static::getFacadeRoot()->updatePost($post, $dto);
    }
}
