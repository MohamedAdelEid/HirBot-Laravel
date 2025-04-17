<?php

namespace App\Modules\SocialMedia\Application\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteInteractionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $interactableId;
    public $interactableType;
    public $count;
    public $countsByType;

    /**
     * Create a new event instance.
     *
     * @param string $userId
     * @param int $interactableId
     * @param string $interactableType
     * @param int $count
     * @param array $countsByType
     */
    public function __construct(
        string $userId,
        int $interactableId,
        string $interactableType,
        int $count,
        array $countsByType
    ) {
        $this->userId = $userId;
        $this->interactableId = $interactableId;
        $this->interactableType = $interactableType;
        $this->count = $count;
        $this->countsByType = $countsByType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        $channel = $this->interactableType . '.' . $this->interactableId;
        return new Channel($channel);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'interaction.deleted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'interactable_id' => $this->interactableId,
            'interactable_type' => $this->interactableType,
            'count' => $this->count,
            'counts_by_type' => $this->countsByType
        ];
    }
}
