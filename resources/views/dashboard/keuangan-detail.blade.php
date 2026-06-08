@extends('layouts.app')

@section('title', 'Detail Keuangan Siswa - Dashboard Sekolah')
@section('page-title', 'Detail Keuangan Siswa')
@section('page-subtitle', 'Record pembayaran bulanan tahun ' . ($payment->invoice_year ?? date('Y')))

@section('content')
<div class="mb-4">
    <!-- Header Actions -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-2 gap-sm-0 mb-3 mb-md-4">
        <a href="{{ route('dashboard.keuangan') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
        <a href="{{ route('dashboard.keuangan.detail.print', ['id' => $payment->student_id, 'year' => $payment->invoice_year]) }}" target="_blank" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Cetak Laporan
        </a>
    </div>

    <!-- Student Info Card -->
    <div class="bg-white rounded-4 shadow-sm p-3 p-md-4 mb-3 mb-md-4 border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <div class="row g-2 g-md-3">
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">NIS</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">{{ $payment->student->nis }}</p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Nama Siswa</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">{{ $payment->student->name }}</p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Kelas</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">
                    @if($payment->student->current_grade_level)
                        Kelas {{ $payment->student->current_grade_level }}
                    @else
                        -
                    @endif
                </p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Jurusan</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">{{ $payment->student->major ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <!-- Total Tagihan -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Total Tagihan</p>
                <h3 class="text-white fw-bold mb-1 fs-6 fs-md-4">Rp {{ number_format($payment->total_bill, 0, ',', '.') }}</h3>
                <p class="text-white small mb-0 d-none d-md-block" style="opacity: 0.8; font-size: 0.7rem;">12 Bulan x Rp {{ number_format($sppPerBulan, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Terbayar -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #198754 0%, #157347 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Terbayar</p>
                <h3 class="text-white fw-bold mb-1 fs-6 fs-md-4 total-paid-amount">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</h3>
                <p class="text-white small mb-0 d-none d-md-block total-paid-months" style="opacity: 0.8; font-size: 0.7rem;">{{ floor($payment->total_paid / $sppPerBulan) }} dari 12 bulan</p>
            </div>
        </div>

        <!-- Tunggakan -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #fb2424d0 0%, #f50b0be1 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Tunggakan</p>
                <h3 class="text-white fw-bold mb-1 fs-6 fs-md-4 remaining-amount">Rp {{ number_format($payment->remaining, 0, ',', '.') }}</h3>
                <p class="text-white small mb-0 d-none d-md-block remaining-months" style="opacity: 0.8; font-size: 0.7rem;">{{ 12 - floor($payment->total_paid / $sppPerBulan) }} bulan tersisa</p>
            </div>
        </div>

        <!-- Progress -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Progress</p>
                <h3 class="text-white fw-bold mb-2 mb-md-3 fs-6 fs-md-4 percentage-text">{{ $payment->payment_percentage }}%</h3>
                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.3);">
                    <div class="progress-bar bg-white" role="progressbar" style="width: {{ $payment->payment_percentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Payments Table -->
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <!-- Table Header -->
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%); border-bottom: 2px solid rgba(59, 130, 246, 0.15);">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                <i class="fas fa-calendar-alt me-2" style="color: #3b82f6;"></i>
                Pembayaran Bulanan (12 Bulan)
            </h5>
        </div>

        <!-- Desktop Table -->
        <div class="d-none d-lg-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: rgba(59, 130, 246, 0.03);">
                        <tr>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">No</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Bulan</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tagihan</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Metode</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tempat Bayar</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Status</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Bukti TF</th>
                            <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyPayments as $monthly)
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <td class="text-dark">{{ $monthly['no'] }}</td>
                            <td class="fw-semibold text-dark">{{ $monthly['month'] }}</td>
                            <td class="text-dark">Rp {{ number_format($monthly['amount'], 0, ',', '.') }}</td>
                            <td>
                                @if($monthly['method'])
                                    <span class="badge px-3 py-2" style="background: {{ $monthly['method'] == 'TUNAI' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-weight: 600;">
                                        {{ $monthly['method'] }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-dark">
                                @if($monthly['location'])
                                    @if($monthly['location'] == 'Data kosong')
                                        <span class="text-danger">{{ $monthly['location'] }}</span>
                                    @else
                                        {{ $monthly['location'] }}
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php $status = $monthly['status']; @endphp
                                @if($status === 'verified')
                                    <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="badge px-3 py-2 bg-warning text-dark fw-semibold">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($status === 'rejected')
                                    <span class="badge px-3 py-2 bg-danger text-white fw-semibold">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 bg-secondary text-white">
                                        <i class="fas fa-clock me-1"></i>Belum
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($monthly['proof'] == 'Pratinjau' && $monthly['proof_url'])
                                    <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ $monthly['proof_url'] }}', {{ $monthly['payment_id'] ?? 'null' }})">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @elseif($monthly['proof'])
                                    <span class="text-muted">{{ $monthly['proof'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!$monthly['payment_id'] || $monthly['status'] === 'rejected')
                                    <button class="btn btn-sm btn-primary px-3" 
                                            onclick="openPaymentModal('{{ $monthly['month'] }}', {{ $monthly['amount'] }}, {{ $monthly['no'] }})">
                                        <i class="fas fa-plus me-1"></i>Bayar
                                    </button>
                                @elseif($monthly['status'] === 'pending')
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($monthly['payment_id']), 'verify')">
                                            <i class="fas fa-check me-1"></i>Verify
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($monthly['payment_id']), 'reject')">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </div>
                                @elseif($monthly['status'] === 'verified')
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('payment.receipt', $monthly['payment_id']) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                            <i class="fas fa-print me-1"></i>Cetak
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick='handlePaymentDelete({{ $monthly['payment_id'] }}, {!! json_encode('bulan '.$monthly['month']) !!})'>
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Cards -->
        <div class="d-lg-none p-3">
            <div class="d-flex flex-column gap-3">
                @foreach($monthlyPayments as $monthly)
                @php
                    $status = $monthly['status'] ?? 'unpaid';
                    if ($status === 'submitted') {
                        $status = 'pending';
                    }
                @endphp
                <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
                    <!-- Card Header -->
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $status === 'verified' ? '#198754 0%, #157347 100%' : ($status === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($status === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#3b82f6 0%, #1d4ed8 100%')) }});">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-white text-dark px-2 py-1 me-2" style="font-size: 0.7rem;">{{ $monthly['no'] }}</span>
                                <span class="text-white fw-bold" style="font-size: 0.95rem;">{{ $monthly['month'] }}</span>
                            </div>
                            @if($status === 'verified')
                                <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                            @elseif($status === 'pending')
                                <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                            @elseif($status === 'rejected')
                                <i class="fas fa-times-circle text-white" style="font-size: 1.1rem;"></i>
                            @else
                                <i class="fas fa-clock text-white" style="font-size: 1.1rem;"></i>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-3 bg-white">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tagihan</p>
                                <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($monthly['amount'], 0, ',', '.') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Status</p>
                                @if($status === 'verified')
                                    <span class="badge px-2 py-1" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-size: 0.75rem;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="badge px-2 py-1 bg-warning text-dark" style="font-size: 0.75rem;">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($status === 'rejected')
                                    <span class="badge px-2 py-1 bg-danger text-white" style="font-size: 0.75rem;">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-2 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                        <i class="fas fa-clock me-1"></i>Belum
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($monthly['method'])
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Metode</p>
                                <span class="badge px-2 py-1" style="background: {{ $monthly['method'] == 'TUNAI' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-size: 0.75rem;">
                                    {{ $monthly['method'] }}
                                </span>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tempat Bayar</p>
                                <p class="mb-0" style="font-size: 0.85rem;">
                                    @if($monthly['location'])
                                        @if($monthly['location'] == 'Data kosong')
                                            <span class="text-danger small">{{ $monthly['location'] }}</span>
                                        @else
                                            <span class="text-dark fw-semibold small">{{ $monthly['location'] }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($monthly['proof'])
                        <div class="mb-2">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                            @if($monthly['proof'] == 'Pratinjau' && $monthly['proof_url'])
                                <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ $monthly['proof_url'] }}', {{ $monthly['payment_id'] ?? 'null' }})">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @else
                                <span class="text-muted small">{{ $monthly['proof'] }}</span>
                            @endif
                        </div>
                        @endif

                        <!-- Action Button -->
                        <div class="d-grid mt-3 pt-2 border-top">
                            @if(!$monthly['payment_id'] || $monthly['status'] === 'rejected')
                                <button class="btn btn-sm btn-primary" 
                                        onclick="openPaymentModal('{{ $monthly['month'] }}', {{ $monthly['amount'] }}, {{ $monthly['no'] }})">
                                    <i class="fas fa-plus me-2"></i>Bayar Sekarang
                                </button>
                            @elseif($monthly['status'] === 'pending')
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($monthly['payment_id']), 'verify')">
                                        <i class="fas fa-check me-1"></i>Verify
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($monthly['payment_id']), 'reject')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            @elseif($monthly['status'] === 'verified')
                                <div class="d-grid gap-2">
                                    <a href="{{ route('payment.receipt', $monthly['payment_id']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-2"></i>Cetak Kwitansi
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick='handlePaymentDelete({{ $monthly['payment_id'] }}, {!! json_encode('bulan '.$monthly['month']) !!})'>
                                        <i class="fas fa-trash me-2"></i>Hapus Pembayaran
                                    </button>
                                </div>
                            @else
                                <span class="text-muted small">Tidak ada aksi</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Additional Payment Status Tables -->
