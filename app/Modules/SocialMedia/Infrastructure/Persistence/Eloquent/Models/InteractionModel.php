<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionModel extends Model
{
    use HasFactory;

    protected $table = 'interactions';

    protected $fillable = [
        'user_id',
        'post_id',
        'type',
    ];

    /**
     * Get the user who created the interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the post that the interaction belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(PostModel::class, 'post_id');
    }
}
