<?php

namespace App\Shared\Models;

use App\Shared\Enums\NotifiableTypeEnum;
use App\Shared\Enums\NotificationActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'Notifications';

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
        'type',
        'Notifiable_Type',
        'Notifiable_ID',
        'massage',
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
        'type' => NotificationActionEnum::class,
        'Notifiable_Type' => NotifiableTypeEnum::class,
        'CreationDate' => 'datetime',
        'ModificationDate' => 'datetime',
    ];

    /**
     * Get the notifiable entity that the notification belongs to.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo(
            'notifiable',
            'Notifiable_Type',
            'Notifiable_ID'
        );
    }

    /**
     * Get the notification receivers for this notification.
     */
    public function receivers(): HasMany
    {
        return $this->hasMany(NotificationReceiver::class, 'NotificationID', 'ID');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CreationDate = now();
            $model->CreatedBy = Auth::user()->Id ?? 'system';
        });

        static::updating(function ($model) {
            $model->ModificationDate = now();
            $model->ModifiedBy = Auth::user()->Id ?? 'system';
        });
    }
}
