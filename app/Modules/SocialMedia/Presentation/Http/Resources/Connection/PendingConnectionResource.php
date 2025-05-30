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
        // dump($user->portfolio->Title);
        return [
            'id' => $this->id,
            'user' => [
                'id' => $user->Id,
                'name' => $user->FullName,
                'username' => $user->UserName,
                'email' => $user->Email,
                'profile_image' => $user->ImagePath,
                'title' => $user->relationLoaded('portfolio') && $user->portfolio ? $user->portfolio->Title : null,
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
