<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Comment\CreateCommentDTO;
use App\Modules\SocialMedia\Application\Services\CommentService;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use Illuminate\Support\Facades\Facade;

class CommentFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CommentService::class;
    }

    /**
     * Create a new comment
     *
     * @param array $data
     * @param int $postId
     * @return CommentModel
     */
    public static function createComment(array $data, int $postId): CommentModel
    {
        $dto = CreateCommentDTO::fromRequest($data, $postId);
        return static::getFacadeRoot()->createComment($dto);
    }

    /**
     * Update a comment
     * @param array $data
     * @param int $commentId
     * @return CommentModel
     */
    public static function updateComment(array $data, int $commentId): CommentModel
    {
        $dto = CreateCommentDTO::fromRequest($data, $commentId);
        return static::getFacadeRoot()->updateComment($dto);
    }

}
