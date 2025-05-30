<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Connection;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowedCompanyResource extends JsonResource
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
            'id' => $this->id,
            'company' => [
                'id' => $this->receiver_id,
                'name' => $this->receiver->FullName,
                'user_name' => $this->receiver->UserName,
                'email' => $this->receiver->Email,
                'company_type' => $this->receiver->company->CompanyType ?? null,
                'logo' => $this->receiver->ImagePath,
                'created_at' => $this->receiver->created_at,
                'updated_at' => $this->receiver->updated_at,
            ],
            'connected_employees_count' => $this->connected_employees_count ?? 0,
            'connected_employees' => UserMiniResource::collection($this->connectedEmployees ?? []),
            'followed_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
