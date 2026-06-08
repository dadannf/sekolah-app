<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $table = 'grades';
    
    protected $fillable = [
        'grade_code'
    ];

    /**
     * Relasi ke Enrollments
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
