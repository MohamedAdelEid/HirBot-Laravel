<?php

namespace App\Shared\Resources;

use App\Shared\Enums\NotifiableTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationReceiverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $notification = $this->notification;

        return [
            'id' => $this->ID,
            'notification_id' => $notification->ID,
            'type' => [
                'value' => $notification->type->value,
                'label' => $notification->type->label(),
                'category' => $notification->type->category(),
                'action' => $notification->type->action(),
            ],
            'message' => $notification->massage,
            'is_read' => !is_null($this->read_at),
            'read_at' => $this->read_at,
            'created_at' => $this->CreationDate,
            'meta_data' => $this->generateMetaData($notification),
        ];
    }

    /**
     * Generate dynamic meta data based on notification type.
     *
     * @param \App\Shared\Models\Notification $notification
     * @return array
     */
    private function generateMetaData($notification): array
    {
        $notifiable = $notification->notifiable;

        if (!$notifiable) {
            return [];
        }

        return match($notification->type) {
            NotifiableTypeEnum::POST_CREATED => $this->getPostCreatedMetaData($notifiable),
            NotifiableTypeEnum::POST_COMMENTED => $this->getPostCommentedMetaData($notifiable),
            NotifiableTypeEnum::POST_LIKED => $this->getPostLikedMetaData($notifiable),
            NotifiableTypeEnum::POST_SHARED => $this->getPostSharedMetaData($notifiable),
            NotifiableTypeEnum::CONNECTION_REQUEST_SENT => $this->getConnectionRequestSentMetaData($notifiable),
            NotifiableTypeEnum::CONNECTION_REQUEST_ACCEPTED => $this->getConnectionRequestAcceptedMetaData($notifiable),
            NotifiableTypeEnum::CONNECTION_REQUEST_REJECTED => $this->getConnectionRequestRejectedMetaData($notifiable),
            NotifiableTypeEnum::COMMENT_LIKED => $this->getCommentLikedMetaData($notifiable),
            NotifiableTypeEnum::COMMENT_REPLIED => $this->getCommentRepliedMetaData($notifiable),
            NotifiableTypeEnum::POLL_VOTED => $this->getPollVotedMetaData($notifiable),
            NotifiableTypeEnum::POLL_ENDED => $this->getPollEndedMetaData($notifiable),
            default => [],
        };
    }

    /**
     * Get meta data for post created notification.
     *
     * @param mixed $post
     * @return array
     */
    private function getPostCreatedMetaData($post): array
    {
        return [
            'post' => [
                'id' => $post->id,
                'content' => $post->content ? substr($post->content, 0, 100) . (strlen($post->content) > 100 ? '...' : '') : null,
                'has_media' => $post->media && $post->media->count() > 0,
                'media_count' => $post->media ? $post->media->count() : 0,
                'has_poll' => $post->poll ? true : false,
            ],
            'author' => [
                'id' => $post->user->Id,
                'name' => $post->user->FullName,
                'username' => $post->user->UserName,
                'profile_image' => $post->user->ImagePath,
            ],
        ];
    }

    /**
     * Get meta data for post commented notification.
     *
     * @param mixed $post
     * @return array
     */
    private function getPostCommentedMetaData($post): array
    {
        $latestComment = $post->comments()->latest()->first();

        return [
            'post' => [
                'id' => $post->id,
                'content' => $post->content ? substr($post->content, 0, 100) . (strlen($post->content) > 100 ? '...' : '') : null,
            ],
            'comment' => $latestComment ? [
                'id' => $latestComment->id,
                'content' => substr($latestComment->content, 0, 100) . (strlen($latestComment->content) > 100 ? '...' : ''),
                'author' => [
                    'id' => $latestComment->user->Id,
                    'name' => $latestComment->user->FullName,
                    'username' => $latestComment->user->UserName,
                    'profile_image' => $latestComment->user->ImagePath,
                ],
            ] : null,
        ];
    }

    /**
     * Get meta data for post liked notification.
     *
     * @param mixed $post
     * @return array
     */
    private function getPostLikedMetaData($post): array
    {
        $latestLike = $post->interactions()->where('type', 'like')->latest()->first();

        return [
            'post' => [
                'id' => $post->id,
                'content' => $post->content ? substr($post->content, 0, 100) . (strlen($post->content) > 100 ? '...' : '') : null,
            ],
            'liker' => $latestLike ? [
                'id' => $latestLike->user->Id,
                'name' => $latestLike->user->FullName,
                'username' => $latestLike->user->UserName,
                'profile_image' => $latestLike->user->ImagePath,
            ] : null,
            'total_likes' => $post->interactions()->where('type', 'like')->count(),
        ];
    }

    /**
     * Get meta data for post shared notification.
     *
     * @param mixed $post
     * @return array
     */
    private function getPostSharedMetaData($post): array
    {
        return [
            'post' => [
                'id' => $post->id,
                'content' => $post->content ? substr($post->content, 0, 100) . (strlen($post->content) > 100 ? '...' : '') : null,
            ],
            'total_shares' => $post->interactions()->where('type', 'share')->count(),
        ];
    }

    /**
     * Get meta data for connection request sent notification.
     *
     * @param mixed $connection
     * @return array
     */
    private function getConnectionRequestSentMetaData($connection): array
    {
        return [
            'connection' => [
                'id' => $connection->id,
                'type' => $connection->type->value,
                'status' => $connection->status->value,
            ],
            'requester' => [
                'id' => $connection->requester->Id,
                'name' => $connection->requester->FullName,
                'username' => $connection->requester->UserName,
                'profile_image' => $connection->requester->ImagePath,
                'current_position' => $connection->requester->currentExperience?->Position,
                'current_company' => $connection->requester->currentExperience?->company?->Name,
            ],
        ];
    }

    /**
     * Get meta data for connection request accepted notification.
     *
     * @param mixed $connection
     * @return array
     */
    private function getConnectionRequestAcceptedMetaData($connection): array
    {
        return [
            'connection' => [
                'id' => $connection->id,
                'type' => $connection->type->value,
                'status' => $connection->status->value,
            ],
            'accepter' => [
                'id' => $connection->receiver->Id,
                'name' => $connection->receiver->FullName,
                'username' => $connection->receiver->UserName,
                'profile_image' => $connection->receiver->ImagePath,
                'current_position' => $connection->receiver->currentExperience?->Position,
                'current_company' => $connection->receiver->currentExperience?->company?->Name,
            ],
        ];
    }

    /**
     * Get meta data for connection request rejected notification.
     *
     * @param mixed $connection
     * @return array
     */
    private function getConnectionRequestRejectedMetaData($connection): array
    {
        return [
            'connection' => [
                'id' => $connection->id,
                'type' => $connection->type->value,
                'status' => $connection->status->value,
            ],
        ];
    }

    /**
     * Get meta data for comment liked notification.
     *
     * @param mixed $comment
     * @return array
     */
    private function getCommentLikedMetaData($comment): array
    {
        $latestLike = $comment->interactions()->where('type', 'like')->latest()->first();

        return [
            'comment' => [
                'id' => $comment->id,
                'content' => substr($comment->content, 0, 100) . (strlen($comment->content) > 100 ? '...' : ''),
            ],
            'post' => [
                'id' => $comment->post->id,
                'content' => $comment->post->content ? substr($comment->post->content, 0, 100) . (strlen($comment->post->content) > 100 ? '...' : '') : null,
            ],
            'liker' => $latestLike ? [
                'id' => $latestLike->user->Id,
                'name' => $latestLike->user->FullName,
                'username' => $latestLike->user->UserName,
                'profile_image' => $latestLike->user->ImagePath,
            ] : null,
            'total_likes' => $comment->interactions()->where('type', 'like')->count(),
        ];
    }

    /**
     * Get meta data for comment replied notification.
     *
     * @param mixed $comment
     * @return array
     */
    private function getCommentRepliedMetaData($comment): array
    {
        return [
            'comment' => [
                'id' => $comment->id,
                'content' => substr($comment->content, 0, 100) . (strlen($comment->content) > 100 ? '...' : ''),
            ],
            'post' => [
                'id' => $comment->post->id,
                'content' => $comment->post->content ? substr($comment->post->content, 0, 100) . (strlen($comment->post->content) > 100 ? '...' : '') : null,
            ],
            'replier' => [
                'id' => $comment->user->Id,
                'name' => $comment->user->FullName,
                'username' => $comment->user->UserName,
                'profile_image' => $comment->user->ImagePath,
            ],
        ];
    }

    /**
     * Get meta data for poll voted notification.
     *
     * @param mixed $poll
     * @return array
     */
    private function getPollVotedMetaData($poll): array
    {
        $latestVote = $poll->votes()->latest()->first();

        return [
            'poll' => [
                'id' => $poll->id,
                'question' => $poll->question,
                'total_votes' => $poll->votes()->count(),
            ],
            'post' => [
                'id' => $poll->post->id,
                'content' => $poll->post->content ? substr($poll->post->content, 0, 100) . (strlen($poll->post->content) > 100 ? '...' : '') : null,
            ],
            'voter' => $latestVote ? [
                'id' => $latestVote->user->Id,
                'name' => $latestVote->user->FullName,
                'username' => $latestVote->user->UserName,
                'profile_image' => $latestVote->user->ImagePath,
            ] : null,
        ];
    }

    /**
     * Get meta data for poll ended notification.
     *
     * @param mixed $poll
     * @return array
     */
    private function getPollEndedMetaData($poll): array
    {
        $winningOption = $poll->options()->orderBy('vote_count', 'desc')->first();

        return [
            'poll' => [
                'id' => $poll->id,
                'question' => $poll->question,
                'total_votes' => $poll->votes()->count(),
                'winning_option' => $winningOption ? [
                    'id' => $winningOption->id,
                    'content' => $winningOption->content,
                    'vote_count' => $winningOption->vote_count,
                ] : null,
            ],
            'post' => [
                'id' => $poll->post->id,
                'content' => $poll->post->content ? substr($poll->post->content, 0, 100) . (strlen($poll->post->content) > 100 ? '...' : '') : null,
            ],
        ];
    }
}
