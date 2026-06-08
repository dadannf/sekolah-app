<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    
    protected $fillable = [
        'user_id',
        'nis',
        'nisn',
        'name',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'current_grade_level',
        'major',
        'national_id_number',
        'religion',
        'hobby',
        'aspiration',
        'father_name',
        'mother_name',
        'address',
        'email',
        'phone_number',
        'student_status',
        'photo_path'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Append accessors to JSON
     */
    protected $appends = [
        'photo_url',
        'gender_label',
        'full_birth',
        'grade_display'
    ];
    
    /**
     * Include relationships in JSON
     */
    protected $with = ['user'];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke SPP Invoices
     */
    public function sppInvoices()
    {
        return $this->hasMany(\App\Models\SppInvoice::class);
    }

    /**
     * Get display kelas berdasarkan current_grade_level
     */
    public function getGradeDisplayAttribute()
    {
        if ($this->current_grade_level) {
            return 'Kelas ' . $this->current_grade_level;
        }
        return null;
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }
        
        // Default avatar based on gender
        if ($this->gender === 'F') {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=ec4899&color=fff&size=200&bold=true';
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=3b82f6&color=fff&size=200&bold=true';
    }

    /**
     * Get gender label
     */
    public function getGenderLabelAttribute()
    {
        if (!$this->gender) {
            return '-';
        }
        return $this->gender === 'M' ? 'Laki-laki' : 'Perempuan';
    }

    /**
     * Get full birthplace and date
     */
    public function getFullBirthAttribute()
    {
        if ($this->place_of_birth && $this->date_of_birth) {
            return $this->place_of_birth . ', ' . $this->date_of_birth->format('d F Y');
        }
        
        return '-';
    }
}
