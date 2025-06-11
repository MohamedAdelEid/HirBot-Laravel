<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionRequestRejectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ConnectionModel $connection
    ) {}
}
