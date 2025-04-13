<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractableTargetTypeEnum;
use App\Modules\SocialMedia\Domain\Enums\Interaction\InteractionTypeEnum;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InteractionModel extends Model
{
    use HasFactory;

    protected $table = 'interactions';

    protected $cast = [
        InteractionTypeEnum::class,
    ];

    protected $fillable = [
        'user_id',
        'interactable_id',
        'interactable_type',
        'type',
    ];

    /**
     * Get the user who created the interaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'Id');
    }

    /**
     * Get the interactable model (e.g., Post, Comment).
     */
    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }


    /**
     * Get the interactable type as enum
     *
     * @return InteractableTargetTypeEnum
     */
    public function getInteractableTypeEnum(): InteractableTargetTypeEnum
    {
        return InteractableTargetTypeEnum::from($this->interactable_type);
    }
}
