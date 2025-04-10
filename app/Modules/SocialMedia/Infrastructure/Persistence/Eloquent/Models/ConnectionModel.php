<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
