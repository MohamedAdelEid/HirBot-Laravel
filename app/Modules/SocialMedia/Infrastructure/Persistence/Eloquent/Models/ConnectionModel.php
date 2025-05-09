<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ConnectionModel extends Model
{
    use HasFactory;

    protected $table = 'connections';

    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
        'type',
    ];

    protected $casts = [
        'status' => ConnectionStatusEnum::class,
        'type' => ConnectionTypeEnum::class,
    ];

    /**
     * Get the requester user.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id', 'Id');
    }

    /**
     * Get the receiver user.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id', 'Id');
    }

    /**
     * Scope to get connected users
     *
     * @param Builder $query
     * @param string $userId
     * @return Builder
     */
    public function scopeConnectedTo($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
                $q->where('requester_id', $userId)
                ->orWhere('receiver_id', $userId);
            })
            ->where("type" , ConnectionTypeEnum::CONNECTION)
            ->where('status', ConnectionStatusEnum::ACCEPTED);
    }

    /**
     * Scope to get companies that the user follows
     *
     * @param Builder $query
     * @param string $userId
     * @return Builder
     */
    public function scopeFollowedCompanies($query, $userId)
    {
        return $query->where('requester_id', $userId)
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED);
    }
}
