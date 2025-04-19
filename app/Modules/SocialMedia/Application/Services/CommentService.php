<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Comment\CreateCommentDTO;
use App\Modules\SocialMedia\Domain\Entities\Comment;
use App\Modules\SocialMedia\Application\Events\NewCommentEvent;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Shared\Facades\FileUploader;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
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
     * Includes only the first reply for each comment and the total reply count
     *
     * @param int $postId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPostComments(int $postId , $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $query = CommentModel::with($this->getCommentRelations())
            ->withCount('replies')
            ->where('post_id', $postId)
            ->whereNull('parent_comment_id')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get replies for a specific comment with pagination
     *
     * @param int $commentId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getCommentReplies(int $commentId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $query = CommentModel::with($this->getCommentRelations())
            ->withCount('replies')
            ->where('parent_comment_id', $commentId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all replies for a comment thread recursively
     * This is useful for getting the entire conversation thread
     *
     * @param int $rootCommentId
     * @return Collection
     */
    public function getCommentThread(int $rootCommentId): Collection
    {
        // Get the root comment
        $rootComment = CommentModel::with($this->getCommentRelations())
            ->withCount('replies')
            ->findOrFail($rootCommentId);

        // Get all replies in the thread
        $allReplies = $this->getAllRepliesRecursive($rootCommentId);

        // Return as a collection with the root comment first
        return collect([$rootComment])->merge($allReplies);
    }

    /**
     * Helper method to get all replies recursively
     *
     * @param int $commentId
     * @return Collection
     */
    private function getAllRepliesRecursive(int $commentId): Collection
    {
        $replies = CommentModel::with($this->getCommentRelations())
            ->withCount('replies')
            ->where('parent_comment_id', $commentId)
            ->orderBy('created_at', 'asc')
            ->get();

        $allReplies = collect($replies);

        foreach ($replies as $reply) {
            if ($reply->replies_count > 0) {
                $allReplies = $allReplies->merge($this->getAllRepliesRecursive($reply->id));
            }
        }

        return $allReplies;
    }

    /**
     * Helper method to define common relations to load with comments
     *
     * @return array
     */
    private function getCommentRelations(): array
    {
        return [
            'user' => function ($query) {
                $query->with(['company', 'portfolio']);
            },
            'replies' => function ($query) {
                $query->with([
                    'user' => function ($query) {
                        $query->with(['company', 'portfolio']);
                    },
                    'interactions',
                ])->oldest()->limit(1);
            },
            'interactions'
        ];
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
