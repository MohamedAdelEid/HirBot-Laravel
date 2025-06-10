<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Search;

use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\PostResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'data' => $this->transformData($request),
            'relevance_score' => $this->relevance_score,
            'match_type' => $this->match_type,
            'status' => $this->getStatusData(),
        ];
    }

    /**
     * Transform data based on type
     */
    private function transformData($request): array
    {
        return match($this->type) {
            'user' => (new UserSearchResource($this->data))->toArray($request),
            'company' => (new CompanySearchResource($this->data))->toArray($request),
            'post' => (new PostResource($this->data))->toArray($request),
            default => []
        };
    }

    /**
     * Get status data based on type
     */
    private function getStatusData(): array
    {
        $status = [];

        if (isset($this->isConnected)) {
            $status['is_connected'] = $this->isConnected;
        }

        if (isset($this->isFollowed)) {
            $status['is_followed'] = $this->isFollowed;
        }

        return $status;
    }
}
