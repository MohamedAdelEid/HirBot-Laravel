<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use App\Shared\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Company $company */
        $company = $this->resource;

        return [
            'id' => $company->user->Id,
            'company' => [
                'id' => $company->ID,
                'name' => $company->Name,
                'user_name' => $company->user?->UserName ?? '',
                'email' => $company->user?->Email ?? '',
                'company_type' => $company->CompanyType ?? '',
                'logo' => $company->user?->ImagePath ?? '',
                'created_at' => $company->CreationDate,
            ],
            'connected_employees_count' => $company->connected_employees_count ?? 0,
            'connected_employees' => $company->connected_employees ?? [],
            'relevance_score' => round($company->relevance_score ?? 0, 1),
            'job_openings' => $company->job_openings ?? 0,
            'match_factors' => $company->match_factors ?? [
                'industry' => 'low',
                'skills' => 'low',
                'location' => 'low',
                'network' => 'low',
            ],
            'followers_count' => $this->getFollowersCount($company),
        ];
    }

    /**
     * Get the number of followers for the company
     */
    private function getFollowersCount(Company $company): int
    {
        return \App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel::where('receiver_id', $company->UserID)
            ->where('type', \App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum::FOLLOW)
            ->where('status', \App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum::ACCEPTED)
            ->count();
    }
}
