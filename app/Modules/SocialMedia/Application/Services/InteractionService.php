<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Interaction\CreateInteractionDTO;
use App\Modules\SocialMedia\Domain\Entities\Interaction;
use App\Modules\SocialMedia\Application\Events\NewInteractionEvent;
use App\Modules\SocialMedia\Application\Events\InteractionCountsUpdatedEvent;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class InteractionService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->repository->setModel(new InteractionModel());
    }

    /**
     * Create an interaction
     *
     * @param CreateInteractionDTO $dto
     * @return InteractionModel
     */
    public function createInteraction(CreateInteractionDTO $dto): InteractionModel
    {
        try {
            DB::beginTransaction();

            // Check if the interactable model exists
            $modelClass = $dto->interactableType->modelClass();
            $modelClass::findOrFail($dto->interactableId);

            $interactionEntity = new Interaction(
                $dto->userId,
                $dto->interactableId,
                $dto->interactableType,
                $dto->type->value
            );

            // Check if user already interacted with this item
            $existingInteraction = InteractionModel::where('user_id', $dto->userId)
                ->where('interactable_id', $dto->interactableId)
                ->where('interactable_type', $dto->interactableType->morphClass())
                ->first();

            // Update or create the interaction
            if ($existingInteraction) {
                $interaction = $this->repository->update($existingInteraction->id, [
                    'type' => $dto->type->value
                ]);
            } else {
                $interaction = $this->repository->create($interactionEntity->toArray());
            }

            $interaction->load(['user', 'interactable']);

            // Broadcast the updated counts with interaction data
            event(new InteractionCountsUpdatedEvent(
                $interaction,
                false
            ));

            DB::commit();

            return $interaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an interaction
     *
     * @param int $interactableId
     * @param string $interactableType
     * @param string $userId
     * @return bool
     */
       public function deleteInteraction(int $interactableId, string $interactableType, string $userId): bool
    {
        try {
            DB::beginTransaction();

            $interaction = InteractionModel::where('interactable_id', $interactableId)
                ->where('interactable_type', $interactableType)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Load relationships before deleting
            $interaction->load(['user', 'interactable']);

            // Store a copy of the interaction for the event
            $interactionCopy = clone $interaction;

            // Delete the interaction
            $this->repository->delete($interaction->id);

            // Broadcast the deletion event with interaction data
            event(new InteractionCountsUpdatedEvent(
                $interactionCopy,
                true
            ));

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
