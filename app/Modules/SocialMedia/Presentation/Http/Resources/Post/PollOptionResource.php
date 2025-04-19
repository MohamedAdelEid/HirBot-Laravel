<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Post;

use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollVoteModel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PollOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Check if the current user has voted for this option
        $isChosen = false;
        $userId = Auth::check() ? Auth::user()->Id : null;

        if ($userId) {
            $isChosen = PollVoteModel::where('user_id', $userId)
                ->where('option_id', $this->id)
                ->exists();
        }

        // Calculate percentage if total votes > 0
        $totalVotes = $this->poll->options->sum('vote_count');
        $percentage = $totalVotes > 0 ? round(($this->vote_count / $totalVotes) * 100, 1) : 0;

        return [
            'id' => $this->id,
            'content' => $this->content,
            'vote_count' => $this->vote_count,
            'percentage' => $percentage,
            'is_chosen' => $isChosen,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
