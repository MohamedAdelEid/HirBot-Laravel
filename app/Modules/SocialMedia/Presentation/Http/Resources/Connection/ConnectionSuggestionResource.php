<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionSuggestionResource extends JsonResource
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
            'username' => $this->UserName,
            'email' => $this->Email,
            'profile_image' => $this->ImagePath,
            'title' => $this->portfolio ? $this->portfolio->Title : null,
            'address' => $this->portfolio ? $this->portfolio->location   : null,
            'current_company' => $this->when($this->currentExperience && $this->currentExperience->company, function () {
                return [
                    'id' => $this->currentExperience->company->ID,
                    'name' => $this->currentExperience->company->Name,
                    'logo' => $this->currentExperience->company->Logo,
                    'type' => $this->currentExperience->company->CompanyType,
                ];
            }),
            'current_position' => $this->when($this->currentExperience, function () {
                return $this->currentExperience->Title;
            }),
            'similarity_score' => round($this->similarity_score * 100), // Convert to percentage
            'similarity_factors' => [
                'title' => round($this->similarity_factors['title'] * 100),
                'location' => round($this->similarity_factors['location'] * 100),
                'mutual_connections' => round($this->similarity_factors['mutual_connections'] * 100),
                'skills' => round($this->similarity_factors['skills'] * 100),
                'education' => round($this->similarity_factors['education'] * 100),
                'experience' => round($this->similarity_factors['experience'] * 100),
            ],
            'mutual_connections' => [
                'count' => $this->mutual_connections->count(),
                'preview' => UserMiniResource::collection($this->mutual_connections->take(3)),
            ],
            'skills' => $this->when($this->skills, function () {
                return $this->skills->take(5)->map(function ($skill) {
                    return [
                        'id' => $skill->ID,
                        'name' => $skill->Name,
                    ];
                });
            }),
        ];
    }
}
