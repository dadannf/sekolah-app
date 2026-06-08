<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'paid_at',
        'amount',
        'method',
        'status',
            'ocr_status',
        'reference_no',
        'bank_name',
        'proof_path',
        'place_paid',
        'note',
        'received_by',
        'received_by_name',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship to SppInvoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SppInvoice::class, 'invoice_id');
    }

    /**
     * Relationship to User (who received the payment)
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get student through invoice
     */
    public function getStudentAttribute()
    {
        return $this->invoice?->student;
    }

    /**
     * Relationship to OcrPaymentReceipt
     */
    public function ocrReceipt()
    {
        return $this->hasOne(OcrPaymentReceipt::class, 'payment_id');
    }

    /**
     * Accessor for proof URL
     */
    public function getProofUrlAttribute()
    {
        if (!$this->proof_path) {
            return null;
        }

        return route('payment.proof.show', [$this->id, basename($this->proof_path)]);
    }

    /**
     * Accessor for status badge
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'verified' => 'success',
            'pending' => 'warning',
            'submitted' => 'warning',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

        /**
         * Accessor for OCR status label
         */
        public function getOcrStatusLabelAttribute()
        {
            if (!$this->ocr_status) {
                return 'N/A';
            }
        
            return match($this->ocr_status) {
                'success' => 'OCR Berhasil',
                'unavailable' => '⚠️ OCR Tidak Tersedia',
                'failed' => '❌ OCR Gagal Validasi',
                default => 'Tidak Diketahui',
            };
        }

        /**
         * Accessor for OCR status badge color
         */
        public function getOcrStatusBadgeAttribute()
        {
            return match($this->ocr_status) {
                'success' => 'success',
                'unavailable' => 'warning',
                'failed' => 'danger',
                default => 'secondary',
            };
        }

    /**
     * Accessor for method label
     */
    public function getMethodLabelAttribute()
    {
        return match($this->method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qr' => 'QR Code',
            default => ucfirst($this->method),
        };
    }
}
