<?php

namespace App\Shared\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class NotificationReceiver extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'NotificationRecivers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ReciverID',
        'NotificationID',
        'read_at',
        'CreationDate',
        'ModificationDate',
        'ModifiedBy',
        'CreatedBy',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'read_at' => 'datetime',
        'CreationDate' => 'datetime',
        'ModificationDate' => 'datetime',
    ];

    /**
     * Get the notification that owns the receiver.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'NotificationID', 'ID');
    }

    /**
     * Get the user that owns the receiver.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ReciverID', 'Id');
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CreationDate = now();
            $model->CreatedBy = auth()->Id() ?? 'system';
        });

        static::updating(function ($model) {
            $model->ModificationDate = now();
            $model->ModifiedBy = auth()->Id() ?? 'system';
        });
    }
}
