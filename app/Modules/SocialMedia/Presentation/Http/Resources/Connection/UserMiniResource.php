<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;

class UserMiniResource extends JsonResource
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
            'id' => $this->Id,
            'name' => $this->FullName,
            'user_name' => $this->UserName,
            'profile_image' => $this->ImagePath,
        ];
    }
}
