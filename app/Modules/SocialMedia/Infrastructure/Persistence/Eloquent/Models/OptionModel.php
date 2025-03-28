<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionModel extends Model
{
    protected $table = 'options';
    
    protected $fillable = [
        'poll_id',
        'option_text',
        'vote_count'
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(PollModel::class, 'poll_id');
    }
}