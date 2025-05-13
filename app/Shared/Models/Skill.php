<?php

namespace App\Shared\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $table = 'Skills';
    protected $CREATED_AT = 'CreationDate';
    protected $UPDATED_AT = 'ModificationDate';

    protected $fillable = [
        'Name',
        'Status',
        'ImagePath',
    ];

    /**
     * Get the users who have this skill
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'UserSkills', 'SkillID', 'UserID')
            ->withPivot('Rate')
            ->withTimestamps();
    }

    /**
     * Get the user skills pivot records
     */
    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class, 'SkillID');
    }
}
