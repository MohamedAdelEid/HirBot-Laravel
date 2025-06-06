<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $table = 'Companies';

    protected $fillable = [
        'user_id',
        'CompanyType',
        'description',
        'website',
        'founded_year',
        'size',
        'industry',
    ];

    /**
     * Get the user that owns the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'Id');
    }

        /**
     * Get all jobs posted by this company
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'CompanyID', 'ID');
    }

    /**
     * Get open jobs for this company
     */
    public function openJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'CompanyID', 'ID')->open();
    }

    /**
     * Get employees currently working at this company
     */
    public function currentEmployees(): HasMany
    {
        return $this->hasMany(Experience::class, 'CompanyID', 'ID')->currentlyWorking();
    }

    /**
     * Get followers of this company
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'connections', 'receiver_id', 'requester_id', 'UserID', 'Id')
            ->where('type', \App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum::FOLLOW)
            ->where('status', \App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum::ACCEPTED);
    }

    /**
     * Get full location string
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([$this->street, $this->Governate, $this->country]);
        return implode(', ', $parts);
    }

    /**
     * Scope to exclude companies already followed by user
     */
    public function scopeNotFollowedBy($query, $userId)
    {
        return $query->whereDoesntHave('user.followers', function ($followQuery) use ($userId) {
            $followQuery->where('requester_id', $userId);
        });
    }
}
