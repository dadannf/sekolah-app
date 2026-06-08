<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SppInvoice extends Model
{
    use HasFactory;

    protected $table = 'spp_invoices';

    protected $fillable = [
        'student_id',
        'invoice_year',
        'invoice_month',
        'grade_level_at_invoice',
        'tariff_id',
        'amount_due',
        'due_date',
        'status',
    ];

    protected $casts = [
        'invoice_year' => 'integer',
        'invoice_month' => 'integer',
        'grade_level_at_invoice' => 'integer',
        'amount_due' => 'integer',
        'due_date' => 'date',
    ];

    /**
     * Relationship to Student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relationship to SppTariff
     */
    public function tariff(): BelongsTo
    {
        return $this->belongsTo(SppTariff::class, 'tariff_id');
    }

    /**
     * Relationship to Payment (one invoice can have one payment)
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'invoice_id');
    }

    /**
     * Accessor for month name
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$this->invoice_month] ?? '';
    }

    /**
     * Accessor for status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'paid' => 'Lunas',
            'unpaid' => 'Belum Bayar',
            'overdue' => 'Terlambat',
            default => ucfirst($this->status),
        };
    }

    /**
     * Accessor for status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'paid' => 'success',
            'unpaid' => 'warning',
            'overdue' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || 
               (!$this->isPaid() && $this->due_date < now());
    }
}
