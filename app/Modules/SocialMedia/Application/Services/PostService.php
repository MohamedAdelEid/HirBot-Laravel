<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\UpdatePostDTO;
use App\Modules\SocialMedia\Application\Events\NewPostEvent;
use App\Modules\SocialMedia\Domain\Entities\Post;
use App\Modules\SocialMedia\Domain\Entities\PostMedia;
use App\Modules\SocialMedia\Domain\Entities\Poll;
use App\Modules\SocialMedia\Domain\Entities\PollOption;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollOptionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Shared\Repositories\BaseRepository;
use App\Shared\Facades\Video;
use App\Shared\Facades\FileUploader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;


class PostService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all posts with pagination
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllPosts(array $filters = []): LengthAwarePaginator
    {
        $query = PostModel::with(['media', 'poll.options']);

        // Apply pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Create a new post
     *
     * @param CreatePostDTO $dto
     * @return array
     */
    public function createPost(CreatePostDTO $dto): array
    {
        try {
            DB::beginTransaction();

            // Create post entity
            $this->repository->setModel(new PostModel());

            $postEntity = new Post(
                $dto->userId,
                $dto->content,
                $dto->privacyComments,
                $dto->visibility
            );

            $postModel = $this->repository->create($postEntity->toArray());

            // Handle media uploads if provided
            if ($dto->media) {
                $this->repository->setModel(new PostMediaModel());

                foreach ($dto->media as $mediaDTO) {
                    $mediaEntity = new PostMedia(
                        $postModel->id,
                        $mediaDTO->type,
                        $mediaDTO->media
                    );

                    $nameFile = Str::random(10);
                    $path = FileUploader::upload(
                        $mediaEntity->getFile(),
                        $mediaEntity->getType(),
                        $nameFile,
                        "posts/{$mediaEntity->getPostId()}",
                        'azure',
                    );

                    $mediaEntity->setMediaUrl($path);

                    // Extract poster for videos
                    if ($mediaDTO->type === 'video') {
                        // Try to extract poster using FFMpeg
                        try {
                            $posterPath = Video::extractPoster(
                                $path,
                                'azure',
                                "posts/{$mediaEntity->getPostId()}/posters"
                            );

                            if (!$posterPath) {
                                // Fallback to simpler method if FFMpeg fails
                                $posterPath = Video::extractPosterFallback(
                                    $mediaEntity->getFile(),
                                    'azure',
                                    "posts/{$mediaEntity->getPostId()}/posters"
                                );
                            }

                            if ($posterPath) {
                                $mediaEntity->setPosterUrl($posterPath);
                            }
                        } catch (\Exception $e) {
                            // Log error but continue without poster
                            \Log::error('Failed to extract video poster: ' . $e->getMessage());
                        }
                    }

                    $this->repository->create($mediaEntity->toArray());
                }
            }

            // Handle poll creation if provided
            if ($dto->pollData) {
                $this->repository->setModel(new PollModel());

                foreach ($dto->pollData as $pollDTO) {

                    $pollOptions = [];
                    foreach ($pollDTO->options as $option) {
                        $pollOptions[] = new PollOption(
                            $option->content,
                            $option->voteCount
                        );
                    }

                    $pollEntity = new Poll(
                        $postModel->id,
                        $pollDTO->question,
                        $pollOptions
                    );

                    $poll = $this->repository->create($pollEntity->toArray());

                    foreach ($pollEntity->getOptions() as $optionEntity) {
                        $poll->options()->create($optionEntity->toArray());
                    }
                }
            }

            // Dispatch event to create notifications for connected users
            event(new NewPostEvent($postModel));

            DB::commit();

            return $postModel->fresh()->load(['media', 'poll.options'])->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get a post by ID
     *
     * @param int $postId
     * @return PostModel|null
     */
    public function getPost(int $postId): ?PostModel
    {
        $this->repository->setModel(new PostModel());

        $post = $this->repository->findOrFail($postId);

        $post->load(['media', 'poll.options']);

        return $post;
    }

    /**
     * Update a post
     *
     * @param PostModel $postModel
     * @param UpdatePostDTO $dto
     * @return array
     */
    public function updatePost(PostModel $postModel, UpdatePostDTO $dto): array
    {
        try {
            DB::beginTransaction();

            // Update post basic information if provided
            $updateData = [];
            if ($dto->content !== null) {
                $updateData['content'] = $dto->content;
            }
            if ($dto->privacyComments !== null) {
                $updateData['privacy_comments'] = $dto->privacyComments->value;
            }
            if ($dto->visibility !== null) {
                $updateData['visibility'] = $dto->visibility->value;
            }

            if (!empty($updateData)) {
                $this->repository->setModel(new PostModel());
                $this->repository->update($postModel->id, $updateData);
            }

            // Handle media deletion if specified
            if ($dto->mediaToDelete) {
                $this->repository->setModel(new PostMediaModel());
                $mediaToDelete = PostMediaModel::whereIn('id', $dto->mediaToDelete)->get();

                foreach ($mediaToDelete as $media) {
                    // Delete the file from storage
                    FileUploader::delete($media->media_url, 'azure');

                    // Delete the poster if it exists
                    if ($media->poster_url) {
                        FileUploader::delete($media->poster_url, 'azure');
                    }

                    // Delete the media record
                    $this->repository->delete($media->id);
                }
            }

            // Handle new media uploads
            if ($dto->media) {
                $this->repository->setModel(new PostMediaModel());

                foreach ($dto->media as $mediaDTO) {
                    $mediaEntity = new PostMedia(
                        $postModel->id,
                        $mediaDTO->type,
                        $mediaDTO->media
                    );

                    $nameFile = Str::random(10);
                    $path = FileUploader::upload(
                        $mediaEntity->getFile(),
                        $mediaEntity->getType(),
                        $nameFile,
                        "posts/{$mediaEntity->getPostId()}",
                        'azure',
                    );

                    $mediaEntity->setMediaUrl($path);

                    // Extract poster for videos
                    if ($mediaDTO->type === 'video') {
                        // Try to extract poster using FFMpeg
                        try {
                            $posterPath = Video::extractPoster(
                                $path,
                                'azure',
                                "posts/{$mediaEntity->getPostId()}/posters"
                            );

                            if (!$posterPath) {
                                // Fallback to simpler method if FFMpeg fails
                                $posterPath = Video::extractPosterFallback(
                                    $mediaEntity->getFile(),
                                    'azure',
                                    "posts/{$mediaEntity->getPostId()}/posters"
                                );
                            }

                            if ($posterPath) {
                                $mediaEntity->setPosterUrl($posterPath);
                            }
                        } catch (\Exception $e) {
                            // Log error but continue without poster
                            \Log::error('Failed to extract video poster: ' . $e->getMessage());
                        }
                    }

                    $this->repository->create($mediaEntity->toArray());
                }
            }

            // Handle poll updates
            if ($dto->pollData) {
                $pollDTO = $dto->pollData[0];
                $existingPoll = PollModel::where('post_id', $postModel->id)->first();

                if ($existingPoll) {

                    if(!empty($pollDTO->question)) {
                        $this->repository->setModel(new PollModel());
                        $this->repository->update($existingPoll->id, [
                            'question' => $pollDTO->question
                        ]);
                    }

                    foreach ($pollDTO->options as $optionDTO) {
                        if ($optionDTO->id) {
                            // Update existing option
                            $this->repository->setModel(new PollOptionModel());
                            $this->repository->update($optionDTO->id, [
                                'content' => $optionDTO->content
                            ]);
                        } else {
                            // Add new option
                            $existingPoll->options()->create([
                                'content' => $optionDTO->content,
                                'vote_count' => 0
                            ]);
                        }
                    }
                }
            }

            // Handle option deletions
            if ($dto->optionsToDelete) {
                $this->repository->setModel(new PollOptionModel());
                foreach ($dto->optionsToDelete as $optionId) {
                    $this->repository->delete($optionId);
                }
            }

            DB::commit();

            return $postModel->fresh()->load(['media', 'poll.options'])->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Soft delete a post
     *
     * @param int $postId
     * @return bool
     */
    public function deletePost(int $postId): bool
    {
        try {
            DB::beginTransaction();

            $this->repository->setModel(new PostModel());

            $post = $this->repository->findOrFail($postId);

            if ($post->user_id !== Auth::id()) {
                throw new \Exception('You do not have permission to delete this post.' , 403);
            }

            // Soft delete the post
            $this->repository->delete($postId);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Force delete a post and its related data
     *
     * @param int $postId
     * @return bool
     */
    public function forceDeletePost(int $postId): bool
    {
        try {
            DB::beginTransaction();

            // Find the post
            $this->repository->setModel(new PostModel());
            $post = $this->repository->findOrFail($postId);

            if ($post->user_id !== Auth::id()) {
                throw new \Exception('You do not have permission to delete this post.' , 403);
            }

            // Delete media files from storage
            foreach ($post->media as $media) {
                FileUploader::delete($media->media_url, 'azure');

                // Delete poster if it exists
                if ($media->poster_url) {
                    FileUploader::delete($media->poster_url, 'azure');
                }
            }

            // Delete poll options
            if ($post->poll) {
                $this->repository->setModel(new PollOptionModel());
                foreach ($post->poll->options as $option) {
                    $this->repository->delete($option->id);
                }

                // Delete poll
                $this->repository->setModel(new PollModel());
                $this->repository->delete($post->poll->id);
            }

            // Delete media records
            $this->repository->setModel(new PostMediaModel());
            foreach ($post->media as $media) {
                $this->repository->delete($media->id);
            }

            // Force delete the post
            $this->repository->setModel(new PostModel());
            $post->forceDelete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all comments for a post
     *
     * @param int $postId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPostComments(int $postId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $query = CommentModel::with(['user:Id,name,profile_image'])
            ->where('post_id', $postId)
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

}
