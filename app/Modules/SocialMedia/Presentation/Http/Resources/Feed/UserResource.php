<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Feed;

use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
        $currentUserId = Auth::id();
        $userData = [
            'id' => $this->Id,
            'name' => $this->FullName,
            'email' => $this->Email,
            'profile_image' => $this->ImagePath,
            'username' => $this->UserName,
            'is_company' => $this->is_company ?? false,
        ];

        // Add title based on user type
        if ($this->is_company) {
            $userData['title'] = $this->whenLoaded('company', function() {
                return $this->company->CompanyType ?? null;
            });

            // Add is_followed for company users
            $userData['is_followed'] = ConnectionFacade::areUsersConnected($currentUserId, $this->id);
        } else {
            $userData['title'] = $this->whenLoaded('portfolio', function() {
                return $this->portfolio->Title ?? null;
            });

            // Add connection_status for non-company users
            if ($this->id !== $currentUserId) {
                $connections = collect();

                if ($this->relationLoaded('requestedConnections')) {
                    $connections = $connections->merge($this->requestedConnections);
                }

                if ($this->relationLoaded('receivedConnections')) {
                    $connections = $connections->merge($this->receivedConnections);
                }

                $connection = $connections->first(function ($conn) use ($currentUserId) {
                    return ($conn->requester_id == $currentUserId && $conn->receiver_id == $this->id) ||
                           ($conn->receiver_id == $currentUserId && $conn->requester_id == $this->id);
                });

                $userData['connection_status'] = $connection && $connection->status
                    ? $connection->status->value
                    : null;
            }
        }

        return $userData;
    }
}
