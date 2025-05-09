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
            'FullName' => $this->FullName,
            'UserName' => $this->UserName,
            'ProfileImage' => $this->ProfileImage,
        ];
    }
}
