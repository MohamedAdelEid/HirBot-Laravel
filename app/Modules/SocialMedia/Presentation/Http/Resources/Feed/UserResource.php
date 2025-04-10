<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Feed;

use App\Shared\Enums\UserRoleEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->Email,
            'profile_image' => $this->ImagePath,
            'is_company' => UserRoleEnum::COMPANY === $this->role ?? false,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
        ];
    }
}
