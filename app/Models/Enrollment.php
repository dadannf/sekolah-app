<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $table = 'enrollments';
    
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'grade_id',
        'status'
    ];

    /**
     * Relasi ke Student
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke Academic Year
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relasi ke Grade
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
