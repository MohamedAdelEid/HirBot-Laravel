<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\InteractionResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InteractionCountsUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $interaction;
    public $isDeleted;

    /**
     * Create a new event instance.
     *
     * @param InteractionModel|null $interaction
     * @param bool $isDeleted
     */
    public function __construct(
        ?InteractionModel $interaction = null,
        bool $isDeleted = false
    ) {
        $this->interaction = $interaction;
        $this->isDeleted = $isDeleted;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        $interactableType = $this->interaction->interactable_type;

        return new Channel($interactableType . '.' . $this->interaction->interactable_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'interaction.counts.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Fetch all interactions for the given interactable
        $interactions = InteractionModel::query()
            ->where('interactable_id', $this->interaction->interactable_id)
            ->where('interactable_type', $this->interaction->interactable_type)
            ->get();

        // Group interactions by type and count them
        $interactionCounts = $interactions->groupBy('type')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        // Prepare the result array
        $result = [
            'count' => $interactions->count(),
            'counts_by_type' => $interactionCounts,
            'is_deleted' => $this->isDeleted,
        ];

        // If an interaction instance is available (e.g., in case of creation), include it
        if ($this->interaction) {
            $this->interaction->loadMissing('user');
            $result['interaction'] = (new InteractionResource($this->interaction))->resolve();
        }

        return $result;
    }

}
