<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $table = 'academic_years';
    
    protected $fillable = [
        'year_label',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Relasi ke Enrollments
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
