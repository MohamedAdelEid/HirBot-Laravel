<?php

namespace App\Shared\Models;

use App\Shared\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    use HasFactory;

    protected $table = 'Experiences';

    protected $fillable = [
        'Title',
        'UserID',
        'workType',
        'employeeType',
        'location',
        'CompanyID',
        'companyName',
        'Position',
        'Start_Date',
        'End_Date',
        'IsStill',
        'privacy',
        'rate',
    ];

    protected $casts = [
        'Start_Date' => 'date',
        'End_Date' => 'date',
        'IsStill' => 'boolean',
    ];

    public function scopeCurrentlyWorking($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('End_Date')
            ->orWhere('IsStill', true)
            ->orWhere('End_Date', '>', now());
        });
    }

    /**
     * Get the user that owns the experience
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID', 'Id');
    }

    /**
     * Get the company associated with the experience
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'CompanyID' , 'ID');
    }
}
