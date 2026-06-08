@extends('layouts.student')

@section('title', 'Keuangan Siswa - Dashboard')
@section('page-title', 'Keuangan')

@section('content')
<div class="container-fluid">
    <!-- Error Message -->
    @if(isset($error))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($payment)
    <!-- Student Info Card -->
    <div class="bg-white rounded-4 shadow-sm p-3 p-md-4 mb-3 mb-md-4 border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <div class="row g-2 g-md-3">
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">NIS</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">{{ $student->nis }}</p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Nama Siswa</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">{{ $student->name }}</p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Kelas</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">
                    @if($student->current_grade_level)
                        Kelas {{ $student->current_grade_level }}
                    @else
                        -
                    @endif
                </p>
            </div>
            <div class="col-6 col-md-3">
                <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Jurusan</p>
                <p class="fw-bold text-dark mb-0" style="font-size: 0.875rem;">{{ $student->major ?? '-' }}</p>
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
                @php
                    $paidMonths = collect($monthlyPayments)->where('status', 'verified')->count();
                @endphp
                <p class="text-white small mb-0 d-none d-md-block total-paid-months" style="opacity: 0.8; font-size: 0.7rem;">{{ $paidMonths }} dari 12 bulan</p>
            </div>
        </div>

        <!-- Tunggakan -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #fb2424d0 0%, #f50b0be1 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Tunggakan</p>
                <h3 class="text-white fw-bold mb-1 fs-6 fs-md-4 remaining-amount">Rp {{ number_format($payment->remaining, 0, ',', '.') }}</h3>
                @php
                    $remainingMonths = 12 - $paidMonths;
                @endphp
                <p class="text-white small mb-0 d-none d-md-block remaining-months" style="opacity: 0.8; font-size: 0.7rem;">{{ $remainingMonths }} bulan tersisa</p>
            </div>
        </div>

        <!-- Progress -->
        <div class="col-6 col-md-3">
            <div class="rounded-3 rounded-md-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                <p class="text-white small mb-1 mb-md-2" style="opacity: 0.9; font-size: 0.75rem;">Progress</p>
                <h3 class="text-white fw-bold mb-2 mb-md-3 fs-6 fs-md-4 percentage-text">{{ $payment->payment_percentage }}%</h3>
                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.3);">
                    <div class="progress-bar bg-white percentage-bar" role="progressbar" style="width: {{ $payment->payment_percentage }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Payments Table -->
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <!-- Table Header -->
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%); border-bottom: 2px solid rgba(59, 130, 246, 0.15);">
            <div class="d-flex align-items-center">
                <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                    <i class="fas fa-calendar-alt me-2" style="color: #3b82f6;"></i>
                    Pembayaran Bulanan (Juli - Juni)
                </h5>
                <a href="{{ route('student.keuangan.print.spp') }}" target="_blank" class="btn btn-sm btn-outline-primary ms-3">
                    <i class="fas fa-print me-1"></i> Cetak Laporan SPP
                </a>
            </div>
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
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" @if($monthly['payment_id']) data-payment-id="{{ $monthly['payment_id'] }}" @endif>
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
                                    {{ $monthly['location'] }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="status-badge">
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
                                </div>
                            </td>
                            <td>
                                @if($monthly['proof_url'])
                                    <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ $monthly['proof_url'] }}')">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!$monthly['payment_id'] || $monthly['status'] === 'rejected')
                                    @if(!empty($monthly['is_unlocked_to_pay']))
                                        <button class="btn btn-sm btn-primary px-3" 
                                                onclick="openPaymentModal('{{ $monthly['month'] }}', {{ $monthly['amount'] }}, {{ $monthly['no'] }})">
                                            <i class="fas fa-plus me-1"></i>Bayar
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary px-3" type="button" disabled title="Pembayaran harus berurutan">
                                            <i class="fas fa-lock me-1"></i>Terkunci
                                        </button>
                                    @endif
                                @elseif($monthly['status'] === 'verified')
                                    <a href="{{ route('student.payment.receipt', $monthly['payment_id']) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
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
                <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
                    <!-- Card Header -->
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $monthly['status'] == 'verified' ? '#198754 0%, #157347 100%' : ($monthly['status'] == 'pending' ? '#ffc107 0%, #e0a800 100%' : ($monthly['status'] == 'rejected' ? '#fb2424d0 0%, #f50b0be1 100%' : '#6c757d 0%, #495057 100%')) }});">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-white text-dark px-2 py-1 me-2" style="font-size: 0.7rem;">{{ $monthly['no'] }}</span>
                                <span class="text-white fw-bold" style="font-size: 0.95rem;">{{ $monthly['month'] }}</span>
                            </div>
                            @if($monthly['status'] == 'verified')
                                <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                            @elseif($monthly['status'] == 'pending')
                                <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                            @elseif($monthly['status'] == 'rejected')
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
                                @php $status = $monthly['status']; @endphp
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
                                        <span class="text-dark fw-semibold small">{{ $monthly['location'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($monthly['proof_url'])
                        <div class="mb-2">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                            <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ $monthly['proof_url'] }}')">
                                <i class="fas fa-eye me-1"></i>Pratinjau
                            </button>
                        </div>
                        @endif

                        <!-- Action Button -->
                        <div class="d-grid mt-3 pt-2 border-top">
                            @if(!$monthly['payment_id'] || $monthly['status'] === 'rejected')
                                @if(!empty($monthly['is_unlocked_to_pay']))
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="openPaymentModal('{{ $monthly['month'] }}', {{ $monthly['amount'] }}, {{ $monthly['no'] }})">
                                        <i class="fas fa-plus me-2"></i>Bayar Sekarang
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>
                                        <i class="fas fa-lock me-2"></i>Terkunci
                                    </button>
                                @endif
                            @elseif($monthly['status'] === 'verified')
                                <a href="{{ route('student.payment.receipt', $monthly['payment_id']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-2"></i>Cetak Kwitansi
                                </a>
                            @else
                                <span class="text-muted small text-center">Menunggu Verifikasi Admin</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Additional Payments (Seragam / PTS / PAS) -->
    @if($additionalPayments && count($additionalPayments) > 0)
    <div class="mt-4">
        <!-- Seragam -->
        @if(isset($additionalPayments['seragam']))
        <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
            <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(0, 169, 224, 0.08) 0%, rgba(2, 132, 199, 0.08) 100%); border-bottom: 2px solid rgba(0, 169, 224, 0.15);">
                <div class="d-flex align-items-center">
                    <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                        <i class="fas fa-shirt me-2" style="color: #0ea5e9;"></i>
                        Pembayaran Seragam
                    </h5>
                    <a href="{{ route('student.keuangan.print.seragam') }}" target="_blank" class="btn btn-sm btn-outline-info ms-3">
                        <i class="fas fa-print me-1"></i> Cetak Laporan Seragam
                    </a>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="d-none d-lg-block table-responsive">
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
                            $paymentUniform = $detail['payment'] ?? null;
                            $statusUniform = $paymentUniform ? ($paymentUniform->status ?? 'unpaid') : 'unpaid';
                            if ($statusUniform === 'submitted') { $statusUniform = 'pending'; }
                        @endphp
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" @if($paymentUniform) data-payment-id="{{ $paymentUniform->id }}" @endif>
                            <td class="fw-semibold text-dark">{{ $detail['name'] }}</td>
                            <td class="text-dark">Rp {{ number_format($detail['cost'], 0, ',', '.') }}</td>
                            <td>
                                @if($paymentUniform && $paymentUniform->method)
                                    <span class="badge px-3 py-2" style="background: {{ $paymentUniform->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-weight: 600;">
                                        {{ strtoupper($paymentUniform->method) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-dark">
                                @if($paymentUniform && ($paymentUniform->bank_name || $paymentUniform->place_paid))
                                    {{ $paymentUniform->bank_name ?? $paymentUniform->place_paid }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="status-badge">
                                    @if($statusUniform === 'verified')
                                        <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600;">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    @elseif($statusUniform === 'pending')
                                        <span class="badge px-3 py-2 bg-warning text-dark fw-semibold">
                                            <i class="fas fa-hourglass-half me-1"></i>Pending
                                        </span>
                                    @elseif($statusUniform === 'rejected')
                                        <span class="badge px-3 py-2 bg-danger text-white fw-semibold">
                                            <i class="fas fa-times-circle me-1"></i>Rejected
                                        </span>
                                    @else
                                        <span class="badge px-3 py-2 bg-secondary text-white">
                                            <i class="fas fa-clock me-1"></i>Belum
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($paymentUniform && $paymentUniform->proof_path)
                                    <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$paymentUniform->id, basename($paymentUniform->proof_path)]) }}')">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!$paymentUniform || $statusUniform === 'rejected')
                                    <button type="button" class="btn btn-sm btn-primary px-3" onclick="selectUniformType('{{ $type }}', {{ (int) $detail['cost'] }})">
                                        <i class="fas fa-plus me-1"></i>Bayar
                                    </button>
                                @elseif($statusUniform === 'verified')
                                    <a href="{{ route('student.payment.receipt', $paymentUniform->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                @else
                                    <span class="text-muted">Menunggu Verifikasi Admin</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="d-lg-none p-3">
                <div class="d-flex flex-column gap-3">
                    @foreach($additionalPayments['seragam']['details'] as $type => $detail)
                    @php
                        $paymentUniform = $detail['payment'] ?? null;
                        $statusUniform = $paymentUniform ? ($paymentUniform->status ?? 'unpaid') : 'unpaid';
                        if ($statusUniform === 'submitted') { $statusUniform = 'pending'; }
                    @endphp
                    <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(0, 169, 224, 0.18) !important;" @if($paymentUniform) data-payment-id="{{ $paymentUniform->id }}" @endif>
                        <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $statusUniform === 'verified' ? '#198754 0%, #157347 100%' : ($statusUniform === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($statusUniform === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#0ea5e9 0%, #0284c7 100%')) }});">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-white fw-bold" style="font-size: 0.95rem;">{{ $detail['name'] }}</div>
                                @if($statusUniform === 'verified')
                                    <i class="fas fa-check-circle text-white" style="font-size: 1.1rem;"></i>
                                @elseif($statusUniform === 'pending')
                                    <i class="fas fa-hourglass-half text-white" style="font-size: 1.1rem;"></i>
                                @elseif($statusUniform === 'rejected')
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
                                    <div class="status-badge">
                                        @if($statusUniform === 'verified')
                                            <span class="badge px-2 py-1" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-size: 0.75rem;">
                                                <i class="fas fa-check-circle me-1"></i>Verified
                                            </span>
                                        @elseif($statusUniform === 'pending')
                                            <span class="badge px-2 py-1 bg-warning text-dark" style="font-size: 0.75rem;">
                                                <i class="fas fa-hourglass-half me-1"></i>Pending
                                            </span>
                                        @elseif($statusUniform === 'rejected')
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
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Metode</p>
                                    @if($paymentUniform && $paymentUniform->method)
                                        <span class="badge px-2 py-1" style="background: {{ $paymentUniform->method == 'cash' ? 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)' : 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)' }}; color: white; font-size: 0.75rem;">
                                            {{ strtoupper($paymentUniform->method) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                                <div class="col-6 text-end">
                                    <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Tempat Bayar</p>
                                    <p class="mb-0" style="font-size: 0.85rem;">
                                        @if($paymentUniform && ($paymentUniform->bank_name || $paymentUniform->place_paid))
                                            <span class="text-dark fw-semibold small">{{ $paymentUniform->bank_name ?? $paymentUniform->place_paid }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mb-2">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                                @if($paymentUniform && $paymentUniform->proof_path)
                                    <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$paymentUniform->id, basename($paymentUniform->proof_path)]) }}')">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </div>

                            <div class="d-grid mt-3 pt-2 border-top">
                                @if(!$paymentUniform || $statusUniform === 'rejected')
                                    <button type="button" class="btn btn-sm btn-primary" onclick="selectUniformType('{{ $type }}', {{ (int) $detail['cost'] }})">
                                        <i class="fas fa-plus me-2"></i>Bayar
                                    </button>
                                @elseif($statusUniform === 'verified')
                                    <a href="{{ route('student.payment.receipt', $paymentUniform->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print me-2"></i>Cetak Kwitansi
                                    </a>
                                @else
                                    <span class="text-muted small text-center">Menunggu Verifikasi Admin</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- PTS -->
        @if(isset($additionalPayments['pts']))
        <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
            <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(217, 119, 6, 0.08) 100%); border-bottom: 2px solid rgba(245, 158, 11, 0.15);">
                <div class="d-flex align-items-center">
                    <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                        <i class="fas fa-book-open me-2" style="color: #f59e0b;"></i>
                        Pembayaran PTS
                    </h5>
                    <a href="{{ route('student.keuangan.print.ujian') }}" target="_blank" class="btn btn-sm btn-outline-warning ms-3">
                        <i class="fas fa-print me-1"></i> Cetak Laporan PTS/PAS
                    </a>
                </div>
            </div>

            @php
                $ptsPayment = $additionalPayments['pts']['payment'] ?? null;
                $ptsStatus = $ptsPayment ? ($ptsPayment->status ?? 'unpaid') : 'unpaid';
                if ($ptsStatus === 'submitted') { $ptsStatus = 'pending'; }
            @endphp

            <div class="d-none d-lg-block table-responsive">
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
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" @if($ptsPayment) data-payment-id="{{ $ptsPayment->id }}" @endif>
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
                                            <i class="fas fa-clock me-1"></i>Belum
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($ptsPayment && $ptsPayment->proof_path)
                                    <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$ptsPayment->id, basename($ptsPayment->proof_path)]) }}')">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!$ptsPayment || $ptsStatus === 'rejected')
                                    <button type="button" class="btn btn-sm btn-primary px-3" onclick="openPaymentModal('PTS', {{ (int) $additionalPayments['pts']['total_bill'] }}, 5, 'pts')">
                                        <i class="fas fa-plus me-1"></i>Bayar
                                    </button>
                                @elseif($ptsStatus === 'verified')
                                    <a href="{{ route('student.payment.receipt', $ptsPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                @else
                                    <span class="text-muted">Menunggu Verifikasi Admin</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none p-3">
                <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(245, 158, 11, 0.18) !important;" @if($ptsPayment) data-payment-id="{{ $ptsPayment->id }}" @endif>
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $ptsStatus === 'verified' ? '#198754 0%, #157347 100%' : ($ptsStatus === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($ptsStatus === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#f59e0b 0%, #d97706 100%')) }});">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-white fw-bold" style="font-size: 0.95rem;">PTS</div>
                            <i class="fas fa-book-open text-white" style="font-size: 1.05rem;"></i>
                        </div>
                    </div>
                    <div class="p-3 bg-white">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Nominal</p>
                                <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($additionalPayments['pts']['total_bill'], 0, ',', '.') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Status</p>
                                <div class="status-badge">
                                    @if($ptsStatus === 'verified')
                                        <span class="badge px-2 py-1" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-size: 0.75rem;">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    @elseif($ptsStatus === 'pending')
                                        <span class="badge px-2 py-1 bg-warning text-dark" style="font-size: 0.75rem;">
                                            <i class="fas fa-hourglass-half me-1"></i>Pending
                                        </span>
                                    @elseif($ptsStatus === 'rejected')
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
                        </div>

                        <div class="mb-2">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                            @if($ptsPayment && $ptsPayment->proof_path)
                                <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$ptsPayment->id, basename($ptsPayment->proof_path)]) }}')">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </div>

                        <div class="d-grid mt-3 pt-2 border-top">
                            @if(!$ptsPayment || $ptsStatus === 'rejected')
                                <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModal('PTS', {{ (int) $additionalPayments['pts']['total_bill'] }}, 5, 'pts')">
                                    <i class="fas fa-plus me-2"></i>Bayar
                                </button>
                            @elseif($ptsStatus === 'verified')
                                <a href="{{ route('student.payment.receipt', $ptsPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-2"></i>Cetak Kwitansi
                                </a>
                            @else
                                <span class="text-muted small text-center">Menunggu Verifikasi Admin</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- PAS -->
        @if(isset($additionalPayments['pas']))
        <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
            <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.08) 100%); border-bottom: 2px solid rgba(239, 68, 68, 0.15);">
                <div class="d-flex align-items-center">
                    <h5 class="fw-bold text-dark mb-0" style="font-size: 1rem;">
                        <i class="fas fa-scroll me-2" style="color: #ef4444;"></i>
                        Pembayaran PAS
                    </h5>
                    <!-- Combined PTS/PAS printing handled via the PTS header button -->
                </div>
            </div>

            @php
                $pasPayment = $additionalPayments['pas']['payment'] ?? null;
                $pasStatus = $pasPayment ? ($pasPayment->status ?? 'unpaid') : 'unpaid';
                if ($pasStatus === 'submitted') { $pasStatus = 'pending'; }
            @endphp

            <div class="d-none d-lg-block table-responsive">
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
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);" @if($pasPayment) data-payment-id="{{ $pasPayment->id }}" @endif>
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
                                            <i class="fas fa-clock me-1"></i>Belum
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($pasPayment && $pasPayment->proof_path)
                                    <button class="btn btn-sm btn-info text-white px-3" onclick="handleProofPreview('{{ route('payment.proof.show', [$pasPayment->id, basename($pasPayment->proof_path)]) }}')">
                                        <i class="fas fa-eye me-1"></i>Pratinjau
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if(!$pasPayment || $pasStatus === 'rejected')
                                    <button type="button" class="btn btn-sm btn-primary px-3" onclick="openPaymentModal('PAS', {{ (int) $additionalPayments['pas']['total_bill'] }}, 12, 'pas')">
                                        <i class="fas fa-plus me-1"></i>Bayar
                                    </button>
                                @elseif($pasStatus === 'verified')
                                    <a href="{{ route('student.payment.receipt', $pasPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary px-3">
                                        <i class="fas fa-print me-1"></i>Cetak
                                    </a>
                                @else
                                    <span class="text-muted">Menunggu Verifikasi Admin</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-lg-none p-3">
                <div class="rounded-3 overflow-hidden shadow-sm border" style="border-color: rgba(239, 68, 68, 0.18) !important;" @if($pasPayment) data-payment-id="{{ $pasPayment->id }}" @endif>
                    <div class="px-3 py-2" style="background: linear-gradient(135deg, {{ $pasStatus === 'verified' ? '#198754 0%, #157347 100%' : ($pasStatus === 'pending' ? '#f59e0b 0%, #d97706 100%' : ($pasStatus === 'rejected' ? '#ef4444 0%, #dc2626 100%' : '#ef4444 0%, #dc2626 100%')) }});">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-white fw-bold" style="font-size: 0.95rem;">PAS</div>
                            <i class="fas fa-scroll text-white" style="font-size: 1.05rem;"></i>
                        </div>
                    </div>
                    <div class="p-3 bg-white">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Nominal</p>
                                <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Rp {{ number_format($additionalPayments['pas']['total_bill'], 0, ',', '.') }}</p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Status</p>
                                <div class="status-badge">
                                    @if($pasStatus === 'verified')
                                        <span class="badge px-2 py-1" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-size: 0.75rem;">
                                            <i class="fas fa-check-circle me-1"></i>Verified
                                        </span>
                                    @elseif($pasStatus === 'pending')
                                        <span class="badge px-2 py-1 bg-warning text-dark" style="font-size: 0.75rem;">
                                            <i class="fas fa-hourglass-half me-1"></i>Pending
                                        </span>
                                    @elseif($pasStatus === 'rejected')
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
                        </div>

                        <div class="mb-2">
                            <p class="text-secondary small mb-1" style="font-size: 0.7rem;">Bukti Transfer</p>
                            @if($pasPayment && $pasPayment->proof_path)
                                <button class="btn btn-sm btn-info text-white px-3" style="font-size: 0.8rem;" onclick="handleProofPreview('{{ route('payment.proof.show', [$pasPayment->id, basename($pasPayment->proof_path)]) }}')">
                                    <i class="fas fa-eye me-1"></i>Pratinjau
                                </button>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </div>

                        <div class="d-grid mt-3 pt-2 border-top">
                            @if(!$pasPayment || $pasStatus === 'rejected')
                                <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModal('PAS', {{ (int) $additionalPayments['pas']['total_bill'] }}, 12, 'pas')">
                                    <i class="fas fa-plus me-2"></i>Bayar
                                </button>
                            @elseif($pasStatus === 'verified')
                                <a href="{{ route('student.payment.receipt', $pasPayment->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-print me-2"></i>Cetak Kwitansi
                                </a>
                            @else
                                <span class="text-muted small text-center">Menunggu Verifikasi Admin</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

