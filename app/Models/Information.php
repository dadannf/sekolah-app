<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Information extends Model
{
    protected $table = 'information';

    protected $fillable = [
        'title',
        'body',
        'image_path',
        'info_type',
        'is_active',
        'target_class',
        'target_student_id',
        'created_by_user_id',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke user pembuat
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relasi ke student target
     */
    public function targetStudent()
    {
        return $this->belongsTo(Student::class, 'target_student_id');
    }

    /**
     * Scope untuk informasi yang aktif
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            });
    }

    /**
     * Scope untuk informasi berdasarkan student
     */
    public function scopeForStudent($query, $studentId, $class = null)
    {
        return $query->where(function ($q) use ($studentId, $class) {
            // Info untuk semua siswa (tidak ada target)
            $q->where(function ($sub) {
                $sub->whereNull('target_class')
                    ->whereNull('target_student_id');
            })
            // Atau info untuk kelas tertentu
            ->orWhere(function ($sub) use ($class) {
                if ($class) {
                    $sub->where('target_class', $class);
                }
            })
            // Atau info khusus untuk siswa ini
            ->orWhere('target_student_id', $studentId);
        });
    }

    /**
     * Scope filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('info_type', $type);
    }

    /**
     * Check apakah informasi sedang aktif
     */
    public function isCurrentlyActive()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Check start_at
        if ($this->start_at && $this->start_at > $now) {
            return false;
        }

        // Check end_at
        if ($this->end_at && $this->end_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * Get status label
     */
    public function getStatusLabel()
    {
        if (!$this->is_active) {
            return 'Nonaktif';
        }

        $now = now();

        if ($this->start_at && $this->start_at > $now) {
            return 'Terjadwal';
        }

        if ($this->end_at && $this->end_at < $now) {
            return 'Kadaluwarsa';
        }

        return 'Aktif';
    }

    /**
     * Get target description
     */
    public function getTargetDescription()
    {
        if ($this->target_student_id) {
            return 'Siswa: ' . ($this->targetStudent->name ?? 'N/A');
        }

        if ($this->target_class) {
            return 'Kelas: ' . $this->target_class;
        }

        return 'Semua Siswa';
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }
        return asset('storage/' . $this->image_path);
    }
}
