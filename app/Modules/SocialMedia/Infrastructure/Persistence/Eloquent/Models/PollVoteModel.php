<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVoteModel extends Model
{
    protected $table = 'poll_votes';

    protected $fillable = [
        'user_id',
        'option_id',
        'poll_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'Id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOptionModel::class, 'option_id');
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(PollModel::class, 'poll_id');
    }
}
