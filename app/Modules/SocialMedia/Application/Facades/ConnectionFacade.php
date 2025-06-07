<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Connection\ProcessConnectionDTO;
use App\Modules\SocialMedia\Application\DTOs\Connection\SendConnectionDTO;
use App\Modules\SocialMedia\Application\Exceptions\Connection\ConnectionExistsException;
use App\Modules\SocialMedia\Application\Exceptions\Connection\SelfConnectionException;
use App\Modules\SocialMedia\Application\Services\ConnectionService;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use Illuminate\Support\Facades\Facade;

class ConnectionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ConnectionService::class;
    }

    /**
     * Send a connection request
     *
     * @param array $data
     * @return ConnectionModel
     * @throws ConnectionExistsException
     * @throws SelfConnectionException
     */
    public static function sendConnectionRequest(array $data): ConnectionModel
    {
        $dto = SendConnectionDTO::fromRequest($data);
        return static::getFacadeRoot()->sendConnectionRequest(
            $dto->requesterId,
            $dto->receiverId
        );
    }

    public static function acceptConnectionRequest(array $data): ConnectionModel
    {
        $dto = ProcessConnectionDTO::fromRequest($data);
        return static::getFacadeRoot()->acceptConnectionRequest(
            $dto->connectionId,
            $dto->userId
        );
    }

    public static function rejectConnectionRequest(array $data): bool
    {
        $dto = ProcessConnectionDTO::fromRequest($data);
        return static::getFacadeRoot()->rejectConnectionRequest(
            $dto->connectionId,
            $dto->userId
        );
    }

    public static function getUserConnections(string $userId, ?string $status = null, ?string $type = null)
    {
        return static::getFacadeRoot()->getUserConnections($userId, $status, $type);
    }

    public static function getConnectedUserIds(string $userId): array
    {
        return static::getFacadeRoot()->getConnectedUserIds($userId);
    }

    public static function areUsersConnected(string $userId1, string $userId2): bool
    {
        return static::getFacadeRoot()->areUsersConnected($userId1, $userId2);
    }
}