<!-- Modal Pilih Jenis Seragam -->
<div class="modal fade" id="uniformTypeModal" tabindex="-1" aria-labelledby="uniformTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%);">
                <h6 class="modal-title fw-bold text-dark mb-0" id="uniformTypeModalLabel">
                    <i class="fas fa-shirt me-2" style="color: #0ea5e9; font-size: 0.9rem;"></i>
                    Pilih Jenis Seragam
                </h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if(isset($additionalPayments['seragam']))
                    @php
                        $uniformIconMap = [
                            'batik' => 'fa-palette',
                            'olahraga' => 'fa-person-running',
                            'muslim' => 'fa-user-long-hair',
                            'pramuka' => 'fa-hat-user',
                            'almamater' => 'fa-shirt',
                        ];
                    @endphp
                    <div class="row g-3">
                        @foreach($additionalPayments['seragam']['details'] as $type => $detail)
                            <div class="col-6 @if($loop->last && (count($additionalPayments['seragam']['details']) % 2 === 1)) col-12 @endif">
                                <button class="btn btn-outline-primary w-100 py-3 rounded-3 fw-semibold" onclick="selectUniformType('{{ $type }}', {{ (int) $detail['cost'] }})">
                                    <i class="fas {{ $uniformIconMap[$type] ?? 'fa-shirt' }} d-block mb-2" style="font-size: 1.5rem;"></i>
                                    {{ $detail['name'] }}<br>
                                    <small>Rp {{ number_format($detail['cost'], 0, ',', '.') }}</small>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted text-center" style="font-size: 0.9rem;">Data seragam belum tersedia.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Pembayaran SPP (Transfer Only) -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-2" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%);">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="paymentModalLabel">
                        <i class="fas fa-money-bill-wave me-2" style="color: #3b82f6; font-size: 0.9rem;"></i>
                        Pembayaran SPP (Transfer)
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
                <form id="paymentForm" method="POST" action="{{ route('student.payment.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="student_id" name="student_id" value="{{ $student->id }}">
                    <input type="hidden" id="invoice_year" name="invoice_year" value="{{ $invoiceYear }}">
                    <input type="hidden" id="invoice_type" name="invoice_type" value="spp">
                    <input type="hidden" id="uniform_type" name="uniform_type" value="">
                    <input type="hidden" id="payment_month" name="payment_month">
                    <input type="hidden" name="method" value="transfer">
                    
                    <!-- Tanggal Bayar -->
                    <div class="mb-3">
                        <label for="paid_at" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-calendar me-1 text-primary" style="font-size: 0.75rem;"></i>Tanggal Transfer
                            <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control form-control-sm rounded-2 border-2" 
                               id="paid_at" name="paid_at" 
                               value="{{ \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required
                               style="font-size: 0.85rem;">
                        <small class="text-muted" style="font-size: 0.7rem;">Sesuaikan dengan tanggal di bukti transfer</small>
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

                    <!-- Bank Tujuan -->
                    <div class="mb-3">
                        <label for="bank_name" class="form-label fw-semibold text-dark mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-university me-1 text-info" style="font-size: 0.75rem;"></i>Bank Tujuan
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm rounded-2 border-2" id="bank_name" name="bank_name" required style="font-size: 0.85rem;">
                            <option value="BRI" selected>BRI (Bank Rakyat Indonesia)</option>
                        </select>
                    </div>

                    <!-- Upload Bukti Transfer -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 0.85rem;">
                            <i class="fas fa-file-image me-1 text-warning" style="font-size: 0.75rem;"></i>Bukti Transfer
                            <span class="text-danger">*</span>
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
                               accept="image/*" required>
                        
                        <!-- Camera Input (Hidden) -->
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
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Penerima</small>
                                            <p class="mb-0 fw-semibold text-dark" id="ocrRecipient" style="font-size: 0.8rem;">-</p>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">No. Referensi</small>
                                            <p class="mb-0 fw-semibold text-dark" id="ocrReference" style="font-size: 0.8rem;">-</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Retry Upload Section -->
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

                    <!-- Alert OCR Tidak Terhubung -->
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

