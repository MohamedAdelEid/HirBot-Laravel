<?php

namespace App\Modules\SocialMedia\Application\Facades;

use App\Modules\SocialMedia\Application\DTOs\Interaction\CreateInteractionDTO;
use App\Modules\SocialMedia\Application\Services\InteractionService;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use Illuminate\Support\Facades\Facade;

class InteractionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return InteractionService::class;
    }

    /**
     * Create an interaction
     *
     * @param array $data
     * @param int $interactableId
     * @param InteractableTargetTypeEnum $interactableType
     * @return InteractionModel
     */
    public static function createInteraction(array $data, int $interactableId, InteractableTargetTypeEnum $interactableType): InteractionModel
    {
        $dto = CreateInteractionDTO::fromRequest($data, $interactableId, $interactableType);
        return static::getFacadeRoot()->createInteraction($dto);
    }

    /**
     * Delete an interaction
     *
     * @param int $interactableId
     * @param InteractableTargetTypeEnum $interactableType
     * @param string $userId
     * @return bool
     */
    public static function deleteInteraction(int $interactableId, InteractableTargetTypeEnum $interactableType, string $userId): bool
    {
        return static::getFacadeRoot()->deleteInteraction($interactableId, $interactableType->morphClass(), $userId);
    }
}
