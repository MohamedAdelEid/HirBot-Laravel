<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Presentation\Http\Resources\Connection\ConnectionResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConnectionRequest implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection;

    /**
     * Create a new event instance.
     *
     * @param ConnectionModel $connection
     */
    public function __construct(ConnectionModel $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // dd($this->connection->receiver_id);
        return new PrivateChannel('user.' . $this->connection->receiver_id);
    }

    /**
     * The name of the event that will be broadcasted.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'new.connection.request';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'connection' => (new ConnectionResource($this->connection))->resolve(),
        ];
    }
}

