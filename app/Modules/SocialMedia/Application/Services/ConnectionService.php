<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\Events\ConnectionRequestAcceptedEvent;
use App\Modules\SocialMedia\Application\Events\ConnectionRequestRejectedEvent;
use App\Modules\SocialMedia\Application\Events\ConnectionRequestSentEvent;
use App\Modules\SocialMedia\Application\Events\NewConnectionRequest;
use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionExistsException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionRequestAlreadyProcessedException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\SelfConnectionException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\UnauthorizedConnectionRequestException;
use App\Modules\SocialMedia\Domain\Entities\Connection;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionRoleEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Shared\Enums\UserRoleEnum;
use App\Shared\Models\Experience;
use App\Shared\Models\Skill;
use App\Shared\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ConnectionService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->repository->setModel(new ConnectionModel());
    }

    /**
     * Send a connection request
     *
     * @param string $requesterId
     * @param string $receiverId
     * @return ConnectionModel
     * @throws ConnectionExistsException
     * @throws SelfConnectionException
     */
    public function sendConnectionRequest(string $requesterId, string $receiverId): ConnectionModel
    {
        try {
            DB::beginTransaction();

            // Check if user is trying to connect to themselves
            if ($requesterId === $receiverId) {
                throw new SelfConnectionException();
            }

            // Check if a connection already exists
            $existingConnection = ConnectionModel::where(function ($query) use ($requesterId, $receiverId) {
                $query->where('requester_id', $requesterId)
                    ->where('receiver_id', $receiverId);
            })->orWhere(function ($query) use ($requesterId, $receiverId) {
                $query->where('requester_id', $receiverId)
                    ->where('receiver_id', $requesterId);
            })->first();

            if ($existingConnection) {
                throw new ConnectionExistsException();
            }

            // Get user roles to determine connection type
            $requesterUser = User::findOrFail($requesterId);
            $receiverUser = User::findOrFail($receiverId);

            // Determine connection type based on user roles
            $type = ConnectionTypeEnum::CONNECTION;
            $status = ConnectionStatusEnum::PENDING;

            // If requester is a company, they can only follow others
            if ($requesterUser->role === UserRoleEnum::COMPANY) {
                $type = ConnectionTypeEnum::FOLLOW;
                $status = ConnectionStatusEnum::ACCEPTED;
            }
            // If receiver is a company, it's a follow relationship
            else if ($receiverUser->role === UserRoleEnum::COMPANY) {
                $type = ConnectionTypeEnum::FOLLOW;
                $status = ConnectionStatusEnum::ACCEPTED;
            }

            // Create connection entity
            $connectionEntity = new Connection(
                $requesterId,
                $receiverId,
                $status,
                $type
            );

            $connection = $this->repository->create($connectionEntity->toArray());
            $connection->load(['requester', 'receiver']);

            event(new NewConnectionRequest($connection));

            // Dispatch notification event
            event(new ConnectionRequestSentEvent($connection));

            DB::commit();

            return $connection;
        } catch (ConnectionExistsException | SelfConnectionException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Accept a connection request
     *
     * @param int $connectionId
     * @param string $userId User accepting the request
     * @return ConnectionModel
     */
    public function acceptConnectionRequest(int $connectionId, string $userId): ConnectionModel
    {
        try {
            DB::beginTransaction();

            $connection = $this->repository->findOrFail($connectionId);

            // Ensure the user is the receiver of the connection request
            if ($connection->receiver_id !== $userId) {
                throw new UnauthorizedConnectionRequestException();
            }

            // Ensure the connection is pending
            if ($connection->status !== ConnectionStatusEnum::PENDING) {
                throw new ConnectionRequestAlreadyProcessedException();
            }

            // Create connection entity and update status
            $connectionEntity = new Connection(
                $connection->requester_id,
                $connection->receiver_id,
                ConnectionStatusEnum::ACCEPTED,
                $connection->type
            );

            $connection = $this->repository->update($connectionId, $connectionEntity->toArray());
            $connection->load(['requester', 'receiver']);

            // Dispatch notification event
            event(new ConnectionRequestAcceptedEvent($connection));

            DB::commit();

            return $connection;
        } catch (UnauthorizedConnectionRequestException | ConnectionRequestAlreadyProcessedException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a connection request
     *
     * @param int $connectionId
     * @param string $userId User rejecting the request
     * @return bool
     */
    public function rejectConnectionRequest(int $connectionId, string $userId): bool
    {
        try {
            DB::beginTransaction();

            $connection = $this->repository->findOrFail($connectionId);

            // Ensure the user is the receiver of the connection request
            // if ($connection->receiver_id !== $userId) {
            //     throw new UnauthorizedConnectionRequestException('You are not authorized to reject this connection request.');
            // }

            // Ensure the connection is pending
            // if ($connection->status !== ConnectionStatusEnum::PENDING) {
            //     throw new ConnectionRequestAlreadyProcessedException();
            // }

            // Dispatch notification event before deletion
            event(new ConnectionRequestRejectedEvent($connection));

            $connection = $this->repository->delete($connectionId);

            DB::commit();

            return true;
        } catch (UnauthorizedConnectionRequestException | ConnectionRequestAlreadyProcessedException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all connections for a user
     *
     * @param string $userId
     * @param string $status Filter by status (pending, accepted, rejected)
     * @param string $type Filter by type (connection, follow)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConnections( string $userId, ?ConnectionStatusEnum $status = null, ?ConnectionTypeEnum $type = null)
    {
        $query = ConnectionModel::where(function ($query) use ($userId) {
            $query->where('requester_id', $userId)
                ->orWhere('receiver_id', $userId);
        });

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->with(['requester', 'receiver'])->get();
    }

    /**
     * Get pending connection requests for a user
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingConnectionRequests(string $userId)
    {
        return ConnectionModel::where('receiver_id', $userId)
                             ->where('status', ConnectionStatusEnum::PENDING)
                             ->where('type', ConnectionTypeEnum::CONNECTION)
                             ->with('requester')
                             ->get();
    }

    /**
     * Check if two users are connected
     *
     * @param string $userId1
     * @param string $userId2
     * @return bool
     */
    public function areUsersConnected(string $userId1, string $userId2): bool
    {
        return ConnectionModel::where(function($query) use ($userId1, $userId2) {
            $query->where('requester_id', $userId1)
                  ->where('receiver_id', $userId2);
        })->orWhere(function($query) use ($userId1, $userId2) {
            $query->where('requester_id', $userId2)
                  ->where('receiver_id', $userId1);
        })->where('status', ConnectionStatusEnum::ACCEPTED)
          ->exists();
    }

    /**
     * Get all connected user IDs for a user
     *
     * @param string $userId
     * @return array
     */
    public function getConnectedUserIds(string $userId): array
    {
        $connections = ConnectionModel::where(function($query) use ($userId) {
            $query->where('requester_id', $userId)
                  ->orWhere('receiver_id', $userId);
        })->where('status', ConnectionStatusEnum::ACCEPTED)
          ->get();

        $connectedUserIds = [];

        foreach ($connections as $connection) {
            if ($connection->requester_id === $userId) {
                $connectedUserIds[] = $connection->receiver_id;
            } else {
                $connectedUserIds[] = $connection->requester_id;
            }
        }

        return $connectedUserIds;
    }

    /**
     * Get connected users with pagination and search
     *
     * @param string $userId
     * @param string $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getConnectedUsersWithPagination(string $userId, string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        $query = ConnectionModel::connectedTo($userId);

        // Add search functionality
        if (!empty($search)) {
            $query->where(function($q) use ($search, $userId) {
                $q->whereHas('requester', function($subQuery) use ($search, $userId) {
                    if ($userId !== 'requester_id') {
                        $subQuery->where('FullName', 'like', "%{$search}%")
                                ->orWhere('UserName', 'like', "%{$search}%")
                                ->orWhere('Email', 'like', "%{$search}%");
                    }
                })->orWhereHas('receiver', function($subQuery) use ($search, $userId) {
                    if ($userId !== 'receiver_id') {
                        $subQuery->where('FullName', 'like', "%{$search}%")
                                ->orWhere('UserName', 'like', "%{$search}%")
                                ->orWhere('Email', 'like', "%{$search}%");
                    }
                });
            });
        }

        // Load the related user data
        $query->with(['requester', 'receiver']);

        return $query->paginate($perPage);
    }

    /**
     * Get followed companies with pagination and search
     *
     * @param string $userId
     * @param string $search
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFollowedCompanies(string $userId, string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        $connectedUserIds = $this->getConnectedUserIds($userId);

        $query = ConnectionModel::followedCompanies($userId);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->whereHas('receiver', function($subQuery) use ($search) {
                    $subQuery->where('FullName', 'like', "%{$search}%")
                            ->orWhere('UserName', 'like', "%{$search}%")
                            ->orWhere('Email', 'like', "%{$search}%");
                })
                ->orWhereHas('receiver.company', function($subQuery) use ($search) {
                    $subQuery->where('CompanyType', 'like', "%{$search}%");
                });
            });
        }

        // Load the company and its data
        $query->with(['receiver', 'receiver.company']);

        $companies = $query->paginate($perPage);

        foreach ($companies as $company) {
            // Get users who work at this company and are connected to the current user
            $connectedEmployees = User::whereIn('Id', $connectedUserIds)
                ->whereHas('experiences', function($q) use ($company) {
                    $q->where('CompanyID', $company->receiver->company->ID)
                    ->currentlyWorking();
                })
                ->take(3)
                ->get();

            $company->connectedEmployees = $connectedEmployees;

            // Count connected employees separately
            $connectedEmployeesCount = Experience::where('CompanyID', $company->receiver->company->ID)
                ->whereIn('UserID', $connectedUserIds)
                ->currentlyWorking()
                ->count();

            $company->connected_employees_count = $connectedEmployeesCount;
        }

        return $companies;
    }

    /**
     * Get pending connections with detailed information
     *
     * @param string $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingConnectionsDetailed(string $userId, ConnectionRoleEnum $role = ConnectionRoleEnum::RECEIVER, int $perPage = 15): LengthAwarePaginator
    {
        $column = $role === ConnectionRoleEnum::REQUESTER ? 'requester_id' : 'receiver_id';
        $relation = $role === ConnectionRoleEnum::REQUESTER ? ConnectionRoleEnum::RECEIVER->value : ConnectionRoleEnum::REQUESTER->value;

        $pendingConnections = ConnectionModel::where($column, $userId)
            ->where('status', ConnectionStatusEnum::PENDING)
            ->with([$relation => function($query) {
                $query->with(['currentExperience' => function($q) {
                    $q->with('company')->currentlyWorking();
                }, 'skills']);
            }])
            ->paginate($perPage);

        foreach ($pendingConnections as $connection) {

            $otherUserId = $role === ConnectionRoleEnum::REQUESTER ? $connection->receiver_id : $connection->requester_id;

            // Get skills
            $otherUserSkills = User::find($otherUserId)?->skills->pluck('ID')->toArray() ?? [];
            $currentUserSkills = User::find($userId)?->skills->pluck('ID')->toArray() ?? [];

            $matchingSkillIds = array_intersect($otherUserSkills, $currentUserSkills);
            $matchingSkills = Skill::whereIn('ID', $matchingSkillIds)->get();

            $connection->matchingSkills = $matchingSkills;

            $mutualConnections = $this->getMutualConnections($userId, $otherUserId);
            $connection->mutualConnections = $mutualConnections;

            $worksForFollowedCompany = $this->worksForFollowedCompany($userId, $otherUserId);
            $connection->worksForFollowedCompany = $worksForFollowedCompany;
        }

        return $pendingConnections;
    }


    /**
     * Get three mutual connections between two users
     *
     * @param string $userId1
     * @param string $userId2
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getMutualConnections(string $userId1, string $userId2)
    {
        // Get user1's connections
        $user1Connections = ConnectionModel::where(function($q) use ($userId1) {
                $q->where('requester_id', $userId1)
                  ->orWhere('receiver_id', $userId1);
            })
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->where('type' , ConnectionTypeEnum::CONNECTION)
            ->get()
            ->map(function($connection) use ($userId1) {
                return $connection->requester_id == $userId1
                    ? $connection->receiver_id
                    : $connection->requester_id;
            });

        // Get user2's connections
        $user2Connections = ConnectionModel::where(function($q) use ($userId2) {
                $q->where('requester_id', $userId2)
                  ->orWhere('receiver_id', $userId2);
            })
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->where('type' , ConnectionTypeEnum::CONNECTION)
            ->get()
            ->map(function($connection) use ($userId2) {
                return $connection->requester_id == $userId2
                    ? $connection->receiver_id
                    : $connection->requester_id;
            });

        // Find mutual connections
        $mutualConnectionIds = array_intersect($user1Connections->toArray(), $user2Connections->toArray());

        // Get the user data for mutual connections
        return User::whereIn('Id', $mutualConnectionIds)->take(3)->get();
    }

        /**
     * Check if a user works for a company that another user follows
     *
     * @param string $followerId The ID of the user who might be following companies
     * @param string $userId The ID of the user to check if they work for a followed company
     * @return bool
     */
    private function worksForFollowedCompany(string $followerId, string $userId): bool
    {
        // Get the user we're checking
        $user = User::with('currentExperience')->find($userId);

        // If user has no current experience or company, return false
        if (!$user || !$user->currentExperience || !$user->currentExperience->CompanyID) {
            return false;
        }

        // Get the company ID the user works for
        $companyId = $user->currentExperience->company->user->Id;

        // Check if the follower follows this company
        return ConnectionModel::where('requester_id', $followerId)
            ->where('receiver_id', $companyId)
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->exists();
    }

}
