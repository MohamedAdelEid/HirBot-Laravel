<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\PrivacyCommentsEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostModel extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'privacy_comments',
        'visibility'
    ];

    protected $casts = [
        'privacy_comments' => PrivacyCommentsEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMediaModel::class, 'post_id');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(PollModel::class, 'post_id');
    }

    // public function comments(): HasMany
    // {
    //     return $this->hasMany(CommentModel::class, 'post_id');
    // }

    // public function interactions(): HasMany
    // {
    //     return $this->hasMany(InteractionModel::class, 'post_id');
    // }
}
