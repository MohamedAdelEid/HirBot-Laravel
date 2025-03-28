<?php

namespace App\Modules\SocialMedia\Domain\Interfaces\Repositories;

use App\Modules\SocialMedia\Domain\Entities\Post;

interface PostRepositoryInterface
{
    public function create(Post $post): array;
}