<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Facades\SearchFacade;
use App\Modules\SocialMedia\Domain\Enums\Search\SearchTypeEnum;
use App\Modules\SocialMedia\Presentation\Http\Requests\Search\SearchRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Search\SearchResultResource;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;

class SearchController
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Perform unified search across multiple content types
     */
    public function search(SearchRequest $request): JsonResponse
    {
        try {
            $searchType = SearchTypeEnum::fromString($request->validated('type'));

            $results = SearchFacade::search(
                query: $request->validated('query'),
                type: $searchType,
                perPage: $request->validated('per_page', 15),
                page: $request->validated('page', 1)
            );

            return $this->response->paginated(
                SearchResultResource::collection($results),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
             return $this->response->error('Failed to perform search', $e->getMessage());
        }
    }
}
