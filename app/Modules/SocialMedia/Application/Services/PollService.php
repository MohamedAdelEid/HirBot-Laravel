<?php

namespace App\Modules\SocialMedia\Application\Services;

use App\Modules\SocialMedia\Application\DTOs\Post\VotePollDTO;
use App\Modules\SocialMedia\Domain\Entities\PollVote;
use App\Modules\SocialMedia\Application\Events\PollVoteEvent;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollOptionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollVoteModel;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollService
{
    private BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->repository->setModel(new PollVoteModel());
    }

    /**
     * Vote on a poll option
     *
     * @param VotePollDTO $dto
     * @return PollVoteModel
     */
    public function vote(VotePollDTO $dto): PollVoteModel
    {
        try {
            DB::beginTransaction();

            // Check if poll and option exist
            $poll = PollModel::findOrFail($dto->pollId);
            $option = PollOptionModel::where('id', $dto->optionId)
                ->where('poll_id', $dto->pollId)
                ->firstOrFail();

            // Check if user already voted on this poll
            $existingVote = PollVoteModel::where('user_id', $dto->userId)
                ->where('poll_id', $dto->pollId)
                ->first();

            if ($existingVote) {
                // If voting for the same option, do nothing
                if ($existingVote->option_id === $dto->optionId) {
                    DB::commit();
                    return $existingVote;
                }

                // If voting for a different option, update the vote
                // First, decrement the vote count of the previous option
                $previousOption = PollOptionModel::find($existingVote->option_id);
                if ($previousOption) {
                    $previousOption->decrement('vote_count');
                }

                // Update the vote
                $existingVote->option_id = $dto->optionId;
                $existingVote->save();

                // Increment the vote count of the new option
                $option->increment('vote_count');

                // Broadcast the event
                event(new PollVoteEvent($poll->post_id, $poll->id, $option->id, $dto->userId));

                DB::commit();
                return $existingVote;
            }

            // Create a new vote
            $voteEntity = new PollVote(
                $dto->userId,
                $dto->optionId,
                $dto->pollId
            );

            $vote = $this->repository->create($voteEntity->toArray());

            // Increment the vote count
            $option->increment('vote_count');

            // Broadcast the event
            event(new PollVoteEvent($poll->post_id, $poll->id, $option->id, $dto->userId));

            DB::commit();

            return $vote;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error voting on poll: ' . $e->getMessage(), [
                'exception' => $e,
                'dto' => $dto
            ]);
            throw $e;
        }
    }

    /**
     * Remove a vote from a poll
     *
     * @param string $userId
     * @param int $pollId
     * @return bool
     */
    public function removeVote(string $userId, int $pollId): bool
    {
        try {
            DB::beginTransaction();

            // Find the vote
            $vote = PollVoteModel::where('user_id', $userId)
                ->where('poll_id', $pollId)
                ->first();

            if (!$vote) {
                throw new \Exception('Vote not found', 404);
            }

            // Get the option and poll for the event
            $option = PollOptionModel::find($vote->option_id);
            $poll = PollModel::find($pollId);

            if ($option) {
                // Decrement the vote count
                $option->decrement('vote_count');
            }

            // Delete the vote
            $vote->delete();

            // Broadcast the event
            if ($poll) {
                event(new PollVoteEvent($poll->post_id, $pollId, $vote->option_id, $userId, true));
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing vote: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $userId,
                'poll_id' => $pollId
            ]);
            throw $e;
        }
    }

    /**
     * Check if a user has voted on a poll
     *
     * @param string $userId
     * @param int $pollId
     * @return PollVoteModel|null
     */
    public function getUserVote(string $userId, int $pollId): ?PollVoteModel
    {
        return PollVoteModel::where('user_id', $userId)
            ->where('poll_id', $pollId)
            ->first();
    }
}