<!-- Modal Pratinjau Bukti Transfer -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark" id="proofModalLabel">Pratinjau Bukti Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="proofImageContainer" class="text-center" style="display: none;">
                    <img id="proofImagePreview" src="" alt="Bukti Transfer" class="img-fluid rounded" style="max-height: 70vh;">
                </div>
                <div id="proofFrameContainer" style="display: none; height: 70vh;">
                    <iframe id="proofFramePreview" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Proof Preview Modal Elements
    const proofImageContainer = document.getElementById('proofImageContainer');
    const proofFrameContainer = document.getElementById('proofFrameContainer');
    const proofImagePreview = document.getElementById('proofImagePreview');
    const proofFramePreview = document.getElementById('proofFramePreview');
    const proofModalEl = document.getElementById('proofModal');
    const proofModal = proofModalEl ? new bootstrap.Modal(proofModalEl) : null;

    // Format currency input
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        amountInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    }
    
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

    let cameraStream = null;
    let capturedBlob = null;
    let isPreviewMirrored = false;
    let currentFacingMode = 'environment';

    function setCameraPreviewMirroring(shouldMirror) {
        isPreviewMirrored = !!shouldMirror;
        if (cameraVideo) {
            cameraVideo.style.transform = isPreviewMirrored ? 'scaleX(-1)' : 'none';
        }
        // Keep captured preview always "real" (not mirrored)
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

        // Use native picker API when available for better mobile camera support.
        if (typeof proofCamera.showPicker === 'function') {
            proofCamera.showPicker();
            return;
        }

        proofCamera.click();
    }

    if (uploadFileBtn) uploadFileBtn.addEventListener('click', () => proofPath.click());
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

            // Prefer the selected facing mode, fallback gracefully.
            try {
                cameraStream = await tryGet({ facingMode: { ideal: currentFacingMode } });
            } catch (err) {
                // Some devices/browsers are picky; retry without facingMode.
                cameraStream = await tryGet(true);
            }

            cameraVideo.srcObject = cameraStream;
            await cameraVideo.play();

            // Mirror preview only when the selected camera is user-facing.
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
            // Toggle facing mode and restart stream.
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
        formData.append('student_id', '{{ $student->id }}');
        formData.append('uploaded_by', '{{ auth()->id() }}');
        
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
            fetch(@json(route('student.payment.ocr.process')), {
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
                    const retrySection = document.getElementById('retryUploadSection');
                    if (retrySection) retrySection.style.display = 'block';
                    if (submitBtn) submitBtn.style.display = 'inline-block';
                } else {
                    if (submitBtn) submitBtn.style.display = 'inline-block';
                    const retrySection = document.getElementById('retryUploadSection');
                    if (retrySection) retrySection.style.display = 'none';
                }
            } else {
                // OCR extraction failed completely, but the payment can still be submitted for manual review.
                if (submitBtn) submitBtn.style.display = 'inline-block';
                const retrySection = document.getElementById('retryUploadSection');
                if (retrySection) retrySection.style.display = 'none';
                
                // Tampilkan alert kuning jika gagal ekstrak
                const ocrFailedAlert = document.getElementById('ocrFailedAlert');
                if (ocrFailedAlert) {
                    ocrFailedAlert.style.display = 'block';
                    setTimeout(() => ocrFailedAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
                }
            }
        })
        .catch(error => {
            console.error('OCR Error:', error);
            const ocrProcessing = document.getElementById('ocrProcessing');
            if (ocrProcessing) ocrProcessing.style.display = 'none';
            setOcrUiBusy(false);
            
            // OCR Fallback: Sistem OCR tidak tersedia tapi pembayaran tetap bisa disimpan
            console.log('OCR service tidak tersedia - Pembayaran akan disimpan untuk validasi manual oleh admin');

            // Tampilkan alert kuning (warning, bukan error)
            let ocrFailedAlert = document.getElementById('ocrFailedAlert');
            if (!ocrFailedAlert) {
                console.warn("ocrFailedAlert element not found in DOM! Creating dynamically...");
                const alertHtml = `
                    <div id="ocrFailedAlert" class="alert alert-warning mb-0 mt-3" style="display: block; font-size: 0.85rem;">
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
                `;
                const form = document.getElementById('paymentForm');
                if (form) {
                    form.insertAdjacentHTML('beforeend', alertHtml);
                    ocrFailedAlert = document.getElementById('ocrFailedAlert');
                }
            }

            if (ocrFailedAlert) {
                ocrFailedAlert.classList.remove('d-none');
                ocrFailedAlert.style.setProperty('display', 'block', 'important');
                ocrFailedAlert.style.opacity = '1';
                ocrFailedAlert.style.visibility = 'visible';
                ocrFailedAlert.style.height = 'auto';
                setTimeout(() => ocrFailedAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
            }

            // TETAP TAMPILKAN submit button agar siswa bisa submit
            // Pembayaran akan disimpan dengan status pending untuk admin verify
            const submitBtn = document.getElementById('submitPaymentBtn');
            if (submitBtn) submitBtn.style.display = 'inline-block';
            // Sembunyikan retry section karena OCR tidak tersedia
            const retrySection = document.getElementById('retryUploadSection');
            if (retrySection) retrySection.style.display = 'none';
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
    
    if (proofPath) {
        proofPath.addEventListener('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });
    }
    
    if (proofCamera) {
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
                    proofPath.files = e.target.files;
                }
            }
        });
    }
    
    if (removeProof) {
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
    }
    
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
    window.handleProofPreview = function(url) {
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
        proofModal.show();
    };

    if (proofModalEl) {
        proofModalEl.addEventListener('hidden.bs.modal', function() {
            proofImagePreview.src = '';
            proofFramePreview.src = '';
        });
    }
    
    // Open modal with payment info
    // Uniform Type Selection
    window.selectUniformType = function(uniformType, amount) {
        // Close the uniform type modal
        const uniformTypeModal = bootstrap.Modal.getInstance(document.getElementById('uniformTypeModal'));
        if (uniformTypeModal) {
            uniformTypeModal.hide();
        }
        
        // Map uniform type to display name
        const uniformTypeMap = {
            'batik': 'Batik',
            'olahraga': 'Olahraga',
            'muslim': 'Muslim',
            'pramuka': 'Pramuka',
            'almamater': 'Almamater'
        };
        
        // Set uniform type in hidden field
        document.getElementById('uniform_type').value = uniformType;
        
        // Open payment modal with uniform type and amount
        // month parameter + 10 to avoid conflict with SPP months (1-12)
        const uniformMonthMap = {
            'batik': 1,
            'olahraga': 2,
            'muslim': 3,
            'pramuka': 4,
            'almamater': 5
        };
        
        const typeLabel = uniformTypeMap[uniformType] || uniformType;
        openPaymentModal('Seragam - ' + typeLabel, amount, uniformMonthMap[uniformType], 'uniform');
    };
    
    window.openPaymentModal = function(month, amount, monthNo, invoiceType = 'spp') {
        const typeLabels = {
            'spp': 'SPP Bulanan',
            'uniform': 'Seragam',
            'pts': 'PTS',
            'pas': 'PAS'
        };
        
        const typeLabel = typeLabels[invoiceType] || 'SPP';
        if (invoiceType === 'spp') {
            document.getElementById('paymentMonthInfo').textContent = 'Bulan: ' + month;
        } else {
            document.getElementById('paymentMonthInfo').textContent = 'Tipe: ' + typeLabel;
        }
        
        document.getElementById('payment_month').value = monthNo;
        document.getElementById('invoice_type').value = invoiceType;
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('paymentModalLabel').textContent = 'Pembayaran ' + typeLabel;
        
        // Reset form
        document.getElementById('paymentForm').reset();
        document.getElementById('paid_at').value = '{{ \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}';
        document.getElementById('amount').value = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        document.getElementById('payment_month').value = monthNo;
        document.getElementById('invoice_type').value = invoiceType;
        const bankSelect = document.getElementById('bank_name');
        if (bankSelect) bankSelect.value = 'BRI';
        proofPreview.style.display = 'none';
        document.getElementById('ocrResults').style.display = 'none';
        document.getElementById('retryUploadSection').style.display = 'none';
        const ocrFailedAlert = document.getElementById('ocrFailedAlert');
        if (ocrFailedAlert) ocrFailedAlert.style.display = 'none';
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    };
    
    // Form submission
    const paymentForm = document.getElementById('paymentForm');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            // Check if proof is uploaded
            if (!proofPath.files || !proofPath.files.length) {
                alert('Harap upload bukti transfer!');
                return;
            }
            
            // Check if bank is selected
            if (!document.getElementById('bank_name').value) {
                alert('Harap pilih bank tujuan!');
                return;
            }
            
            // Prepare form data
            const formData = new FormData(this);
            
            // Convert amount from formatted string to integer
            const amountValue = document.getElementById('amount').value.replace(/\./g, '');
            formData.set('amount', amountValue);
            
            // Show loading
            const submitBtn = document.getElementById('submitPaymentBtn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            
            // Submit via AJAX
            fetch('{{ route("student.payment.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Server error');
                    });
                }
                return response.json();
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Close modal
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    
                    // Show success message
                    alert('✅ ' + data.message);
                    
                    // Reload page
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
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                console.error('Fetch error:', error);
                alert('❌ Terjadi kesalahan: ' + error.message);
            });
        });
    }
});
</script>

