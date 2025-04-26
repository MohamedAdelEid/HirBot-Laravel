<?php

namespace App\Shared\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Shared\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = "users";
    protected $primaryKey = "Id";
    public $incrementing = false;

    protected $fillable = [
        'Id',
        'name',
        'username',
        'email',
        'password',
        'profile_image',
    ];

    protected $appends = [
        'is_company',
        'is_admin',
        'is_user',
    ];

    public function getIsCompanyAttribute(): bool
    {
        return $this->role === UserRoleEnum::COMPANY;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === UserRoleEnum::ADMIN;
    }

    public function getIsUserAttribute(): bool
    {
        return $this->role === UserRoleEnum::USER;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRoleEnum::class,
        ];
    }

    /**
     * Connections where this user is the requester.
     */
    public function requestedConnections(): HasMany
    {
        return $this->hasMany(ConnectionModel::class, 'requester_id', 'Id');
    }

    /**
     * Connections where this user is the receiver.
     */
    public function receivedConnections(): HasMany
    {
        return $this->hasMany(ConnectionModel::class, 'receiver_id', 'Id');
    }

    /**
     * All connections (requested + received).
     */
    public function allConnections()
    {
        return $this->requestedConnections->merge($this->receivedConnections);
    }

    /**
     * Get the user's company information.
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'UserID', 'Id');
    }

    /**
     * Get the user's portfolio information.
     */
    public function portfolio(): HasOne
    {
        return $this->hasOne(Portfolio::class, 'UserID', 'Id');
    }

    public function interactions()
    {
        return $this->hasMany(InteractionModel::class);
    }


}
