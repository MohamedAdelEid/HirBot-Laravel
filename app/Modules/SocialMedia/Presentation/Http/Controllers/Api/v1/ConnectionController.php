<?php

namespace App\Modules\SocialMedia\Presentation\Http\Controllers\Api\v1;

use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionExistsException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionRequestAlreadyProcessedException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\SelfConnectionException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\UnauthorizedConnectionRequestException;
use App\Modules\SocialMedia\Application\Facades\CompanySuggestionFacade;
use App\Modules\SocialMedia\Application\Facades\ConnectionFacade;
use App\Modules\SocialMedia\Application\Facades\ConnectionSuggestionFacade;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionRoleEnum;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\CompanySearchRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\CompanySuggestionRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\ConnectionSearchRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\ConnectionSuggestionRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\SendConnectionRequest;
use App\Modules\SocialMedia\Presentation\Http\Requests\Connection\ProcessConnectionRequest;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\CompanySuggestionResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\ConnectedUserResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\ConnectionResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\ConnectionSuggestionResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\FollowedCompanyResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\PendingConnectionResource;
use App\Shared\Controllers\Controller;
use App\Shared\Interfaces\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    /**
     * Send a connection request
     *
     * @param SendConnectionRequest $request
     * @return JsonResponse
     */
    public function sendRequest(SendConnectionRequest $request): JsonResponse
    {
        try {
            $connection = ConnectionFacade::sendConnectionRequest($request->validated());

            return $this->response->success(
                new ConnectionResource($connection),
                'Connection request sent successfully'
            );
        } catch (ConnectionExistsException $e) {
            return $this->response->error('Connection exists', $e->getMessage(), $e->getCode());
        } catch (SelfConnectionException $e) {
            return $this->response->error('Invalid connection', $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->response->error('Error sending connection request', $e->getMessage());
        }
    }

    /**
     * Accept a connection request
     *
     * @param ProcessConnectionRequest $request
     * @return JsonResponse
     */
    public function acceptRequest(ProcessConnectionRequest $request): JsonResponse
    {
        try {
            $connection = ConnectionFacade::acceptConnectionRequest($request->validated());

            return $this->response->success(
                new ConnectionResource($connection),
                'Connection request accepted successfully'
            );
        } catch (UnauthorizedConnectionRequestException $e) {
            return $this->response->error('Unauthorized connection request', $e->getMessage(), $e->getCode());
        } catch (ConnectionRequestAlreadyProcessedException $e) {
            return $this->response->error('Connection request already processed', $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->response->error('Error accepting connection request', $e->getMessage());
        }
    }

    /**
     * Reject a connection request
     *
     * @param ProcessConnectionRequest $request
     * @return JsonResponse
     */
    public function rejectRequest(ProcessConnectionRequest $request): JsonResponse
    {
        try {
            $connection = ConnectionFacade::rejectConnectionRequest($request->validated());

            return $this->response->success(
                null,
                'Connection request rejected successfully'
            );
        } catch (UnauthorizedConnectionRequestException $e) {
            return $this->response->error('Unauthorized connection request', $e->getMessage(), $e->getCode());
        } catch (ConnectionRequestAlreadyProcessedException $e) {
            return $this->response->error('Connection request already processed', $e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->response->error('Error rejecting connection request', $e->getMessage());
        }
    }

    /**
     * Get pending connection requests
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        try {
            $connections = ConnectionFacade::getPendingConnectionRequests(Auth::user()->Id);

            return $this->response->success(
                ConnectionResource::collection($connections),
                'Pending connection requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving pending connection requests', $e->getMessage());
        }
    }

    /**
     * Get all connections for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConnections(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $type = $request->query('type');

            $connections = ConnectionFacade::getUserConnections(
                auth()->id(),
                $status,
                $type
            );

            return $this->response->success(
                ConnectionResource::collection($connections),
                'Connections retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving connections', $e->getMessage());
        }
    }

    /**
     * Get all connected users with pagination and search
     *
     * @param ConnectionSearchRequest $request
     * @return JsonResponse
     */
    public function getConnectedUsers(ConnectionSearchRequest $request): JsonResponse
    {
        try {
            $userId = Auth::user()->Id;
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 15);

            $connections = ConnectionFacade::getConnectedUsersWithPagination($userId, $search, $perPage);

            return $this->response->paginated(
                ConnectedUserResource::collection($connections),
                'Connected users retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving connected users', $e->getMessage() );
        }
    }

    /**
     * Get all followed companies with pagination and search
     *
     * @param CompanySearchRequest $request
     * @return JsonResponse
     */
    public function getFollowedCompanies(CompanySearchRequest $request): JsonResponse
    {
        try {
            $userId = Auth::user()->Id;
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 15);

            $companies = ConnectionFacade::getFollowedCompanies($userId, $search, $perPage);

            return $this->response->paginated(
                FollowedCompanyResource::collection($companies),
                'Followed companies retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving followed companies', $e->getMessage());
        }
    }

    /**
     * Get all pending connection requests with detailed information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPendingConnectionsDetailed(Request $request): JsonResponse
    {
        try {
            $userId = Auth::user()->Id;
            $perPage = $request->input('per_page', 15);

            $pendingConnections = ConnectionFacade::getPendingConnectionsDetailed($userId,ConnectionRoleEnum::RECEIVER , $perPage);

            return $this->response->paginated(
                PendingConnectionResource::collection($pendingConnections),
                'Pending connection receives retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving pending connection receives', $e->getMessage());
        }
    }

    public function getPendingRequestConnectionsDetailed(Request $request): JsonResponse
    {
        try {
            $userId = Auth::user()->Id;
            $perPage = $request->input('per_page', 15);

            $pendingRequester = ConnectionFacade::getPendingConnectionsDetailed($userId,ConnectionRoleEnum::REQUESTER , $perPage);

            $transformed = $pendingRequester->getCollection()->map(function ($item) {
                return new PendingConnectionResource($item, ConnectionRoleEnum::REQUESTER->value);
            });

            $pendingRequester->setCollection($transformed);

            return $this->response->paginated(
                $pendingRequester,
                'Pending connection requests retrieved successfully'
                );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving pending connection requests', $e->getMessage());
        }
    }

    /**
     * Get connection suggestions for the authenticated user
     *
     * @param ConnectionSuggestionRequest $request
     * @return JsonResponse
     */
    public function getSuggestions(ConnectionSuggestionRequest $request): JsonResponse
    {
        try {
            $userId = Auth::user()->Id;
            $perPage = $request->input('per_page', 15);

            $suggestions = ConnectionSuggestionFacade::getSuggestions($userId, $perPage);

            return $this->response->paginated(
                ConnectionSuggestionResource::collection($suggestions),
                'Connection suggestions retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->response->error('Error retrieving connection suggestions', $e->getMessage());
        }
    }

        /**
     * Get company suggestions for the authenticated user
     */
    public function getSuggestionsCompanies(CompanySuggestionRequest $request): JsonResponse
    {
        try {
            $suggestions = CompanySuggestionFacade::getSuggestions(
                page: $request->input('page', 1),
                perPage: min($request->input('per_page', 15), 50),
                industry: $request->input('industry'),
                location: $request->input('location'),
                minScore: $request->input('min_score')
            );

            return $this->response->paginated(
                CompanySuggestionResource::collection($suggestions),
                'Company suggestions retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->response->error('Error retrieving company suggestions', $e->getMessage());
        }
    }
}
