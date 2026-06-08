<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrPaymentReceipt extends Model
{
    use HasFactory;

    protected $table = 'ocr_payment_receipts';

    protected $fillable = [
        'payment_id',
        'student_id',
        'uploaded_by',
        'file_path',
        'amount',
        'paid_at',
        'bank_name',
        'sender_name',
        'reference_no',
        'ocr_raw_text',
        'ocr_confidence',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount' => 'decimal:2',
        'ocr_confidence' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
