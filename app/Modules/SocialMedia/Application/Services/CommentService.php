<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Comment\CreateCommentDTO;
use App\Modules\SocialMedia\Domain\Entities\Comment;
use App\Modules\SocialMedia\Application\Events\NewCommentEvent;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Shared\Facades\FileUploader;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommentService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->repository->setModel(new CommentModel());
    }

    /**
     * Create a new comment
     *
     * @param CreateCommentDTO $dto
     * @return CommentModel
     */
    public function createComment(CreateCommentDTO $dto): CommentModel
    {
        try {
            DB::beginTransaction();

            // Check if post exists
            $postModel = $this->repository->setModel(new PostModel());
            $postModel->findOrFail($dto->postId);
            if (!$postModel) {
                throw new \Exception('Post not found', 404);
            }

            // Handle image upload if provided
            $imagePath = null;
            if ($dto->image) {
                $nameFile = Str::random(10);
                $imagePath = FileUploader::upload(
                    $dto->image,
                    'image',
                    $nameFile,
                    "posts/" . $dto->postId . "/comments",
                    'azure',
                );
            }

            $commentEntity = new Comment(
                $dto->userId,
                $dto->postId,
                $dto->content,
                $imagePath,
                $dto->parentCommentId
            );

            $this->repository->setModel(new CommentModel());
            $comment = $this->repository->create($commentEntity->toArray());

            $comment->load(['user' , 'replies' , 'interactions']);

            // Broadcast the new comment event
            event(new NewCommentEvent($comment));

            DB::commit();

            return $comment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get comments for a post with pagination
     *
     * @param int $postId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPostComments(int $postId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $query = CommentModel::with(['user', 'replies.user', 'interactions'])
            ->where('post_id', $postId)
            ->whereNull('parent_comment_id')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Delete a comment
     *
     * @param int $commentId
     * @param string $userId
     * @return bool
     */
    public function deleteComment(int $commentId, string $userId): bool
    {
        try {
            DB::beginTransaction();

            $comment = $this->repository->find($commentId);
            if (!$comment) {
                throw new \Exception('Comment not found', 404);
            }

            // Check if user owns the comment
            if ($comment->user_id !== $userId) {
                throw new \Exception('Unauthorized to delete this comment', 403);
            }

            // Delete image from storage if exists
            if ($comment->image_path) {
                FileUploader::delete($comment->image_path, 'azure');
            }

            // Delete the comment (cascades to replies)
            $this->repository->delete($commentId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
