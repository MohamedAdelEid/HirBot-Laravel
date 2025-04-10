<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionExistsException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionRequestAlreadyProcessedException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\SelfConnectionException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\UnauthorizedConnectionRequestException;
use App\Modules\SocialMedia\Domain\Entities\Connection;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Shared\Enums\UserRoleEnum;
use App\Shared\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

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
     * @return ConnectionModel
     */
    public function rejectConnectionRequest(int $connectionId, string $userId): ConnectionModel
    {
        try {
            DB::beginTransaction();

            $connection = $this->repository->findOrFail($connectionId);

            // Ensure the user is the receiver of the connection request
            if ($connection->receiver_id !== $userId) {
                throw new UnauthorizedConnectionRequestException('You are not authorized to reject this connection request.');
            }

            // Ensure the connection is pending
            if ($connection->status !== ConnectionStatusEnum::PENDING) {
                throw new ConnectionRequestAlreadyProcessedException();
            }

            // Create connection entity and update status
            $connectionEntity = new Connection(
                $connection->requester_id,
                $connection->receiver_id,
                ConnectionStatusEnum::REJECTED,
                $connection->type
            );

            $connection = $this->repository->update($connectionId, $connectionEntity->toArray());

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
     * Get all connections for a user
     *
     * @param string $userId
     * @param string $status Filter by status (pending, accepted, rejected)
     * @param string $type Filter by type (connection, follow)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConnections(
        string $userId,
        ?ConnectionStatusEnum $status = null,
        ?ConnectionTypeEnum $type = null
    ) {
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
}