@if($additionalPayments && count($additionalPayments) > 0)
<div class="mt-4">
    <!-- Seragam Payments Table -->
    @if(isset($additionalPayments['seragam']))
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <!-- Table Header -->
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(0, 169, 224, 0.08) 0%, rgba(2, 132, 199, 0.08) 100%); border-bottom: 2px solid rgba(0, 169, 224, 0.15);">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                <i class="fas fa-shirt me-2" style="color: #0ea5e9;"></i>
                Pembayaran Seragam
            </h5>
        </div>

        <!-- Table (md+) -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background: rgba(0, 169, 224, 0.03);">
                    <tr>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Jenis</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Nominal</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Metode</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tempat Bayar</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Status</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Bukti TF</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($additionalPayments['seragam']['details'] as $type => $detail)
                    @php
                        $payment = $detail['payment'] ?? null;
                        $status = $payment ? ($payment->status ?? 'unpaid') : 'unpaid';
                        if ($status === 'submitted') {
                            $status = 'pending';
                        }
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" data-payment-id="{{ $payment ? $payment->id : '' }}">
                        <td class="fw-semibold text-dark">{{ $detail['name'] }}</td>
                        <td class="text-dark">Rp {{ number_format($detail['cost'], 0, ',', '.') }}</td>
                        <td>
                            @if($payment && $payment->method)
                                <span class="badge px-3 py-2" style="background: {{ $payment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-weight: 600;">
                                    {{ strtoupper($payment->method) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-dark">
                            @if($payment && ($payment->bank_name || $payment->place_paid))
                                {{ $payment->bank_name ?? $payment->place_paid }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="status-badge">
                                @if($status === 'verified')
                                    <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="badge px-3 py-2 bg-warning text-dark fw-semibold">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($status === 'rejected')
                                    <span class="badge px-3 py-2 bg-danger text-white fw-semibold">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 bg-secondary text-white">
                                        <i class="fas fa-clock me-1"></i>Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($payment && $payment->proof_path && $status !== 'rejected')
                                <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$payment->id, basename($payment->proof_path)]) }}', {{ $payment->id }})">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @elseif($payment && $status === 'rejected')
                                <span class="text-muted">Ditolak</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if(!$payment || $status === 'rejected')
                                <div class="d-flex align-items-center gap-2">
                                    <input class="form-check-input uniform-table-checkbox" type="checkbox" value="{{ $type }}" data-cost="{{ (int) ($detail['cost'] ?? 0) }}" aria-label="Pilih {{ $detail['name'] }}" style="margin-top: 0;">
                                    <button type="button" class="btn btn-sm btn-primary px-3" onclick="openUniformPaymentFromTable('{{ $type }}')">
                                        <i class="fas fa-plus me-1"></i>Bayar
                                    </button>
                                </div>
                            @elseif($status === 'pending')
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($payment->id), 'verify')">
                                        <i class="fas fa-check me-1"></i>Verify
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($payment->id), 'reject')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            @elseif($status === 'verified')
                                <div class="d-flex gap-1">
                                    <a href="{{ route('payment.receipt', $payment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick='handlePaymentDelete({{ $payment->id }}, {!! json_encode($detail['name']) !!})'>
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Footer Action (md+) -->
        <div class="d-none d-md-flex justify-content-end align-items-center gap-2 px-3 px-md-4 py-3" style="border-top: 1px solid rgba(0,0,0,0.06); background: rgba(0, 169, 224, 0.02);">
            <small class="text-muted">Centang seragam, lalu klik Bayar</small>
            <button type="button" class="btn btn-sm btn-primary" onclick="openUniformPaymentFromTable(null)">
                <i class="fas fa-check-square me-1"></i>Bayar yang dipilih
            </button>
        </div>

        <!-- Mobile Cards (sm) -->
        <div class="d-md-none p-3">
            <div class="d-flex flex-column gap-3">
                @foreach($additionalPayments['seragam']['details'] as $type => $detail)
                @php
                    $payment = $detail['payment'] ?? null;
                    $status = $payment ? ($payment->status ?? 'unpaid') : 'unpaid';
                    if ($status === 'submitted') {
                        $status = 'pending';
                    }
                @endphp
                <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(0, 169, 224, 0.18) !important;">
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $status === 'verified' ? '#198754 0%, #157347 100%' : ($status === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($status === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#0ea5e9 0%, #0284c7 100%')) }});">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-white fw-bold" style="font-size: 0.95rem;">
                                {{ $detail['name'] }}
                            </div>
                            @if($status === 'verified')
                                <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                            @elseif($status === 'pending')
                                <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                            @elseif($status === 'rejected')
                                <i class="fas fa-times-circle text-white" style="font-size: 1.1rem;"></i>
                            @else
                                <i class="fas fa-clock text-white" style="font-size: 1.1rem;"></i>
                            @endif
                        </div>
                    </div>
                    <div class="p-3 bg-white">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Nominal</p>
                                <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($detail['cost'], 0, ',', '.') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Status</p>
                                @if($status === 'verified')
                                    <span class="badge px-2 py-1" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-size: 0.75rem;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="badge px-2 py-1 bg-warning text-dark" style="font-size: 0.75rem;">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($status === 'rejected')
                                    <span class="badge px-2 py-1 bg-danger text-white" style="font-size: 0.75rem;">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-2 py-1 bg-secondary text-white" style="font-size: 0.75rem;">
                                        <i class="fas fa-clock me-1"></i>Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Metode</p>
                                @if($payment && $payment->method)
                                    <span class="badge px-2 py-1" style="background: {{ $payment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-size: 0.75rem;">
                                        {{ strtoupper($payment->method) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tempat Bayar</p>
                                <p class="mb-0" style="font-size: 0.85rem;">
                                    @if($payment && ($payment->bank_name || $payment->place_paid))
                                        <span class="text-dark fw-semibold small">{{ $payment->bank_name ?? $payment->place_paid }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="mb-2">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                            @if($payment && $payment->proof_path && $status !== 'rejected')
                                <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$payment->id, basename($payment->proof_path)]) }}', {{ $payment->id }})">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @elseif($payment && $status === 'rejected')
                                <span class="text-muted small">Ditolak</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </div>

                        <div class="d-grid mt-3 pt-2 border-top">
                            @if(!$payment || $status === 'rejected')
                                <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                    <div class="form-check m-0">
                                        <input class="form-check-input uniform-table-checkbox" type="checkbox" value="{{ $type }}" data-cost="{{ (int) ($detail['cost'] ?? 0) }}" id="uniform_check_{{ $type }}" style="margin-top: 0.2rem;">
                                        <label class="form-check-label text-dark" for="uniform_check_{{ $type }}" style="font-size: 0.85rem;">
                                            Pilih
                                        </label>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $detail['name'] }}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" onclick="openUniformPaymentFromTable('{{ $type }}')">
                                    <i class="fas fa-plus me-2"></i>Bayar
                                </button>
                            @elseif($status === 'pending')
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($payment->id), 'verify')">
                                        <i class="fas fa-check me-1"></i>Verify
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($payment->id), 'reject')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            @elseif($status === 'verified')
                                <div class="d-grid gap-2">
                                    <a href="{{ route('payment.receipt', $payment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-2"></i>Cetak
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick='handlePaymentDelete({{ $payment->id }}, {!! json_encode($detail['name']) !!})'>
                                        <i class="fas fa-trash me-2"></i>Hapus
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-3 pt-2 border-top">
                <button type="button" class="btn btn-primary w-100" onclick="openUniformPaymentFromTable(null)">
                    <i class="fas fa-check-square me-2"></i>Bayar yang dipilih
                </button>
                <small class="text-muted d-block text-center mt-2" style="font-size: 0.75rem;">Centang seragam yang ingin dibayar</small>
            </div>
        </div>
    </div>
    @endif

    <!-- PTS Payments Table -->
    @if(isset($additionalPayments['pts']))
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <!-- Table Header -->
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(217, 119, 6, 0.08) 100%); border-bottom: 2px solid rgba(245, 158, 11, 0.15);">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                <i class="fas fa-book-open me-2" style="color: #f59e0b;"></i>
                Pembayaran PTS
            </h5>
        </div>

        <!-- Table (md+) -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background: rgba(245, 158, 11, 0.03);">
                    <tr>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tipe</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Nominal</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Metode</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tempat Bayar</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Status</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Bukti TF</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $ptsPayment = isset($additionalPayments['pts']['payment']) ? $additionalPayments['pts']['payment'] : null; @endphp
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" data-payment-id="{{ $ptsPayment ? $ptsPayment->id : '' }}">
                        <td class="fw-semibold text-dark">PTS</td>
                        <td class="text-dark">Rp {{ number_format($additionalPayments['pts']['total_bill'], 0, ',', '.') }}</td>
                        <td>
                            @if($ptsPayment && $ptsPayment->method)
                                <span class="badge px-3 py-2" style="background: {{ $ptsPayment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-weight: 600;">
                                    {{ strtoupper($ptsPayment->method) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-dark">
                            @if($ptsPayment && ($ptsPayment->bank_name || $ptsPayment->place_paid))
                                {{ $ptsPayment->bank_name ?? $ptsPayment->place_paid }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="status-badge">
                                @php
                                    $ptsStatus = $ptsPayment ? ($ptsPayment->status ?? 'unpaid') : 'unpaid';
                                    if ($ptsStatus === 'submitted') {
                                        $ptsStatus = 'pending';
                                    }
                                @endphp
                                @if($ptsStatus === 'verified')
                                    <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($ptsStatus === 'pending')
                                    <span class="badge px-3 py-2 bg-warning text-dark fw-semibold">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($ptsStatus === 'rejected')
                                    <span class="badge px-3 py-2 bg-danger text-white fw-semibold">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 bg-secondary text-white">
                                        <i class="fas fa-clock me-1"></i>Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($ptsPayment && $ptsPayment->proof_path && $ptsStatus !== 'rejected')
                                <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$ptsPayment->id, basename($ptsPayment->proof_path)]) }}', {{ $ptsPayment->id }})">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @elseif($ptsPayment && $ptsStatus === 'rejected')
                                <span class="text-muted">Ditolak</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if(!$ptsPayment || $ptsStatus === 'rejected')
                                <button type="button" class="btn btn-sm btn-primary px-3" onclick="openPaymentModalForAdditional('pts', 'PTS', {{ $additionalPayments['pts']['total_bill'] }})">
                                    <i class="fas fa-plus me-1"></i>Bayar
                                </button>
                            @elseif($ptsStatus === 'pending')
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($ptsPayment->id), 'verify')">
                                        <i class="fas fa-check me-1"></i>Verify
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($ptsPayment->id), 'reject')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            @elseif($ptsStatus === 'verified')
                                <div class="d-flex gap-1">
                                    <a href="{{ route('payment.receipt', $ptsPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick='handlePaymentDelete({{ $ptsPayment->id }}, "PTS")'>
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card (sm) -->
        <div class="d-md-none p-3">
            @php $ptsPayment = isset($additionalPayments['pts']['payment']) ? $additionalPayments['pts']['payment'] : null; @endphp
            @php
                $ptsStatus = $ptsPayment ? ($ptsPayment->status ?? 'unpaid') : 'unpaid';
                if ($ptsStatus === 'submitted') {
                    $ptsStatus = 'pending';
                }
            @endphp
            <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(245, 158, 11, 0.22) !important;">
                <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $ptsStatus === 'verified' ? '#198754 0%, #157347 100%' : ($ptsStatus === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($ptsStatus === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#f59e0b 0%, #d97706 100%')) }});">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white fw-bold" style="font-size: 0.95rem;">PTS</div>
                        @if($ptsStatus === 'verified')
                            <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                        @elseif($ptsStatus === 'pending')
                            <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                        @elseif($ptsStatus === 'rejected')
                            <i class="fas fa-times-circle text-white" style="font-size: 1.1rem;"></i>
                        @else
                            <i class="fas fa-clock text-white" style="font-size: 1.1rem;"></i>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-white">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Nominal</p>
                            <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($additionalPayments['pts']['total_bill'], 0, ',', '.') }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Metode</p>
                            @if($ptsPayment && $ptsPayment->method)
                                <span class="badge px-2 py-1" style="background: {{ $ptsPayment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-size: 0.75rem;">
                                    {{ strtoupper($ptsPayment->method) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tempat Bayar</p>
                            @if($ptsPayment && ($ptsPayment->bank_name || $ptsPayment->place_paid))
                                <span class="text-dark fw-semibold small">{{ $ptsPayment->bank_name ?? $ptsPayment->place_paid }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-2">
                        <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                        @if($ptsPayment && $ptsPayment->proof_path && $ptsStatus !== 'rejected')
                            <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$ptsPayment->id, basename($ptsPayment->proof_path)]) }}', {{ $ptsPayment->id }})">
                                <i class="fas fa-eye me-1"></i>Pratinjau
                            </button>
                        @elseif($ptsPayment && $ptsStatus === 'rejected')
                            <span class="text-muted small">Ditolak</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </div>

                    <div class="d-grid mt-3 pt-2 border-top">
                        @if(!$ptsPayment || $ptsStatus === 'rejected')
                            <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModalForAdditional('pts', 'PTS', {{ $additionalPayments['pts']['total_bill'] }})">
                                <i class="fas fa-plus me-2"></i>Bayar
                            </button>
                        @elseif($ptsStatus === 'pending')
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($ptsPayment->id), 'verify')">
                                    <i class="fas fa-check me-1"></i>Verify
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($ptsPayment->id), 'reject')">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                            </div>
                        @elseif($ptsStatus === 'verified')
                            <div class="d-grid gap-2">
                                <a href="{{ route('payment.receipt', $ptsPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-2"></i>Cetak
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick='handlePaymentDelete({{ $ptsPayment->id }}, "PTS")'>
                                    <i class="fas fa-trash me-2"></i>Hapus
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- PAS Payments Table -->
    @if(isset($additionalPayments['pas']))
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <!-- Table Header -->
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.08) 100%); border-bottom: 2px solid rgba(239, 68, 68, 0.15);">
            <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                <i class="fas fa-scroll me-2" style="color: #ef4444;"></i>
                Pembayaran PAS
            </h5>
        </div>

        <!-- Table (md+) -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background: rgba(239, 68, 68, 0.03);">
                    <tr>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tipe</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Nominal</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Metode</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Tempat Bayar</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Status</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Bukti TF</th>
                        <th class="py-3 fw-bold text-secondary" style="font-size: 0.85rem;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $pasPayment = isset($additionalPayments['pas']['payment']) ? $additionalPayments['pas']['payment'] : null; @endphp
                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" data-payment-id="{{ $pasPayment ? $pasPayment->id : '' }}">
                        <td class="fw-semibold text-dark">PAS</td>
                        <td class="text-dark">Rp {{ number_format($additionalPayments['pas']['total_bill'], 0, ',', '.') }}</td>
                        <td>
                            @if($pasPayment && $pasPayment->method)
                                <span class="badge px-3 py-2" style="background: {{ $pasPayment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-weight: 600;">
                                    {{ strtoupper($pasPayment->method) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-dark">
                            @if($pasPayment && ($pasPayment->bank_name || $pasPayment->place_paid))
                                {{ $pasPayment->bank_name ?? $pasPayment->place_paid }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="status-badge">
                                @php
                                    $pasStatus = $pasPayment ? ($pasPayment->status ?? 'unpaid') : 'unpaid';
                                    if ($pasStatus === 'submitted') {
                                        $pasStatus = 'pending';
                                    }
                                @endphp
                                @if($pasStatus === 'verified')
                                    <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                @elseif($pasStatus === 'pending')
                                    <span class="badge px-3 py-2 bg-warning text-dark fw-semibold">
                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                    </span>
                                @elseif($pasStatus === 'rejected')
                                    <span class="badge px-3 py-2 bg-danger text-white fw-semibold">
                                        <i class="fas fa-times-circle me-1"></i>Rejected
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 bg-secondary text-white">
                                        <i class="fas fa-clock me-1"></i>Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($pasPayment && $pasPayment->proof_path && $pasStatus !== 'rejected')
                                <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$pasPayment->id, basename($pasPayment->proof_path)]) }}', {{ $pasPayment->id }})">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @elseif($pasPayment && $pasStatus === 'rejected')
                                <span class="text-muted">Ditolak</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if(!$pasPayment || $pasStatus === 'rejected')
                                <button type="button" class="btn btn-sm btn-primary px-3" onclick="openPaymentModalForAdditional('pas', 'PAS', {{ $additionalPayments['pas']['total_bill'] }})">
                                    <i class="fas fa-plus me-1"></i>Bayar
                                </button>
                            @elseif($pasStatus === 'pending')
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($pasPayment->id), 'verify')">
                                        <i class="fas fa-check me-1"></i>Verify
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($pasPayment->id), 'reject')">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            @elseif($pasStatus === 'verified')
                                <div class="d-flex gap-1">
                                    <a href="{{ route('payment.receipt', $pasPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3" onclick='handlePaymentDelete({{ $pasPayment->id }}, "PAS")'>
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card (sm) -->
        <div class="d-md-none p-3">
            @php $pasPayment = isset($additionalPayments['pas']['payment']) ? $additionalPayments['pas']['payment'] : null; @endphp
            @php
                $pasStatus = $pasPayment ? ($pasPayment->status ?? 'unpaid') : 'unpaid';
                if ($pasStatus === 'submitted') {
                    $pasStatus = 'pending';
                }
            @endphp
            <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(239, 68, 68, 0.22) !important;">
                <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $pasStatus === 'verified' ? '#198754 0%, #157347 100%' : ($pasStatus === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($pasStatus === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#ef4444 0%, #dc2626 100%')) }});">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-white fw-bold" style="font-size: 0.95rem;">PAS</div>
                        @if($pasStatus === 'verified')
                            <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                        @elseif($pasStatus === 'pending')
                            <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                        @elseif($pasStatus === 'rejected')
                            <i class="fas fa-times-circle text-white" style="font-size: 1.1rem;"></i>
                        @else
                            <i class="fas fa-clock text-white" style="font-size: 1.1rem;"></i>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-white">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Nominal</p>
                            <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($additionalPayments['pas']['total_bill'], 0, ',', '.') }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Metode</p>
                            @if($pasPayment && $pasPayment->method)
                                <span class="badge px-2 py-1" style="background: {{ $pasPayment->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-size: 0.75rem;">
                                    {{ strtoupper($pasPayment->method) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tempat Bayar</p>
                            @if($pasPayment && ($pasPayment->bank_name || $pasPayment->place_paid))
                                <span class="text-dark fw-semibold small">{{ $pasPayment->bank_name ?? $pasPayment->place_paid }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-2">
                        <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                        @if($pasPayment && $pasPayment->proof_path && $pasStatus !== 'rejected')
                            <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$pasPayment->id, basename($pasPayment->proof_path)]) }}', {{ $pasPayment->id }})">
                                <i class="fas fa-eye me-1"></i>Pratinjau
                            </button>
                        @elseif($pasPayment && $pasStatus === 'rejected')
                            <span class="text-muted small">Ditolak</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </div>

                    <div class="d-grid mt-3 pt-2 border-top">
                        @if(!$pasPayment || $pasStatus === 'rejected')
                            <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModalForAdditional('pas', 'PAS', {{ $additionalPayments['pas']['total_bill'] }})">
                                <i class="fas fa-plus me-2"></i>Bayar
                            </button>
                        @elseif($pasStatus === 'pending')
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-success" onclick="handlePaymentAction(@json($pasPayment->id), 'verify')">
                                    <i class="fas fa-check me-1"></i>Verify
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="handlePaymentAction(@json($pasPayment->id), 'reject')">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                            </div>
                        @elseif($pasStatus === 'verified')
                            <div class="d-grid gap-2">
                                <a href="{{ route('payment.receipt', $pasPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-2"></i>Cetak
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick='handlePaymentDelete({{ $pasPayment->id }}, "PAS")'>
                                    <i class="fas fa-trash me-2"></i>Hapus
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Modal Pembayaran SPP -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%);">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="paymentModalLabel">
                        <i class="fas fa-money-bill-wave me-2" style="color: #3b82f6; font-size: 0.9rem;"></i>
                        Pembayaran SPP
                    </h6>
                    <p class="text-secondary mb-0" style="font-size: 0.75rem;" id="paymentMonthInfo">Bulan: -</p>
                </div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-3 py-3 position-relative" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                <!-- OCR Loading Overlay (shown while waiting for OCR) -->
                <div id="ocrOverlay" class="position-absolute top-0 start-0 end-0 bottom-0 d-none align-items-center justify-content-center" style="z-index: 10;">
                    <div class="bg-white border rounded-3 p-3 w-100 mx-2" style="max-width: 520px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Memproses OCR…</div>
                                <div class="text-muted" style="font-size: 0.8rem;">Mohon tunggu, jangan tutup form.</div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <form id="paymentForm" method="POST" action="/dashboard/keuangan/payment/store" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="student_id" name="student_id" value="{{ $payment->student_id ?? ($student->id ?? '') }}">
                    <input type="hidden" id="invoice_year" name="invoice_year" value="{{ $payment->invoice_year ?? date('Y') }}">
                    <input type="hidden" id="invoice_type" name="invoice_type" value="spp">
                    <input type="hidden" id="uniform_type" name="uniform_type" value="">
                    <div id="uniformTypesContainer" class="d-none"></div>
                    <input type="hidden" id="payment_month" name="payment_month">
                    
                    <!-- Tanggal Bayar -->
                    <div class="mb-3">
                        <label for="paid_at" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-calendar me-1 text-primary" style="font-size: 0.75rem;"></i>Tanggal Bayar
                            <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control form-control-sm rounded-2 border-2" 
                               id="paid_at" name="paid_at" 
                               value="{{ \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required
                               style="font-size: 0.85rem;">
                    </div>

                    <!-- Nominal Bayar -->
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-money-bill me-1 text-success" style="font-size: 0.75rem;"></i>Nominal Bayar
                            <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-2" style="font-size: 0.85rem;">Rp</span>
                            <input type="text" class="form-control border-2 rounded-end-2" 
                                   id="amount" name="amount" 
                                   placeholder="0" required
                                   style="font-size: 0.85rem;">
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Format: 380.000 atau 380000</small>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-2" style="font-size: 0.85rem;">
                            <i class="fas fa-credit-card me-1 text-info" style="font-size: 0.75rem;"></i>Metode Pembayaran
                            <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="method" id="method_cash" value="cash" checked>
                                <label class="btn btn-outline-primary w-100 py-2 rounded-2 border-2" for="method_cash" style="font-size: 0.8rem;">
                                    <i class="fas fa-money-bill-wave d-block mb-1" style="font-size: 1.1rem;"></i>
                                    <strong>TUNAI</strong>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="method" id="method_transfer" value="transfer">
                                <label class="btn btn-outline-primary w-100 py-2 rounded-2 border-2" for="method_transfer" style="font-size: 0.8rem;">
                                    <i class="fas fa-university d-block mb-1" style="font-size: 1.1rem;"></i>
                                    <strong>TRANSFER</strong>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Conditional Fields: TUNAI -->
                    <div id="cash_fields" class="conditional-fields">
                        <div class="p-3 rounded-2 mb-2" style="background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.2);">
                            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">
                                <i class="fas fa-money-bill-wave me-1 text-cyan" style="font-size: 0.75rem;"></i>Informasi Pembayaran Tunai
                            </h6>
                            
                            <!-- Tempat Bayar -->
                            <div class="mb-2">
                                <label for="place_paid" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.8rem;">
                                    Tempat Bayar <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm rounded-2 border-2" id="place_paid" name="place_paid" style="font-size: 0.8rem;">
                                    <option value="Ruang TU" selected>Ruang TU</option>
                                </select>
                            </div>

                            <!-- Diterima Oleh -->
                            <div class="mb-2">
                                <label for="received_by_user_id" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.8rem;">
                                    Diterima Oleh <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm rounded-2 border-2" id="received_by_user_id" name="received_by_user_id" style="font-size: 0.8rem;">
                                    <option value="">-- Pilih Petugas --</option>
                                    @foreach(\App\Models\User::whereIn('role', ['admin', 'kepala_sekolah'])->get() as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Catatan -->
                            <div class="mb-0">
                                <label for="note" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.8rem;">
                                    Catatan <small class="text-muted">(Opsional)</small>
                                </label>
                                <textarea class="form-control form-control-sm rounded-2 border-2" 
                                          id="note" name="note" 
                                          rows="2" 
                                          placeholder="Catatan tambahan..."
                                          style="font-size: 0.8rem;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Conditional Fields: TRANSFER -->
                    <div id="transfer_fields" class="conditional-fields" style="display: none;">
                        <div class="p-3 rounded-2 mb-2" style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
                            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">
                                <i class="fas fa-university me-1 text-primary" style="font-size: 0.75rem;"></i>Informasi Transfer Bank
                            </h6>
                            
                            <!-- Bank Tujuan -->
                            <div class="mb-2">
                                <label for="bank_name" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.8rem;">
                                    Bank Tujuan <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm rounded-2 border-2" id="bank_name" name="bank_name" style="font-size: 0.8rem;">
                                    <option value="BRI" selected>BRI (Bank Rakyat Indonesia)</option>
                                </select>
                            </div>

                            <!-- Upload Bukti Transfer -->
                            <div class="mb-0">
                                <label class="form-label fw-semibold text-dark mb-1" style="font-size: 0.8rem;">
                                    Bukti Transfer <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex gap-2 mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="uploadFileBtn" style="font-size: 0.75rem;">
                                        <i class="fas fa-upload me-1" style="font-size: 0.7rem;"></i>Upload File
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info flex-fill" id="capturePhotoBtn" style="font-size: 0.75rem;">
                                        <i class="fas fa-camera me-1" style="font-size: 0.7rem;"></i>Ambil Foto
                                    </button>
                                </div>
                                
                                <!-- File Input (Hidden) -->
                                <input type="file" class="d-none" id="proof_path" name="proof_path" 
                                       accept="image/*">
                                
                                <!-- Camera Input (Hidden) - NOT submitted with form -->
                                    <input type="file" class="visually-hidden" id="proof_camera" 
                                       accept="image/*" capture="environment">
                                
                                <!-- Preview Area -->
                                <div id="proofPreview" class="mt-2" style="display: none;">
                                    <div class="border rounded-2 p-2 bg-light">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <img id="proofImage" src="" alt="Preview" class="rounded" style="max-width: 60px; max-height: 60px; display: none;">
                                                <div id="proofFileInfo">
                                                    <p class="mb-0 fw-semibold text-dark" id="fileName" style="font-size: 0.8rem;"></p>
                                                    <small class="text-muted" id="fileSize" style="font-size: 0.7rem;"></small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-danger" id="removeProof" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- OCR Processing Status -->
                                <div id="ocrProcessing" class="mt-2" style="display: none;">
                                    <div class="alert alert-info mb-0 py-2 px-3" role="alert" style="font-size: 0.75rem;">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-spinner fa-spin me-2"></i>
                                                <span id="ocrProcessingText">Memproses bukti transfer dengan OCR...</span>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.7rem;">Mohon tunggu</small>
                                        </div>
                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- OCR Results -->
                                <div id="ocrResults" class="mt-2" style="display: none;">
                                    <div class="card" id="ocrResultCard">
                                        <div class="card-header py-2 px-3" id="ocrResultHeader">
                                            <h6 class="mb-0 fw-bold" style="font-size: 0.8rem;" id="ocrResultTitle">
                                                <i class="fas fa-robot me-1"></i>
                                                Hasil OCR (Confidence: <span id="ocrConfidence">0</span>%)
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- Validation Status Message -->
                                            <div id="ocrValidationStatus" style="display: none;"></div>
                                            
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Nominal</small>
                                                    <p class="mb-0 fw-semibold text-dark" id="ocrAmount" style="font-size: 0.8rem;">-</p>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Tanggal</small>
                                                    <p class="mb-0 fw-semibold text-dark" id="ocrDate" style="font-size: 0.8rem;">-</p>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Bank</small>
                                                    <p class="mb-0 fw-semibold text-dark" id="ocrBank" style="font-size: 0.8rem;">-</p>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">Penerima (Sekolah)</small>
                                                    <p class="mb-0 fw-semibold text-dark" id="ocrRecipient" style="font-size: 0.8rem;">-</p>
                                                </div>
                                                <div class="col-12">
                                                    <small class="text-muted d-block" style="font-size: 0.7rem;">No. Referensi</small>
                                                    <p class="mb-0 fw-semibold text-dark" id="ocrReference" style="font-size: 0.8rem;">-</p>
                                                </div>
                                            </div>
                                            
                                            <!-- Retry Upload Section (shown when validation fails) -->
                                            <div id="retryUploadSection" class="mt-3 pt-2 border-top" style="display: none;">
                                                <button type="button" class="btn btn-sm btn-warning w-100 mb-1" id="retryUploadBtn" style="font-size: 0.75rem;">
                                                    <i class="fas fa-redo me-1"></i>Upload Bukti Transfer Baru
                                                </button>
                                                <small class="text-muted d-block text-center" style="font-size: 0.65rem;">
                                                    <i class="fas fa-info-circle me-1"></i>Pastikan bukti transfer sesuai dengan data pembayaran
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                

                                
                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Format: JPG, PNG, PDF. Maks 5MB
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Alert OCR Gagal Terhubung - Posisi Paling Bawah Form -->
                    <div id="ocrFailedAlert" class="alert alert-warning mb-0 mt-3" style="display: none; font-size: 0.85rem;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1" style="font-size: 1rem;"></i>
                            <div>
                                <strong>Sistem OCR Tidak Terhubung</strong>
                                <p class="mb-0 mt-1" style="font-size: 0.8rem;">
                                    Pembayaran Anda akan tetap disimpan dan <strong>validasi akan dilakukan oleh admin</strong>. 
                                    Pastikan bukti transfer yang diupload sudah sesuai dengan data pembayaran.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-0 pt-2 px-3 pb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal" style="font-size: 0.8rem;">
                    <i class="fas fa-times me-1" style="font-size: 0.75rem;"></i>Batal
                </button>
                <button type="submit" form="paymentForm" class="btn btn-sm btn-primary px-3 rounded-2" id="submitPaymentBtn" style="font-size: 0.8rem;">
                    <i class="fas fa-check me-1" style="font-size: 0.75rem;"></i>Simpan Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pratinjau Bukti Transfer & Hasil OCR -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark" id="proofModalLabel">Pratinjau Bukti Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Left: Image Preview -->
                    <div class="col-md-7 p-3 bg-light d-flex align-items-center justify-content-center" style="min-height: 50vh;">
                        <div id="proofImageContainer" class="text-center w-100" style="display: none;">
                            <img id="proofImagePreview" src="" alt="Bukti Transfer" class="img-fluid rounded shadow-sm" style="max-height: 70vh; object-fit: contain;">
                        </div>
                        <div id="proofFrameContainer" class="w-100" style="display: none; height: 70vh;">
                            <iframe id="proofFramePreview" src="" style="width: 100%; height: 100%; border: none;" class="rounded shadow-sm"></iframe>
                        </div>
                    </div>
                    <!-- Right: OCR Results (if any) -->
                    <div class="col-md-5 p-4 border-start" id="ocrResultsContainer" style="display: none; max-height: 70vh; overflow-y: auto;">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                            <i class="fas fa-robot me-2"></i>Analisis OCR Sistem
                        </h6>
                        <div id="ocrLoading" class="text-center py-4" style="display: none;">
                            <div class="spinner-border text-primary spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="small text-muted mt-2">Mengambil data OCR...</p>
                        </div>
                        <div id="ocrContent" style="display: none;">
                            <!-- Confidence Score -->
                            <div class="mb-3 d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                <span class="small fw-semibold text-secondary">Akurasi Pembacaan:</span>
                                <span class="badge bg-success" id="ocrConfidence">100%</span>
                            </div>
                            
                            <!-- Strict Validation -->
                            <div class="mb-3">
                                <span class="small fw-semibold text-secondary d-block mb-2">Hasil Validasi Otomatis:</span>
                                <ul class="list-group list-group-flush small border rounded" id="ocrValidationList">
                                    <!-- Populated by JS -->
                                </ul>
                            </div>

                            <!-- Mapped Fields -->
                            <div class="mb-3">
                                <span class="small fw-semibold text-secondary d-block mb-2">Ekstraksi Data (Field Mapping):</span>
                                <div class="bg-light p-2 rounded small" style="font-family: monospace;" id="ocrMappedFields">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                            
                            <!-- Raw Text -->
                            <div class="mb-2">
                                <span class="small fw-semibold text-secondary d-block mb-1">Teks Mentah (Raw Text):</span>
                                <div class="bg-dark text-light p-2 rounded small overflow-auto" style="max-height: 150px; font-size: 0.7rem; white-space: pre-wrap;" id="ocrRawText"></div>
                            </div>
                        </div>
                        <div id="ocrError" class="alert alert-secondary py-2 small" style="display: none;">
                            Tidak ada data OCR untuk pembayaran ini.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ambil Foto (Kamera) -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark" id="cameraModalLabel">Ambil Foto Bukti Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary py-2 px-3" style="font-size: 0.8rem;" id="cameraHint">
                    Arahkan kamera ke struk/bukti transfer, lalu klik <strong>Ambil</strong>.
                </div>

                <div class="ratio ratio-4x3 bg-dark rounded overflow-hidden">
                    <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 100%; object-fit: contain;"></video>
                    <img id="cameraCaptured" alt="Hasil Foto" style="display:none; width: 100%; height: 100%; object-fit: contain;" />
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="cameraSwitchBtn">Balik Kamera</button>
                <button type="button" class="btn btn-sm btn-outline-info" id="cameraRetakeBtn" style="display:none;">Ulangi</button>
                <button type="button" class="btn btn-sm btn-primary" id="cameraCaptureBtn">Ambil</button>
                <button type="button" class="btn btn-sm btn-success" id="cameraUseBtn" style="display:none;">Gunakan Foto</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('[name=_token]')?.value || '';

    const proofImageContainer = document.getElementById('proofImageContainer');
    const proofFrameContainer = document.getElementById('proofFrameContainer');
    const proofImagePreview = document.getElementById('proofImagePreview');
    const proofFramePreview = document.getElementById('proofFramePreview');
    const proofModalEl = document.getElementById('proofModal');
    const proofModal = proofModalEl ? new bootstrap.Modal(proofModalEl) : null;

    // Payment Method Toggle
    const methodCash = document.getElementById('method_cash');
    const methodTransfer = document.getElementById('method_transfer');
    const cashFields = document.getElementById('cash_fields');
    const transferFields = document.getElementById('transfer_fields');
    
    function togglePaymentFields() {
        if (methodCash.checked) {
            cashFields.style.display = 'block';
            transferFields.style.display = 'none';
            // Required for cash
            document.getElementById('place_paid').required = true;
            document.getElementById('received_by_user_id').required = true;
            // Remove required for transfer
            document.getElementById('bank_name').required = false;
        } else {
            cashFields.style.display = 'none';
            transferFields.style.display = 'block';
            // Remove required for cash
            document.getElementById('place_paid').required = false;
            document.getElementById('received_by_user_id').required = false;
            // Required for transfer
            document.getElementById('bank_name').required = true;
        }
    }
    
    methodCash.addEventListener('change', togglePaymentFields);
    methodTransfer.addEventListener('change', togglePaymentFields);
    
    // Format currency input
    const amountInput = document.getElementById('amount');
    amountInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });
    
    // File upload handlers
    const uploadFileBtn = document.getElementById('uploadFileBtn');
    const capturePhotoBtn = document.getElementById('capturePhotoBtn');
    const proofPath = document.getElementById('proof_path');
    const proofCamera = document.getElementById('proof_camera');
    const proofPreview = document.getElementById('proofPreview');
    const proofImage = document.getElementById('proofImage');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeProof = document.getElementById('removeProof');

    function setOcrUiBusy(isBusy) {
        const ocrOverlay = document.getElementById('ocrOverlay');
        if (ocrOverlay) {
            ocrOverlay.classList.toggle('d-none', !isBusy);
            ocrOverlay.classList.toggle('d-flex', !!isBusy);
        }

        if (uploadFileBtn) uploadFileBtn.disabled = isBusy;
        if (capturePhotoBtn) capturePhotoBtn.disabled = isBusy;
        if (removeProof) removeProof.disabled = isBusy;

        const submitBtn = document.getElementById('submitPaymentBtn');
        if (submitBtn) submitBtn.disabled = isBusy;
    }

    // Camera modal elements
    const cameraModalEl = document.getElementById('cameraModal');
    const cameraModal = cameraModalEl ? new bootstrap.Modal(cameraModalEl) : null;
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraCaptured = document.getElementById('cameraCaptured');
    const cameraCaptureBtn = document.getElementById('cameraCaptureBtn');
    const cameraUseBtn = document.getElementById('cameraUseBtn');
    const cameraRetakeBtn = document.getElementById('cameraRetakeBtn');
    const cameraSwitchBtn = document.getElementById('cameraSwitchBtn');
    const uniformTypesContainer = document.getElementById('uniformTypesContainer');
    let pendingUniformTypes = [];

    function setUniformTypesInForm(types) {
        if (!uniformTypesContainer) return;
        uniformTypesContainer.innerHTML = '';
        (types || []).forEach(t => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'uniform_types[]';
            input.value = t;
            uniformTypesContainer.appendChild(input);
        });
    }

    // ===== UNIFORM MULTI-SELECT (TABLE CHECKBOX) =====
    function readUniformSelectionsFromTable() {
        const checks = document.querySelectorAll('.uniform-table-checkbox');
        const selected = [];
        checks.forEach(ch => {
            if (!ch || ch.disabled) return;
            if (!ch.checked) return;
            const type = String(ch.value || '').trim();
            const cost = parseInt(ch.getAttribute('data-cost') || '0', 10);
            if (!type) return;
            selected.push({ type, cost: isNaN(cost) ? 0 : cost });
        });
        return selected;
    }

    function getUniformCostFromTable(type) {
        const safeType = String(type || '').trim();
        if (!safeType) return 0;
        const checks = document.querySelectorAll('.uniform-table-checkbox');
        for (const ch of checks) {
            if (!ch) continue;
            if (String(ch.value) !== safeType) continue;
            const cost = parseInt(ch.getAttribute('data-cost') || '0', 10);
            return isNaN(cost) ? 0 : cost;
        }
        return 0;
    }

    window.openUniformPaymentFromTable = function(defaultType) {
        const selected = readUniformSelectionsFromTable();
        let types = selected.map(s => s.type);

        if (!types.length && defaultType) {
            const fallbackType = String(defaultType).trim();
            if (fallbackType) {
                const rowCheckbox = document.querySelector('.uniform-table-checkbox[value="' + CSS.escape(fallbackType) + '"]');
                if (rowCheckbox && !rowCheckbox.disabled) {
                    rowCheckbox.checked = true;
                }
                types = [fallbackType];
            }
        }

        if (!types.length) {
            alert('Pilih minimal 1 jenis seragam.');
            return;
        }

        pendingUniformTypes = types;
        const total = types.reduce((sum, t) => sum + (getUniformCostFromTable(t) || 0), 0);
        const label = types.length > 1 ? `Seragam (${types.length} Jenis)` : 'Seragam';

        openPaymentModalForAdditional('uniform', label, total, null);
    };

    let cameraStream = null;
    let capturedBlob = null;
    let isPreviewMirrored = false;
    let currentFacingMode = 'environment';

    function setCameraPreviewMirroring(shouldMirror) {
        isPreviewMirrored = !!shouldMirror;
        if (cameraVideo) {
            cameraVideo.style.transform = isPreviewMirrored ? 'scaleX(-1)' : 'none';
        }
        if (cameraCaptured) {
            cameraCaptured.style.transform = 'none';
        }
    }
    
    async function openCameraModalIfSupported() {
        if (!cameraModal || !cameraVideo || !cameraCaptured) return false;
        if (!proofPath) return false;
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') return false;
        if (typeof DataTransfer === 'undefined' || typeof File === 'undefined') return false;

        if (!window.isSecureContext) {
            alert('Fitur Ambil Foto butuh akses HTTPS atau http://localhost agar kamera bisa dibuka. Saat ini halaman masih HTTP sehingga browser memblokir kamera.');
            return true;
        }

        capturedBlob = null;
        cameraCaptured.style.display = 'none';
        cameraVideo.style.display = 'block';
        if (cameraUseBtn) cameraUseBtn.style.display = 'none';
        if (cameraRetakeBtn) cameraRetakeBtn.style.display = 'none';
        if (cameraCaptureBtn) cameraCaptureBtn.style.display = 'inline-block';

        cameraModal.show();
        return true;
    }

    function openNativeCameraPicker() {
        if (!proofCamera) return;
        proofCamera.value = '';
        proofCamera.setAttribute('capture', 'environment');

        if (typeof proofCamera.showPicker === 'function') {
            proofCamera.showPicker();
            return;
        }

        proofCamera.click();
    }

    if (uploadFileBtn && proofPath) uploadFileBtn.addEventListener('click', () => proofPath.click());
    if (capturePhotoBtn) {
        capturePhotoBtn.addEventListener('click', async function() {
            const opened = await openCameraModalIfSupported();
            if (opened) return;
            openNativeCameraPicker();
        });
    }

    async function startCameraStream() {
        if (!cameraVideo) return;
        stopCameraStream();
        try {
            const tryGet = async (videoConstraints) => {
                return navigator.mediaDevices.getUserMedia({ video: videoConstraints, audio: false });
            };

            try {
                cameraStream = await tryGet({ facingMode: { ideal: currentFacingMode } });
            } catch (err) {
                cameraStream = await tryGet(true);
            }

            cameraVideo.srcObject = cameraStream;
            await cameraVideo.play();

            const track = cameraStream.getVideoTracks?.()[0];
            const settings = track && typeof track.getSettings === 'function' ? track.getSettings() : {};
            const detectedFacing = settings && settings.facingMode ? settings.facingMode : currentFacingMode;
            setCameraPreviewMirroring(detectedFacing === 'user');
        } catch (e) {
            if (e && (e.name === 'NotAllowedError' || e.name === 'SecurityError')) {
                alert('Akses kamera ditolak/diblokir. Silakan izinkan kamera di setting situs (Site settings) lalu coba lagi.');
                cameraModal?.hide();
                return;
            }
            alert('Gagal membuka kamera. Silakan coba lagi atau gunakan Upload File.');
            cameraModal?.hide();
        }
    }

    function stopCameraStream() {
        try {
            cameraStream?.getTracks()?.forEach(t => t.stop());
        } catch (e) {
            // ignore
        }
        cameraStream = null;
        if (cameraVideo) cameraVideo.srcObject = null;
        setCameraPreviewMirroring(false);
    }

    function resetCaptureUi() {
        capturedBlob = null;
        if (cameraCaptured) {
            try { URL.revokeObjectURL(cameraCaptured.src); } catch (e) {}
            cameraCaptured.src = '';
            cameraCaptured.style.display = 'none';
        }
        if (cameraVideo) cameraVideo.style.display = 'block';
        if (cameraUseBtn) cameraUseBtn.style.display = 'none';
        if (cameraRetakeBtn) cameraRetakeBtn.style.display = 'none';
        if (cameraCaptureBtn) cameraCaptureBtn.style.display = 'inline-block';
    }

    async function captureFromVideo() {
        if (!cameraVideo) return;
        const width = cameraVideo.videoWidth || 1280;
        const height = cameraVideo.videoHeight || 720;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        if (isPreviewMirrored) {
            ctx.translate(width, 0);
            ctx.scale(-1, 1);
        }
        ctx.drawImage(cameraVideo, 0, 0, width, height);

        const toJpeg = (c, q) => new Promise(resolve => c.toBlob(resolve, 'image/jpeg', q));
        let blob = await toJpeg(canvas, 0.85);
        if (!blob) return;

        if (blob.size > 5 * 1024 * 1024) {
            const maxWidth = 1600;
            let workingCanvas = canvas;
            if (width > maxWidth) {
                const scale = maxWidth / width;
                const scaledCanvas = document.createElement('canvas');
                scaledCanvas.width = Math.round(width * scale);
                scaledCanvas.height = Math.round(height * scale);
                const sctx = scaledCanvas.getContext('2d');
                if (sctx) {
                    sctx.drawImage(canvas, 0, 0, scaledCanvas.width, scaledCanvas.height);
                    workingCanvas = scaledCanvas;
                }
            }
            for (const q of [0.75, 0.65, 0.55]) {
                const candidate = await toJpeg(workingCanvas, q);
                if (candidate) blob = candidate;
                if (blob.size <= 5 * 1024 * 1024) break;
            }
            if (blob.size > 5 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 5MB');
                return;
            }
        }

        capturedBlob = blob;
        cameraCaptured.src = URL.createObjectURL(blob);
        cameraCaptured.style.display = 'block';
        cameraVideo.style.display = 'none';
        if (cameraUseBtn) cameraUseBtn.style.display = 'inline-block';
        if (cameraRetakeBtn) cameraRetakeBtn.style.display = 'inline-block';
        if (cameraCaptureBtn) cameraCaptureBtn.style.display = 'none';
    }

    function useCapturedPhoto() {
        if (!capturedBlob || !proofPath) return;
        const safeIso = new Date().toISOString().replace(/[:.]/g, '-');
        const file = new File([capturedBlob], `bukti-transfer-${safeIso}.jpg`, { type: capturedBlob.type || 'image/jpeg' });
        const dt = new DataTransfer();
        dt.items.add(file);
        proofPath.files = dt.files;
        proofPath.dispatchEvent(new Event('change', { bubbles: true }));
        cameraModal?.hide();
    }

    function retakePhoto() {
        resetCaptureUi();
    }

    if (cameraModalEl) {
        cameraModalEl.addEventListener('shown.bs.modal', startCameraStream);
        cameraModalEl.addEventListener('hidden.bs.modal', function() {
            retakePhoto();
            stopCameraStream();
        });
    }

    if (cameraCaptureBtn) cameraCaptureBtn.addEventListener('click', captureFromVideo);
    if (cameraUseBtn) cameraUseBtn.addEventListener('click', useCapturedPhoto);
    if (cameraRetakeBtn) cameraRetakeBtn.addEventListener('click', retakePhoto);
    if (cameraSwitchBtn) {
        cameraSwitchBtn.addEventListener('click', async function() {
            currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
            resetCaptureUi();
            await startCameraStream();
        });
    }
    
    function handleFileSelect(file) {
        if (file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file terlalu besar! Maksimal 5MB');
                return;
            }
            
            // Show preview
            proofPreview.style.display = 'block';
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
            
            // Show image preview if it's an image
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    proofImage.src = e.target.result;
                    proofImage.style.display = 'block';
                };
                reader.readAsDataURL(file);
                
                // Process with OCR
                processOCR(file);
            } else {
                proofImage.style.display = 'none';
            }
        }
    }
    
    // OCR Processing Function
    function processOCR(file) {
        // Hide previous results and alerts
        document.getElementById('ocrResults').style.display = 'none';
        const ocrFailedAlert = document.getElementById('ocrFailedAlert');
        if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
        
        // Show processing indicator
        document.getElementById('ocrProcessing').style.display = 'block';
        setOcrUiBusy(true);
        
        // Get form data for validation
        const amountInput = document.getElementById('amount');
        const paidAtInput = document.querySelector('input[name="paid_at"]');
        const bankSelect = document.getElementById('bank_name');
        
        // Prepare form data
        const formData = new FormData();
        formData.append('file', file);
        formData.append('student_id', '{{ $siswa->id ?? "" }}');
        formData.append('uploaded_by', '{{ auth()->id() ?? "" }}');
        
        // Add expected values for validation
        if (amountInput && amountInput.value) {
            const amount = parseFloat(amountInput.value.replace(/\./g, ''));
            formData.append('expected_amount', amount);
        }
        
        if (paidAtInput && paidAtInput.value) {
            formData.append('expected_date', paidAtInput.value);
        }
        
        if (bankSelect && bankSelect.value) {
            formData.append('expected_bank', bankSelect.value);
        }
        
        // Let the browser paint the loading UI before starting the request.
        requestAnimationFrame(() => {
            // Call OCR API
            fetch(@json(route('payment.ocr.process')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
        .then(async response => {
            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                // ignore JSON parse error; handled below
            }
            if (!response.ok) {
                throw { status: response.status, data };
            }
            return data;
        })
        .then(data => {
            // Hide processing
            document.getElementById('ocrProcessing').style.display = 'none';
            setOcrUiBusy(false);
            
            // Get submit button
            const submitBtn = document.getElementById('submitPaymentBtn');
            
            // Always show OCR results if extraction succeeded
            if (data.extracted_fields) {
                displayOCRResults(data);
                
                // Check validation status
                if (data.validation && data.validation.is_valid === false) {
                    document.getElementById('retryUploadSection').style.display = 'block';
                    if (submitBtn) submitBtn.style.display = 'inline-block';
                } else {
                    if (submitBtn) submitBtn.style.display = 'inline-block';
                    document.getElementById('retryUploadSection').style.display = 'none';
                }
            } else {
                // OCR extraction failed completely, but the payment can still be submitted for manual review.
                if (submitBtn) submitBtn.style.display = 'inline-block';
                document.getElementById('retryUploadSection').style.display = 'none';
                alert('⚠️ OCR Gagal mengekstrak data. Pembayaran tetap bisa disimpan dan akan divalidasi manual oleh admin.');
            }
        })
        .catch(error => {
            console.error('OCR Error:', error);
            document.getElementById('ocrProcessing').style.display = 'none';
            setOcrUiBusy(false);
            
            // OCR bersifat best-effort; kegagalan layanan tidak boleh memblokir pembayaran.
            console.log('OCR service tidak tersedia, pembayaran tetap bisa disimpan untuk validasi manual');

            // Tampilkan alert kuning di bawah form
            const ocrFailedAlert = document.getElementById('ocrFailedAlert');
            if (ocrFailedAlert) {
                ocrFailedAlert.style.display = 'block';
            }

            const submitBtn = document.getElementById('submitPaymentBtn');
            if (submitBtn) submitBtn.style.display = 'inline-block';
            document.getElementById('retryUploadSection').style.display = 'none';
        });
        });
    }
    
    // Display OCR Results
    function displayOCRResults(data) {
        const fields = data.extracted_fields;
        const confidence = data.confidence || 0;
        const validation = data.validation || {};
        const isValid = validation.is_valid !== false;
        
        // Get elements
        const card = document.getElementById('ocrResultCard');
        const header = document.getElementById('ocrResultHeader');
        const title = document.getElementById('ocrResultTitle');
        const validationStatus = document.getElementById('ocrValidationStatus');
        
        // Update confidence
        document.getElementById('ocrConfidence').textContent = confidence.toFixed(1);
        
        // Update fields
        document.getElementById('ocrAmount').textContent = 
            fields.amount ? 'Rp ' + Number(fields.amount).toLocaleString('id-ID') : '-';
        
        document.getElementById('ocrDate').textContent = 
            fields.paid_at ? new Date(fields.paid_at).toLocaleString('id-ID') : '-';
        
        document.getElementById('ocrBank').textContent = fields.bank_name || '-';
        
        // Prioritize recipient_name (penerima) over sender_name
        const recipientName = fields.recipient_name || fields.sender_name || '-';
        document.getElementById('ocrRecipient').textContent = recipientName;
        
        document.getElementById('ocrReference').textContent = fields.reference_no || '-';
        
        // Update card style based on validation
        if (isValid) {
            // Validation passed - Green
            card.className = 'card border-success';
            header.className = 'card-header py-2 px-3 bg-success text-white';
            title.className = 'mb-0 fw-bold text-white';
            title.innerHTML = '<i class="fas fa-check-circle me-1"></i>Hasil OCR - VALID (Confidence: <span id="ocrConfidence">' + confidence.toFixed(1) + '</span>%)';
            
            // Show warnings if any
            if (validation.warnings && validation.warnings.length > 0) {
                validationStatus.style.display = 'block';
                validationStatus.className = 'alert alert-warning py-2 px-3 mb-2';
                validationStatus.innerHTML = '<strong><i class="fas fa-exclamation-triangle me-1"></i>Peringatan:</strong><ul class="mb-0 mt-1 ps-3" style="font-size: 0.75rem;">' +
                    validation.warnings.map(w => '<li>' + w + '</li>').join('') + '</ul>';
            } else {
                validationStatus.style.display = 'none';
            }
        } else {
            // Validation failed - Red
            card.className = 'card border-danger';
            header.className = 'card-header py-2 px-3 bg-danger text-white';
            title.className = 'mb-0 fw-bold text-white';
            title.innerHTML = '<i class="fas fa-times-circle me-1"></i>Hasil OCR - TIDAK VALID (Confidence: <span id="ocrConfidence">' + confidence.toFixed(1) + '</span>%)';
            
            // Show errors
            if (validation.errors && validation.errors.length > 0) {
                validationStatus.style.display = 'block';
                validationStatus.className = 'alert alert-danger py-2 px-3 mb-2';
                validationStatus.innerHTML = '<strong><i class="fas fa-exclamation-circle me-1"></i>Validasi Gagal - Bukti Transfer Tidak Sesuai!</strong><ul class="mb-0 mt-1 ps-3" style="font-size: 0.75rem;">' +
                    validation.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
            }
        }
        
        // Show results
        document.getElementById('ocrResults').style.display = 'block';
    }
    
    proofPath.addEventListener('change', function(e) {
        handleFileSelect(e.target.files[0]);
    });
    
    proofCamera.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            handleFileSelect(e.target.files[0]);
            // Transfer the file to proofPath input for submission
            try {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(e.target.files[0]);
                proofPath.files = dataTransfer.files;
            } catch (error) {
                console.error('Error transferring file:', error);
                // Fallback: directly set the files
                proofPath.files = e.target.files;
            }
        }
    });
    
    removeProof.addEventListener('click', function() {
        proofPath.value = '';
        proofCamera.value = '';
        proofPreview.style.display = 'none';
        proofImage.src = '';
        
        // Hide OCR results
        document.getElementById('ocrResults').style.display = 'none';
        document.getElementById('retryUploadSection').style.display = 'none';
        
        // Hide OCR failed alert
        const ocrFailedAlert = document.getElementById('ocrFailedAlert');
        if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
        
        // Show submit button back
        const submitBtn = document.getElementById('submitPaymentBtn');
        if (submitBtn) submitBtn.style.display = 'inline-block';
    });
    
    // Retry Upload Button
    const retryUploadBtn = document.getElementById('retryUploadBtn');
    if (retryUploadBtn) {
        retryUploadBtn.addEventListener('click', function() {
            // Clear current file
            proofPath.value = '';
            proofCamera.value = '';
            
            // Hide results and alerts
            document.getElementById('ocrResults').style.display = 'none';
            document.getElementById('retryUploadSection').style.display = 'none';
            const ocrFailedAlert = document.getElementById('ocrFailedAlert');
            if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
            
            // Trigger file input
            proofPath.click();
        });
    }

    // Preview proof file
    // Preview proof file & fetch OCR
    window.handleProofPreview = function(url, paymentId = null) {
        if (!url || !proofModal) return;
        
        const isImage = /\.(jpe?g|png|gif|bmp|webp)$/i.test(url);
        proofImageContainer.style.display = isImage ? 'block' : 'none';
        proofFrameContainer.style.display = isImage ? 'none' : 'block';
        if (isImage) {
            proofImagePreview.src = url;
            proofFramePreview.src = '';
        } else {
            proofFramePreview.src = url;
            proofImagePreview.src = '';
        }

        // Handle OCR section
        const ocrContainer = document.getElementById('ocrResultsContainer');
        const ocrLoading = document.getElementById('ocrLoading');
        const ocrContent = document.getElementById('ocrContent');
        const ocrError = document.getElementById('ocrError');
        
        if (paymentId) {
            ocrContainer.style.display = 'block';
            ocrLoading.style.display = 'block';
            ocrContent.style.display = 'none';
            ocrError.style.display = 'none';
            
            // Adjust left column to col-md-7 when OCR is shown
            proofImageContainer.parentElement.className = "col-md-7 p-3 bg-light d-flex align-items-center justify-content-center";
            
            fetch(`/dashboard/keuangan/payment/${paymentId}/ocr-receipt`)
                .then(res => res.json())
                .then(res => {
                    ocrLoading.style.display = 'none';
                    if (res.success && res.data) {
                        ocrContent.style.display = 'block';
                        
                        // Populate confidence
                        const confScore = Math.round(res.data.ocr_confidence * 100);
                        const confBadge = document.getElementById('ocrConfidence');
                        confBadge.textContent = confScore + '%';
                        confBadge.className = confScore > 80 ? 'badge bg-success' : (confScore > 50 ? 'badge bg-warning text-dark' : 'badge bg-danger');
                        
                        // Populate raw text
                        document.getElementById('ocrRawText').textContent = res.data.ocr_raw_text;
                        
                        // Populate mapped fields
                        const mappedHtml = Object.entries(res.data.mapped_fields).map(([k, v]) => {
                            return `<div class="d-flex justify-content-between border-bottom border-light pb-1 mb-1">
                                <span class="text-muted">${k}</span>
                                <span class="fw-bold text-dark text-end">${v || '-'}</span>
                            </div>`;
                        }).join('');
                        document.getElementById('ocrMappedFields').innerHTML = mappedHtml;
                        
                        // Populate validation checks
                        const valChecks = res.data.validation_checks || {};
                        const valHtml = Object.entries(valChecks).map(([key, isValid]) => {
                            const icon = isValid ? '<i class="fas fa-check text-success me-2"></i>' : '<i class="fas fa-times text-danger me-2"></i>';
                            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            return `<li class="list-group-item px-2 py-1 bg-transparent border-0 d-flex align-items-center">
                                ${icon} ${label}
                            </li>`;
                        }).join('');
                        document.getElementById('ocrValidationList').innerHTML = valHtml;
                        
                    } else {
                        ocrError.style.display = 'block';
                    }
                })
                .catch(() => {
                    ocrLoading.style.display = 'none';
                    ocrError.style.display = 'block';
                });
        } else {
            // No payment ID => hide OCR section
            ocrContainer.style.display = 'none';
            proofImageContainer.parentElement.className = "col-12 p-3 bg-light d-flex align-items-center justify-content-center";
        }

        proofModal.show();
    };

    if (proofModalEl) {
        proofModalEl.addEventListener('hidden.bs.modal', function() {
            proofImagePreview.src = '';
            proofFramePreview.src = '';
            document.getElementById('ocrResultsContainer').style.display = 'none';
        });
    }
    
    // Open modal with payment info
    // Open modal untuk pembayaran additional types (Seragam, PTS, PAS) - Admin/Kepsek
    window.openAdditionalPaymentModal = function(invoiceType, label, amount) {
        // Untuk PTS dan PAS, langsung buka payment modal
        // Untuk Seragam juga langsung (pemilihan dilakukan via checkbox di tabel)
        openPaymentModalForAdditional(invoiceType, label, amount);
    };
    
    window.openPaymentModalForAdditional = function(invoiceType, label, amount, uniformType = null) {
        // Set form fields
        document.getElementById('invoice_type').value = invoiceType;
        if (uniformType) {
            document.getElementById('uniform_type').value = uniformType;
        }
        
        // Update modal title dan amount
        document.getElementById('paymentMonthInfo').textContent = 'Tipe: ' + label;
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('paymentModalLabel').innerHTML = '<i class="fas fa-money-bill-wave me-2" style="color: #3b82f6; font-size: 0.9rem;"></i>Pembayaran ' + label;
        
        // Reset form
        document.getElementById('paymentForm').reset();
        document.getElementById('paid_at').value = '{{ \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}';
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('invoice_type').value = invoiceType;
        // Uniform can be single or multi. Prefer pendingUniformTypes (multi).
        if (invoiceType === 'uniform') {
            document.getElementById('uniform_type').value = uniformType || '';
            if (pendingUniformTypes && pendingUniformTypes.length) {
                setUniformTypesInForm(pendingUniformTypes);
            } else {
                setUniformTypesInForm([]);
            }
        } else {
            setUniformTypesInForm([]);
            if (uniformType) {
                document.getElementById('uniform_type').value = uniformType;
            }
        }
        
        document.getElementById('payment_month').value = 1; // Default month untuk non-SPP
        proofPreview.style.display = 'none';
        document.getElementById('ocrResults').style.display = 'none';
        document.getElementById('retryUploadSection').style.display = 'none';
        const ocrFailedAlert = document.getElementById('ocrFailedAlert');
        if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
        const submitBtn = document.getElementById('submitPaymentBtn');
        if (submitBtn) submitBtn.style.display = 'inline-block';
        togglePaymentFields();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    };
    
    window.openPaymentModal = function(month, amount, monthNo) {
        console.log('Opening payment modal for month:', month, 'amount:', amount);
        document.getElementById('invoice_type').value = 'spp';
        document.getElementById('uniform_type').value = '';
        document.getElementById('paymentMonthInfo').textContent = 'Bulan: ' + month;
        document.getElementById('payment_month').value = monthNo;
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        
        // Reset form
        document.getElementById('paymentForm').reset();
        document.getElementById('paid_at').value = '{{ \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}';
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('payment_month').value = monthNo;
        proofPreview.style.display = 'none';
        document.getElementById('ocrResults').style.display = 'none';
        document.getElementById('retryUploadSection').style.display = 'none';
        const ocrFailedAlert = document.getElementById('ocrFailedAlert');
        if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
        const submitBtn = document.getElementById('submitPaymentBtn');
        if (submitBtn) submitBtn.style.display = 'inline-block';
        togglePaymentFields();
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    };
    
    // Debug button click
    const submitBtn = document.getElementById('submitPaymentBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            console.log('Submit button clicked!', e);
        });
    }
    
    // Form submission
    const paymentForm = document.getElementById('paymentForm');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Form submitted!');
            
            // Validate
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                console.log('Form validation failed');
                return;
            }
            
            // Check if transfer method requires proof
            if (methodTransfer.checked) {
                if (!proofPath.files || !proofPath.files.length) {
                    alert('Harap upload bukti transfer!');
                    return;
                }
                console.log('Proof file attached:', proofPath.files[0]);
            }
        
        // Check if transfer method requires bank name
        if (methodTransfer.checked && !document.getElementById('bank_name').value) {
            alert('Harap pilih bank tujuan!');
            return;
        }
        
        // Check if cash method requires fields
        if (methodCash.checked) {
            if (!document.getElementById('place_paid').value) {
                alert('Harap pilih tempat bayar!');
                return;
            }
            if (!document.getElementById('received_by_user_id').value) {
                alert('Harap pilih petugas yang menerima!');
                return;
            }
        }
        
        // Prepare form data
        const formData = new FormData(this);
        
        // Convert amount from formatted string to integer
        const amountValue = document.getElementById('amount').value.replace(/\./g, '');
        formData.set('amount', amountValue);
        
        // Debug: Log form data
        console.log('Sending payment data...');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(key, ':', value.name, '(', value.size, 'bytes)');
            } else {
                console.log(key, ':', value);
            }
        }
        
        // Show loading
        const submitBtn = document.getElementById('submitPaymentBtn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        
        // Submit via AJAX
        // IMPORTANT: Don't set Content-Type header when using FormData with file uploads
        // Browser will automatically set it to multipart/form-data with boundary
        fetch('/dashboard/keuangan/payment/store', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                document.querySelector('[name=_token]')?.value || '',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Success response:', data);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                
                // Show success message
                alert('✅ ' + data.message);
                
                // Update UI secara real-time
                updatePaymentUI(data.data);
                
                // Reload page untuk sync data terbaru
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // Handle validation errors
                if (data.errors) {
                    let errorMsg = 'Validasi gagal:\n';
                    for (let field in data.errors) {
                        errorMsg += '- ' + data.errors[field].join('\n- ') + '\n';
                    }
                    alert('❌ ' + errorMsg);
                } else {
                    alert('❌ Error: ' + data.message);
                }
                console.error('Server error:', data);
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            console.error('Fetch error:', error);
            alert('❌ Terjadi kesalahan: ' + error.message);
        });
        });
    } else {
        console.error('Payment form not found!');
    }

    // Verify/Reject payment actions
    window.handlePaymentAction = function(paymentId, action) {
        if (!paymentId || !['verify', 'reject'].includes(action)) return;
        const confirmText = action === 'verify' ? 'Verifikasi pembayaran ini?' : 'Tolak pembayaran ini?';
        if (!confirm(confirmText)) return;

        fetch(`/dashboard/keuangan/payment/${paymentId}/${action}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(async (resp) => {
            const contentType = resp.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                const json = await resp.json();
                return { ok: resp.ok, status: resp.status, json };
            }

            const text = await resp.text();
            return { ok: resp.ok, status: resp.status, text };
        })
        .then(data => {
            if (data.json?.success) {
                alert('✅ ' + data.json.message);
                location.reload();
            } else {
                const message = data.json?.message
                    || (data.status === 419 ? 'CSRF token tidak valid. Silakan refresh halaman.' : null)
                    || (data.status === 401 ? 'Sesi login berakhir. Silakan login ulang.' : null)
                    || (data.status === 403 ? 'Anda tidak punya akses untuk aksi ini.' : null)
                    || (data.status ? `Gagal memproses aksi (HTTP ${data.status})` : 'Gagal memproses aksi');
                alert('❌ ' + message);
                if (data.text) {
                    console.error('Non-JSON response:', data.text);
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ Terjadi kesalahan: ' + err.message);
        });
    };

    // Delete payment (admin/kepala sekolah)
    window.handlePaymentDelete = function(paymentId, label) {
        if (!paymentId) return;
        const suffix = label ? ` ${label}` : '';
        const confirmText = `Yakin hapus pembayaran${suffix}? Bukti transfer akan dihapus dan status kembali menjadi Belum.`;
        if (!confirm(confirmText)) return;

        fetch(`/dashboard/keuangan/payment/${paymentId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: '_method=DELETE',
            credentials: 'same-origin'
        })
        .then(async (resp) => {
            const contentType = resp.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const json = await resp.json();
                return { ok: resp.ok, status: resp.status, json };
            }
            const text = await resp.text();
            return { ok: resp.ok, status: resp.status, text };
        })
        .then(data => {
            if (data.json?.success) {
                alert('✅ ' + data.json.message);
                location.reload();
                return;
            }

            const message = data.json?.message
                || (data.status === 419 ? 'CSRF token tidak valid. Silakan refresh halaman.' : null)
                || (data.status === 401 ? 'Sesi login berakhir. Silakan login ulang.' : null)
                || (data.status === 403 ? 'Anda tidak punya akses untuk aksi ini.' : null)
                || (data.status ? `Gagal menghapus pembayaran (HTTP ${data.status})` : 'Gagal menghapus pembayaran');
            alert('❌ ' + message);
            if (data.text) {
                console.error('Non-JSON response:', data.text);
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ Terjadi kesalahan: ' + err.message);
        });
    };
    
    // Function to update UI after payment
    function updatePaymentUI(data) {
        // Assuming SPP per bulan = 50000 (atau bisa kirim dari backend)
        const sppPerBulan = {{ $sppPerBulan }};
        const paidMonths = Math.floor(data.total_paid / sppPerBulan);
        const remainingMonths = 12 - paidMonths;
        
        // Update total terbayar
        const totalPaidEl = document.querySelector('.total-paid-amount');
        if (totalPaidEl) {
            totalPaidEl.textContent = 'Rp ' + Number(data.total_paid).toLocaleString('id-ID');
        }
        
        // Update jumlah bulan terbayar
        const totalPaidMonthsEl = document.querySelector('.total-paid-months');
        if (totalPaidMonthsEl) {
            totalPaidMonthsEl.textContent = paidMonths + ' dari 12 bulan';
        }
        
        // Update tunggakan
        const remainingEl = document.querySelector('.remaining-amount');
        if (remainingEl) {
            remainingEl.textContent = 'Rp ' + Number(data.remaining).toLocaleString('id-ID');
        }
        
        // Update bulan tersisa
        const remainingMonthsEl = document.querySelector('.remaining-months');
        if (remainingMonthsEl) {
            remainingMonthsEl.textContent = remainingMonths + ' bulan tersisa';
        }
        
        // Update progress bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = data.percentage + '%';
        }
        
        // Update percentage text
        const percentageEl = document.querySelector('.percentage-text');
        if (percentageEl) {
            percentageEl.textContent = data.percentage + '%';
        }
    }

    // ===== REAL-TIME PAYMENT STATUS SYNCHRONIZATION =====
    // Initialize payment status watcher untuk real-time sync
    const paymentSynchronizer = new PaymentStatusSynchronizer({
        pollingInterval: 3000, // Poll every 3 seconds
        studentId: {{ $payment->student_id ?? 'null' }},
        year: {{ $payment->invoice_year ?? date('Y') }},
        onStatusChange: function(event) {
            console.log('Payment status changed:', event);
            
            // Reload payment table untuk reflect changes
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    });

    // Get all payment IDs pada page dan mulai polling
    document.querySelectorAll('[data-payment-id]').forEach(el => {
        const paymentId = el.getAttribute('data-payment-id');
        if (paymentId) {
            paymentSynchronizer.startSyncPayment(paymentId);
        }
    });

    // Listen untuk custom payment status updated event
    window.addEventListener('paymentStatusUpdated', function(e) {
        console.log('Payment status update received:', e.detail);
        
        // Update UI element untuk payment ini
        const paymentId = e.detail.paymentId;
        const newStatus = e.detail.newStatus;
        
        // Find dan update status badge
        const statusBadge = document.querySelector(`[data-payment-id="${paymentId}"] .status-badge`);
        if (statusBadge) {
            statusBadge.innerHTML = getStatusBadgeHtml(newStatus);
        }
    });

    // Stop synchronization when page unload
    window.addEventListener('beforeunload', function() {
        paymentSynchronizer.stopAll();
    });

    // Helper function untuk generate status badge HTML
    function getStatusBadgeHtml(status) {
        const badges = {
            'verified': '<span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;"><i class="fas fa-check-circle me-1"></i>Verified</span>',
            'pending': '<span class="badge px-3 py-2 bg-warning text-dark fw-semibold"><i class="fas fa-hourglass-half me-1"></i>Pending</span>',
            'rejected': '<span class="badge px-3 py-2 bg-danger text-white fw-semibold"><i class="fas fa-times-circle me-1"></i>Rejected</span>',
            'unpaid': '<span class="badge px-3 py-2 bg-secondary text-white"><i class="fas fa-clock me-1"></i>Belum Bayar</span>'
        };
        
        return badges[status] || badges['unpaid'];
    }
});
</script>
<script src="{{ asset('js/payment-status-sync.js') }}"></script>
@endpush
@endsection
