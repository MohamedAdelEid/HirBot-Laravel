<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ConnectedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $currentUserId = Auth::id();

        // Determine which user is the connected user (not the current user)
        $connectedUser = $this->requester_id === $currentUserId
            ? $this->receiver
            : $this->requester;

        return [
            'id' => $this->id,
            'user' => [
                'id' => $connectedUser->Id,
                'name' => $connectedUser->FullName,
                'username' => $connectedUser->UserName,
                'email' => $connectedUser->Email,
                'profile_image' => $connectedUser->ImagePath,
            ],
        ];
    }
}
