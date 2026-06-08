<!-- 
    OCR Fallback Payment System - View Component Example
    
    Usage in admin payment verification dashboard:
    Include this in your payment verification view to display OCR status
-->

@if($payment->method === 'transfer')
    <!-- OCR Status Section -->
    <div class="ocr-status-section mb-3">
        <h6 class="border-bottom pb-2">
            <i class="fas fa-robot"></i> Status Validasi OCR
        </h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Status OCR:</label>
                    @if($payment->ocr_status === null)
                        <span class="badge badge-secondary">Tidak Tersedia (Cash)</span>
                    @elseif($payment->ocr_status === 'success')
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> OCR Berhasil
                        </span>
                        <small class="text-muted d-block mt-1">
                            Sistem OCR berhasil memvalidasi bukti transfer
                        </small>
                    @elseif($payment->ocr_status === 'unavailable')
                        <span class="badge badge-warning">
                            <i class="fas fa-exclamation-triangle"></i> OCR Tidak Tersedia
                        </span>
                        <small class="text-muted d-block mt-1">
                            Layanan OCR tidak aktif saat pembayaran disubmit
                        </small>
                    @elseif($payment->ocr_status === 'failed')
                        <span class="badge badge-danger">
                            <i class="fas fa-times-circle"></i> OCR Gagal Validasi
                        </span>
                        <small class="text-muted d-block mt-1">
                            Sistem OCR menemukan potensi masalah pada bukti
                        </small>
                    @endif
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Submission:</label>
                    <p class="form-control-plaintext">
                        {{ $payment->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert untuk admin action -->
        @if($payment->ocr_status === 'unavailable')
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-info-circle"></i> 
                <strong>Perhatian:</strong> OCR service tidak tersedia saat pembayaran ini disubmit.
                Silakan lakukan verifikasi manual dengan hati-hati:
                <ul class="mb-0 mt-2">
                    <li>Periksa nama bank sesuai (BRI)</li>
                    <li>Verifikasi tanggal transfer</li>
                    <li>Validasi jumlah nominal</li>
                    <li>Cek nomor rekening tujuan</li>
                </ul>
            </div>
        @elseif($payment->ocr_status === 'failed')
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> 
                <strong>Masalah Terdeteksi:</strong> OCR menemukan ketidaksesuaian dengan data pembayaran.
                Silakan review dengan teliti sebelum mengapprove:
                <ul class="mb-0 mt-2">
                    <li>Kemungkinan ada ketidaksesuaian nominal</li>
                    <li>Tanggal transfer mungkin berbeda</li>
                    <li>Nama bank atau rekening tidak sesuai</li>
                </ul>
            </div>
        @elseif($payment->ocr_status === 'success')
            <div class="alert alert-info" role="alert">
                <i class="fas fa-check-circle"></i> 
                <strong>OCR Berhasil:</strong> Sistem OCR telah memvalidasi data pembayaran.
                Data sesuai dengan dokumen bukti transfer.
            </div>
        @endif
    </div>
    
    <!-- Bukti Transfer Preview -->
    <div class="proof-section mb-3">
        <h6 class="border-bottom pb-2">
            <i class="fas fa-image"></i> Bukti Transfer
        </h6>
        
        @if($payment->proof_path)
            <div class="proof-preview">
                <img src="{{ $payment->proof_url }}" 
                     alt="Bukti Transfer" 
                     class="img-fluid rounded" 
                     style="max-width: 400px;">
                <p class="text-muted small mt-2">
                    Klik gambar untuk memperbesar
                </p>
            </div>
        @else
            <p class="text-muted">Tidak ada bukti transfer</p>
        @endif
    </div>
    
    <!-- Payment Details -->
    <div class="payment-details-section">
        <h6 class="border-bottom pb-2">
            <i class="fas fa-receipt"></i> Detail Pembayaran
        </h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Bank Tujuan:</label>
                    <p class="form-control-plaintext">{{ $payment->bank_name ?? '-' }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nominal:</label>
                    <p class="form-control-plaintext">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Bayar:</label>
                    <p class="form-control-plaintext">{{ $payment->paid_at->format('d/m/Y') }}</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Referensi/Catatan:</label>
                    <p class="form-control-plaintext">{{ $payment->reference_no ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons (for your existing verify/reject buttons) -->
    <div class="action-section mt-4 border-top pt-3">
        <div class="btn-group">
            <button type="button" class="btn btn-success" onclick="verifyPayment({{ $payment->id }})">
                <i class="fas fa-check"></i> Terima & Verifikasi
            </button>
            <button type="button" class="btn btn-danger" onclick="rejectPayment({{ $payment->id }})">
                <i class="fas fa-times"></i> Tolak
            </button>
        </div>
    </div>
@else
    <!-- For CASH payments, show simplified status -->
    <div class="alert alert-info">
        <i class="fas fa-money-bill"></i>
        Pembayaran Cash - Status OCR: Tidak Berlaku
    </div>
@endif

<style>
.ocr-status-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #0c5460;
}

.proof-preview {
    text-align: center;
    padding: 10px;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.proof-preview img {
    cursor: pointer;
    transition: transform 0.2s;
}

.proof-preview img:hover {
    transform: scale(1.05);
}

.payment-details-section {
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
</style>

<script>
function verifyPayment(paymentId) {
    if (confirm('Apakah Anda yakin ingin memverifikasi pembayaran ini?')) {
        fetch(`/dashboard/keuangan/payment/${paymentId}/verify`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pembayaran berhasil diverifikasi!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memverifikasi pembayaran');
        });
    }
}

function rejectPayment(paymentId) {
    const reason = prompt('Alasan penolakan:');
    if (reason) {
        fetch(`/dashboard/keuangan/payment/${paymentId}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ note: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pembayaran berhasil ditolak!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menolak pembayaran');
        });
    }
}
</script>
