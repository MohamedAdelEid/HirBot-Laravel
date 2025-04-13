<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Post\PostVisibilityEnum;
use App\Modules\SocialMedia\Domain\Enums\Post\PrivacyCommentsEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PollModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\PostMediaModel;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'visibility' => PostVisibilityEnum::class,
        'privacy_comments' => PrivacyCommentsEnum::class,
    ];


    /**
     * Get the user who created the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'Id');
    }

    /**
     * Get the media for the post.
     */
    public function media(): HasMany
    {
        return $this->hasMany(PostMediaModel::class, 'post_id');
    }

    /**
     * Get the poll for the post.
     */
    public function poll(): HasOne
    {
        return $this->hasOne(PollModel::class, 'post_id');
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CommentModel::class, 'post_id');
    }

    /**
     * Get the interactions for the post.
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(InteractionModel::class, 'interactable');
    }

    /**
     * Get the views for the post.
     */
    public function views(): HasMany
    {
        return $this->hasMany(PostViewModel::class, 'post_id');
    }

    /**
     * Get the morph class name for this model
     *
     * @return string
     */
    public function getMorphClass()
    {
        return 'post';
    }
}
