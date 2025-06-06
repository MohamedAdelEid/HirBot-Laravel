<?php

namespace App\Shared\Models;

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\ConnectionModel;
use App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\InteractionModel;
use App\Shared\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'CurentJopID'
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
     * Users that this user is following
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'connections', 'requester_id', 'receiver_id')
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED);
    }

    /**
     * Users that are following this user
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'connections', 'receiver_id', 'requester_id')
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED);
    }

    /**
     * All follow relationships for this user
     */
    public function allFollows(): HasMany
    {
        return $this->hasMany(ConnectionModel::class, 'requester_id')
            ->orWhere('receiver_id', $this->Id)
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED);
    }

    /**
     * Companies that this user follows
     */
    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'connections', 'requester_id', 'receiver_id')
            ->where('type', ConnectionTypeEnum::FOLLOW)
            ->where('status', ConnectionStatusEnum::ACCEPTED)
            ->whereHas('company'); // Only users who have companies
    }

    /**
     * Get the user's company information.
     */
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'UserID', 'Id');
    }

    public function portfolio(): HasOne
    {
        return $this->hasOne(Portfolio::class, 'UserID', 'Id');
    }

    public function interactions()
    {
        return $this->hasMany(InteractionModel::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class, 'UserID', 'Id');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class, 'UserID', 'Id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'UserSkills', 'UserID', 'SkillID')
                    ->withPivot('Rate');
    }

    public function currentExperience(): HasOne
    {
        return $this->hasOne(Experience::class, 'ID', 'CurentJopID')->where('IsStill', true);
    }

    /**
     * Scope to get users with connections working at a specific company
     */
    public function scopeConnectedToCompany($query, $companyId, $userId)
    {
        return $query->whereHas('experiences', function ($expQuery) use ($companyId) {
                $expQuery->where('CompanyID', $companyId)->currentlyWorking();
            })
            ->whereHas('requestedConnections', function ($connQuery) use ($userId) {
                $connQuery->where('receiver_id', $userId)
                    ->where('type', ConnectionTypeEnum::CONNECTION)
                    ->where('status', ConnectionStatusEnum::ACCEPTED);
            })
            ->orWhereHas('receivedConnections', function ($connQuery) use ($userId) {
                $connQuery->where('requester_id', $userId)
                    ->where('type', ConnectionTypeEnum::CONNECTION)
                    ->where('status', ConnectionStatusEnum::ACCEPTED);
            });
    }
}
