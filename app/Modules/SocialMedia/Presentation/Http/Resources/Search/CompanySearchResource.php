<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Search;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanySearchResource extends JsonResource
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
            'id' => $this->ID,
            'name' => $this->Name ?? '',
            'username' => $this->user->UserName ?? '',
            'type' => $this->CompanyType,
            'profile_image' => $this->user->ImagePath ?? null,
            'location' => [
                'country' => $this->country,
                'governate' => $this->Governate,
            ],
            'jobs_count' => $this->jobs ? $this->jobs->count() : 0,
            'jobs' => $this->when($this->jobs && $this->jobs->count() > 0, function() {
                return $this->jobs->take(3)->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'title' => $job->title,
                        'status' => $job->status,
                        'created_at' => $job->created_at?->toISOString(),
                    ];
                });
            }),
        ];
    }
}
