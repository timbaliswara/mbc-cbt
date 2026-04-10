<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['name', 'phone', 'school', 'grade'];

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
