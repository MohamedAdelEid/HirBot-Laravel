<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Modules\SocialMedia\Presentation\Http\Resources\Feed\InteractionResource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInteractionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $interaction;

    /**
     * Create a new event instance.
     *
     * @param InteractionModel $interaction
     */
    public function __construct(InteractionModel $interaction)
    {
        $this->interaction = $interaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // Determine the channel based on the interactable type
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
        return 'new.interaction';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $interactions = InteractionModel::where('interactable_id', $this->interaction->interactable_id)
            ->where('interactable_type', $this->interaction->interactable_type)
            ->get();

        // Group interactions by type and count them
        $interactionCounts = $interactions->groupBy('type')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return [
            'interaction' => (new InteractionResource($this->interaction))->resolve(),
            'count' => $interactions->count(),
            'counts_by_type' => $interactionCounts
        ];
    }
}
