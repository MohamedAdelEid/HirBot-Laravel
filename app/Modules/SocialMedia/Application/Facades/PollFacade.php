<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Post\VotePollDTO;
use App\Modules\SocialMedia\Application\Services\PollService;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollVoteModel;
use Illuminate\Support\Facades\Facade;

class PollFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PollService::class;
    }

    /**
     * Vote on a poll option
     *
     * @param array $data
     * @return PollVoteModel
     */
    public static function vote(array $data): PollVoteModel
    {
        $dto = VotePollDTO::fromRequest($data);
        return static::getFacadeRoot()->vote($dto);
    }
    
}
