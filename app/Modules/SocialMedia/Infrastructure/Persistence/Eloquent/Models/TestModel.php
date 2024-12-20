<?php

namespace App\Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $table = 'test';
    protected $fillable = ['name'];
}