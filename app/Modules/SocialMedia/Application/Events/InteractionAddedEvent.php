<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InteractionAddedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public InteractionModel $interaction
    ) {}
}
