<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasFactory;

    protected $table = 'Jobs';

    protected $fillable = [
        'Title',
        'Description',
        'location',
        'Salary',
        'LocationType',
        'EmployeeType',
        'status',
        'CompanyID',
        'Experience',
        'CreationDate',
        'ModificationDate',
        'ModifiedBy',
        'CreatedBy',
    ];

    protected $casts = [
        'CreationDate' => 'datetime',
        'ModificationDate' => 'datetime',
        'status' => 'boolean',
    ];

    /**
     * Get the company that owns the job
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'CompanyID', 'ID');
    }

    /**
     * Scope for open jobs
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for jobs by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('CompanyID', $companyId);
    }
}