<!-- Real-time Payment Status Synchronizer -->
<script src="{{ asset('js/payment-status-sync.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize payment status synchronizer for student dashboard
    const studentId = {{ $student->id }};
    const invoiceYear = {{ $invoiceYear }};
    
    const paymentSynchronizer = new PaymentStatusSynchronizer({
        pollingInterval: 5000, // 5 seconds
        studentId: studentId,
        year: invoiceYear,
        baseUrl: '/student/api', // Use student API routes
        onStatusChange: function(event) {
            console.log('🔄 Payment status changed:', event);
        }
    });
    
    // Find all payment elements with data-payment-id and start polling
    const paymentElements = document.querySelectorAll('[data-payment-id]');
    paymentElements.forEach(el => {
        const paymentId = el.getAttribute('data-payment-id');
        if (paymentId) {
            console.log('📍 Starting sync for payment ID:', paymentId);
            paymentSynchronizer.startSyncPayment(paymentId);
        }
    });
    
    // Listen to custom event from synchronizer
    window.addEventListener('paymentStatusUpdated', function(e) {
        const { paymentId, newStatus, oldStatus, newData } = e.detail;
        console.log(`✅ Payment ${paymentId}: ${oldStatus} → ${newStatus}`);
        
        try {
            // Find the row/card with this payment
            const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
            if (row) {
                // Find status badge in this row
                const statusBadge = row.querySelector('.status-badge');
                if (statusBadge) {
                    // Update status badge based on new status
                    const statusHtml = getStatusBadgeHtmlStudent(newStatus, newData);
                    statusBadge.innerHTML = statusHtml;
                    
                    // Add animation
                    statusBadge.style.animation = 'pulse 0.5s ease-in-out';
                    
                    // Show toast notification
                    showPaymentStatusToast(newStatus, newData);
                }
            }
        } catch (error) {
            console.error('Error updating payment status:', error);
        }
    });
    
    // Get status badge HTML for student view
    window.getStatusBadgeHtmlStudent = function(status, paymentData) {
        const statusMap = {
            'verified': '<span class="badge px-3 py-2 w-100" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600; font-size: 0.75rem; display: block;"><i class="fas fa-check-circle me-1"></i>Verified</span>',
            'pending': '<span class="badge px-3 py-2 w-100 bg-warning text-dark fw-semibold" style="font-size: 0.75rem; display: block;"><i class="fas fa-hourglass-half me-1"></i>Menunggu</span>',
            'rejected': '<span class="badge px-3 py-2 w-100 bg-danger text-white fw-semibold" style="font-size: 0.75rem; display: block;"><i class="fas fa-times-circle me-1"></i>Ditolak</span>'
        };
        return statusMap[status] || '<span class="badge px-3 py-2 w-100 bg-secondary text-white" style="font-size: 0.75rem; display: block;"><i class="fas fa-clock me-1"></i>Belum</span>';
    };
    
    // Show toast notification
    window.showPaymentStatusToast = function(status, paymentData) {
        try {
            let message = '';
            let bgColor = '';
            let icon = '';
            
            if (status === 'verified') {
                message = '✅ Pembayaran berhasil diverifikasi!';
                bgColor = '#198754';
                icon = 'fas fa-check-circle';
            } else if (status === 'rejected') {
                message = '❌ Pembayaran ditolak. Silakan cek kembali.';
                bgColor = '#dc2626';
                icon = 'fas fa-times-circle';
            } else if (status === 'pending') {
                message = '⏳ Pembayaran menunggu verifikasi admin';
                bgColor = '#ffc107';
                icon = 'fas fa-hourglass-half';
            }
            
            if (message) {
                const toastHtml = `
                    <div class="toast" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <div class="toast-body" style="background: ${bgColor}; color: white; border-radius: 8px; padding: 16px;">
                            <i class="${icon} me-2"></i>
                            ${message}
                        </div>
                    </div>
                `;
                const toastEl = document.createElement('div');
                toastEl.innerHTML = toastHtml;
                document.body.appendChild(toastEl);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    toastEl.remove();
                }, 5000);
            }
        } catch (error) {
            console.error('Error showing toast:', error);
        }
    };
    
    // Request browser notification permission (optional)
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                console.log('✅ Browser notifications enabled');
            }
        });
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        paymentSynchronizer.stopAll();
        console.log('🛑 Payment sync stopped');
    });
});
</script>

@endif
@endsection
