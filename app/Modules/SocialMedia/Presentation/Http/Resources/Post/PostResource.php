<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Post;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'privacy_comments' => $this->privacy_comments,
            'visibility' => $this->visibility,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'poll' => $this->whenLoaded('poll', function () {
                return [
                    'id' => $this->poll->id,
                    'question' => $this->poll->question,
                    'options' => PollOptionResource::collection($this->poll->options)
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

