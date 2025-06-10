<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Search;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        if (!$this->resource) {
            return [];
        }

        return [
            'id' => $this->Id,
            'name' => $this->FullName,
            'username' => $this->UserName,
            'email' => $this->Email,
            'profile_image' => $this->ImagePath,
            'portfolio' => $this->when($this->portfolio, [
                'title' => $this->portfolio?->Title,
                'location' => $this->portfolio?->location,
                'portfolio_url' => $this->portfolio?->PortfolioUrl,
            ]),
            'current_experience' => $this->when($this->currentExperience, [
                'title' => $this->currentExperience?->Title,
                'company_name' => $this->currentExperience?->companyName,
                'company' => $this->when($this->currentExperience?->company, [
                    'id' => $this->currentExperience?->company?->ID,
                    'name' => $this->currentExperience?->company?->Name,
                    'type' => $this->currentExperience?->company?->CompanyType,
                ]),
                'start_date' => $this->currentExperience?->Start_Date,
                'is_still' => $this->currentExperience?->IsStill,
            ]),
            'skills' => $this->when($this->skills,
                $this->skills->map(function ($skill) {
                    return [
                        'id' => $skill->ID,
                        'name' => $skill->Name,
                        'rate' => $skill->pivot?->Rate,
                    ];
                })
            ),
            'skills_count' => $this->skills?->count() ?? 0,
            'connections_count' => $this->when(
                method_exists($this, 'connections_count'),
                $this->connections_count ?? 0
            ),
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
        ];
    }
}
