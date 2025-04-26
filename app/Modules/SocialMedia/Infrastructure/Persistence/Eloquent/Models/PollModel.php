<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostModel;

class PollModel extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \App\Modules\SocialMedia\Database\Factories\PollModelFactory::new();
    }

    protected $table = 'polls';

    protected $fillable = [
        'post_id',
        'question'
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOptionModel::class, 'poll_id');
    }
}
