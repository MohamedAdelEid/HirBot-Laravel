<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOptionModel extends Model
{
    protected $table = 'options';

    protected $fillable = [
        'poll_id',
        'content',
        'vote_count'
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(PollModel::class, 'poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVoteModel::class, 'option_id');
    }
}
