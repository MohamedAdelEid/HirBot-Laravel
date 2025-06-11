<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public CommentModel $comment
    ) {}
}
