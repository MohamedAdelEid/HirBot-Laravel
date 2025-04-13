<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Feed;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Modules\SocialMedia\Presentation\Http\Resources\Post\MediaResource;
use App\Modules\SocialMedia\Presentation\Http\Resources\Post\PollOptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Get current user's interaction with this post
        $userInteraction = null;
        $userId = Auth::check() ? Auth::user()->Id : null;

        if ($userId) {
            $userInteraction = InteractionModel::where('user_id', $userId)
                ->where('interactable_id', $this->id)
                ->where('interactable_type', InteractableTargetTypeEnum::POST->morphClass())
                ->first();
        }

        // Get interaction counts by type
        $interactionCounts = [];
        $interactions = InteractionModel::where('interactable_id', $this->id)
            ->where('interactable_type', InteractableTargetTypeEnum::POST->morphClass())
            ->get();

        $interactionCounts = $interactions
            ->groupBy('type')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'content' => $this->content,
            'privacy_comments' => $this->privacy_comments,
            'visibility' => $this->visibility,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'poll' => $this->whenLoaded('poll', function () {
                return [
                    'id' => $this->poll->id,
                    'question' => $this->poll->question,
                    'options' => PollOptionResource::collection($this->poll->options)
                ];
            }),
            // Only include the last two comments
            'recent_comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(isset($this->comments_count), $this->comments_count),
            // Include the last interaction for notification
            'last_interaction' => InteractionResource::collection($this->whenLoaded('interactions')),
            'total_interactions' => $this->when(isset($this->interactions_count), $this->interactions_count),
            'interactions_count' => $interactionCounts,
            'user_interacted' => !is_null($userInteraction),
            'user_interaction_type' => $userInteraction ? $userInteraction->type : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
