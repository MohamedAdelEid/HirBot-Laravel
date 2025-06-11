<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Interaction\CreateInteractionDTO;
use App\Modules\SocialMedia\Application\Events\DeleteInteractionEvent;
use App\Modules\SocialMedia\Application\Events\InteractionAddedEvent;
use App\Modules\SocialMedia\Application\Events\NewInteractionEvent;
use App\Modules\SocialMedia\Domain\Entities\Interaction;
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
            event(new NewInteractionEvent($interaction));

            // Dispatch notification event
            event(new InteractionAddedEvent($interaction));

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
                ->first();

            if (!$interaction) {
                throw new \Exception('Interaction not found', 404);
            }

            // Load relationships before deleting
            $interaction->load(['user', 'interactable']);

            // Store a copy of the interaction for the event
            $interactionCopy = clone $interaction;

            // Delete the interaction
            $this->repository->delete($interaction->id);

            // Get updated counts after deletion
            $interactions = InteractionModel::where('interactable_id', $interactableId)
                ->where('interactable_type', $interactableType)
                ->get();

            $count = $interactions->count();

            // Group interactions by type and count them
            $countsByType = $interactions->groupBy('type')
                ->map(function ($group) {
                    return $group->count();
                })
                ->toArray();

            // Broadcast the deletion event with just the required data
            event(new DeleteInteractionEvent(
                $userId,
                $interactableId,
                $interactableType,
                $count,
                $countsByType
            ));

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
