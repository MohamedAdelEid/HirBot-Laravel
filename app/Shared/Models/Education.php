<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'Educations';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'InstituationName',
        'FieldOfStudy',
        'Start_Date',
        'End_Date',
        'degree',
        'Type',
        'Privacy',
        'UserID',
        'isGraduated',
        'logo',
        'CreationDate',
        'ModificationDate',
        'ModifiedBy',
        'CreatedBy',
    ];

    protected $casts = [
        'ID' => 'integer',
        'Type' => 'integer',
        'Privacy' => 'integer',
        'isGraduated' => 'boolean',
        'CreationDate' => 'datetime',
        'ModificationDate' => 'datetime',
    ];

    /**
     * Get the user that owns the education
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'Id');
    }
}
