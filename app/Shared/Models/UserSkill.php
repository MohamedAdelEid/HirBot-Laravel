<?php

namespace App\Shared\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    use HasFactory;

    protected $table = 'UserSkills';

    protected $fillable = [
        'UserID',
        'SkillID',
        'Rate',
    ];

    /**
     * Get the user that owns the skill
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'Id');
    }

    /**
     * Get the skill that belongs to the user
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'SkillID');
    }
}
