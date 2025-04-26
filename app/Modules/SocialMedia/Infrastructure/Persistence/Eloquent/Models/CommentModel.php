<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommentModel extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \App\Modules\SocialMedia\Database\Factories\CommentModelFactory::new();
    }
    protected $table = 'comments';

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_comment_id',
        'content',
        'image_path',
    ];

    /**
     * Get the user who created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'Id');
    }

    /**
     * Get the post that the comment belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }

    /**
     * Get the parent comment.
     */
    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(CommentModel::class, 'parent_comment_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(CommentModel::class, 'parent_comment_id');
    }

    /**
     * Get the interactions for this comment.
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(InteractionModel::class, 'interactable');
    }

    /**
     * Get the morph class name for this model
     *
     * @return string
     */
    public function getMorphClass()
    {
        return 'comment';
    }
}
