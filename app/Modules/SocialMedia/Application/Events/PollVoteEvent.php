<?php

namespace App\Modules\SocialMedia\Application\Events;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollVoteEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $postId;
    public $pollId;
    public $optionId;
    public $userId;
    public $isRemoved;

    /**
     * Create a new event instance.
     *
     * @param int $postId
     * @param int $pollId
     * @param int $optionId
     * @param string $userId
     * @param bool $isRemoved
     */
    public function __construct(
        int $postId,
        int $pollId,
        int $optionId,
        string $userId,
        bool $isRemoved = false
    ) {
        $this->postId = $postId;
        $this->pollId = $pollId;
        $this->optionId = $optionId;
        $this->userId = $userId;
        $this->isRemoved = $isRemoved;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('poll.' . $this->pollId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'vote.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Get the updated poll with options
        $poll = PollModel::with('options')->find($this->pollId);

        // Calculate total votes
        $totalVotes = $poll->options->sum('vote_count');

        // Format options with percentages
        $options = $poll->options->map(function($option) use ($totalVotes) {
            $percentage = $totalVotes > 0 ? round(($option->vote_count / $totalVotes) * 100, 1) : 0;

            return [
                'id' => $option->id,
                'content' => $option->content,
                'vote_count' => $option->vote_count,
                'percentage' => $percentage
            ];
        });

        return [
            'post_id' => $this->postId,
            'poll_id' => $this->pollId,
            'option_id' => $this->optionId,
            'user_id' => $this->userId,
            'is_removed' => $this->isRemoved,
            'options' => $options,
            'total_votes' => $totalVotes
        ];
    }
}
