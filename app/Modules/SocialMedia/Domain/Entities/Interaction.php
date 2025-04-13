<?php

namespace App\Modules\SocialMedia\Domain\Entities;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;

class Interaction
{
    private string $userId;
    private int $interactableId;
    private InteractableTargetTypeEnum $interactableType;
    private string $type;

    public function __construct(
        string $userId,
        int $interactableId,
        InteractableTargetTypeEnum $interactableType,
        string $type
    ) {
        $this->userId = $userId;
        $this->interactableId = $interactableId;
        $this->interactableType = $interactableType;
        $this->type = $type;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getInteractableId(): int
    {
        return $this->interactableId;
    }

    public function getInteractableType(): InteractableTargetTypeEnum
    {
        return $this->interactableType;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'interactable_id' => $this->interactableId,
            'interactable_type' => $this->interactableType->morphClass(),
            'type' => $this->type,
        ];
    }
}
