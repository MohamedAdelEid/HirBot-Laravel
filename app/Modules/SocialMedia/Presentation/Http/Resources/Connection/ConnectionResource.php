<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
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
            'requester' => new UserResource($this->whenLoaded('requester')),
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'status' => $this->status,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
