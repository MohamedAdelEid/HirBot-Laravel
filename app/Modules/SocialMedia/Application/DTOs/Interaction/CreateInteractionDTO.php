<?php

namespace App\Modules\SocialMedia\Application\DTOs\Interaction;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractionTypeEnum;
use Illuminate\Support\Facades\Auth;

class CreateInteractionDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly int $interactableId,
        public readonly InteractableTargetTypeEnum $interactableType,
        public readonly InteractionTypeEnum $type
    ) {}

    public static function fromRequest(array $data, int $interactableId, InteractableTargetTypeEnum $interactableType): self
    {
        return new self(
            userId: Auth::user()->Id,
            interactableId: $interactableId,
            interactableType: $interactableType,
            type: InteractionTypeEnum::from($data['type'])
        );
    }
}
