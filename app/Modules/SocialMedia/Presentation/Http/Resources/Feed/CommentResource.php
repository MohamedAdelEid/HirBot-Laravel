<?php

namespace App\Modules\SocialMedia\Presentation\Http\Resources\Feed;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Get current user's interaction with this comment
        $userInteraction = null;
        $userId = Auth::check() ? Auth::user()->Id : null;

        if ($userId) {
            $userInteraction = InteractionModel::where('user_id', $userId)
                ->where('interactable_id', $this->id)
                ->where('interactable_type', InteractableTargetTypeEnum::COMMENT->morphClass())
                ->first();
        }

        // Get interaction counts by type
        $interactionCounts = [];
        $interactions = InteractionModel::where('interactable_id', $this->id)
            ->where('interactable_type', InteractableTargetTypeEnum::COMMENT->morphClass())
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
            'post_id' => $this->post_id,
            'parent_comment_id' => $this->parent_comment_id,
            'content' => $this->content,
            'image_path' => $this->image_path,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count),
            'interactions_count' => $interactionCounts,
            'total_interactions' => $this->whenLoaded('interactions', function () {
                return $this->interactions->count();
            }),
            'user_interacted' => !is_null($userInteraction),
            'user_interaction_type' => $userInteraction ? $userInteraction->type : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
