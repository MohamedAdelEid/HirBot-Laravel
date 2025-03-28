<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\SocialMedia\Domain\Entities\Post;
use App\Modules\SocialMedia\Domain\Enums\PostMediaTypeEnum;
use App\Modules\SocialMedia\Domain\Interfaces\Repositories\PostRepositoryInterface;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Shared\Facades\FileUploader;

class PostRepository implements PostRepositoryInterface
{
    public function create(Post $post): array
    {
        try {
            DB::beginTransaction();

            $createdPost = PostModel::create([
                'user_id' => $post->getUserId(),
                'content' => $post->getContent(),
                'privacy_comments' => $post->getPrivacyComments(),
                'visibility' => $post->getVisibility(),
            ]);
            // dd($post->getMedia());
            if ($post->getMedia()) {
                foreach ($post->getMedia() as $mediaDTO) {
                    $nameFile = Str::random(10);
                    $path = FileUploader::upload(
                        $mediaDTO->media,
                        $mediaDTO->type,
                        $nameFile,
                        'files' ,
                        'azure',
                    );

                    $createdPost->media()->create([
                        'type' => PostMediaTypeEnum::tryFrom($mediaDTO->type)->value,
                        'media_url' => $path
                    ]);
                }
            }

            if ($post->getPollData()) {
                $poll = $createdPost->poll()->create([
                    'question' => $post->getPollData()['question']
                ]);

                foreach ($post->getPollData()['options'] as $optionText) {
                    $poll->options()->create([
                        'content' => $optionText,
                        'vote_count' => 0
                    ]);
                }
            }

            DB::commit();

            return $createdPost->fresh()->load(['media', 'poll.options'])->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
