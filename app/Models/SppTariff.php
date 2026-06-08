<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppTariff extends Model
{
    use HasFactory;

    protected $table = 'spp_tariffs';

    protected $fillable = [
        'grade_level',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'amount' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to SppInvoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SppInvoice::class, 'tariff_id');
    }

    /**
     * Accessor for formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Accessor for grade label
     */
    public function getGradeLabelAttribute(): string
    {
        return 'Kelas ' . $this->grade_level;
    }

    /**
     * Scope for active tariffs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific grade
     */
    public function scopeForGrade($query, int $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }
}
