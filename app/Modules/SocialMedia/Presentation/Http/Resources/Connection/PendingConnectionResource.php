<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PendingConnectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->requester;

        return [
            'id' => $this->id,
            'user' => [
                'id' => $user->Id,
                'name' => $user->FullName,
                'username' => $user->UserName,
                'email' => $user->Email,
                'profile_image' => $user->ProfileImage,
                'current_company' => ( $user->currentExperience && $this->worksForFollowedCompany ) ? [
                    'id' => $user->currentExperience->company->ID,
                    'name' => $user->currentExperience->company->Name,
                    'logo' => $user->currentExperience->company->Logo,
                    'type' => $user->currentExperience->company->CompanyType,
                ] : null,
            ],
            'matching_skills' => SkillResource::collection($this->matchingSkills),
            'mutual_connections' => UserMiniResource::collection($this->mutualConnections),
            'requested_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
