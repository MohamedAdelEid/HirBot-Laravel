<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Post;

use Illuminate\Http\Resources\Json\JsonResource;

class PollOptionResource extends JsonResource
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
            'content' => $this->content,
            'vote_count' => $this->vote_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

