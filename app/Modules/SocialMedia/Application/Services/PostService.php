<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\CreatePostDTO;
use App\Modules\SocialMedia\Application\DTOs\Post\UpdatePostDTO;
use App\Modules\SocialMedia\Domain\Entities\Post;
use App\Modules\SocialMedia\Domain\Entities\PostMedia;
use App\Modules\SocialMedia\Domain\Entities\Poll;
use App\Modules\SocialMedia\Domain\Entities\PollOption;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollOptionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Shared\Repositories\BaseRepository;
use App\Shared\Facades\FileUploader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

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

            DB::commit();

            return $postModel->fresh()->load(['media', 'poll.options'])->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

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
}

