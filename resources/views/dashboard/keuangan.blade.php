@extends('layouts.app')

@section('title', 'Data Keuangan Siswa - Dashboard Sekolah')
@section('page-title', 'Data Keuangan Siswa')
@section('page-subtitle', 'Data keuangan dibuat otomatis saat menambah siswa baru | SPP Kelas 10: Rp 200rb/bln • Kelas 11-12: Rp 190rb/bln')

@section('content')
<div class="mb-4">
    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Berhasil!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Stats Summary -->
    <div class="row g-3 g-md-4 mb-4">
        <!-- Total Tagihan -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(59, 130, 246, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(59, 130, 246, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-file-invoice-dollar text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TOTAL TAGIHAN</p>
                        <h3 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Rp {{ number_format($totalBill, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <!-- Total Terbayar -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); box-shadow: 0 8px 16px rgba(25, 135, 84, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(25, 135, 84, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(25, 135, 84, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-money-bill-wave text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TOTAL TERBAYAR</p>
                        <h3 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Rp {{ number_format($totalPaid, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-money-bill-wave" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <!-- Tunggakan -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg,  #fb2424d0 0%, #f50b0be1 100%); box-shadow: 0 8px 16px rgba(251, 191, 36, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(251, 191, 36, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(251, 191, 36, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-exclamation-triangle text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TUNGGAKAN</p>
                        <h3 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Rp {{ number_format($totalRemaining, 0, ',', '.') }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-4 overflow-hidden mb-4" style="box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(59, 130, 246, 0.1);">
        <!-- Card Header -->
        <div class="px-3 px-md-4 py-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); position: relative; overflow: hidden;">
            <div class="position-absolute" style="top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
            <div class="row align-items-center g-3">
                <div class="col-12 col-lg-4 mb-2 mb-lg-0">
                    <h2 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <i class="fas fa-wallet me-2" style="font-size: 1.3rem;"></i>
                        Data Keuangan Siswa
                    </h2>
                </div>
                <div class="col-12 col-lg-8">
                    <form method="GET" action="{{ route('dashboard.keuangan') }}" class="row g-2">
                        <div class="col-12 col-sm-4 col-md-3">
                            <div class="position-relative">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIS / Nama..." class="form-control ps-5 border-0" style="background: rgba(255,255,255,0.95); font-size: 0.9rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 10px 16px 10px 45px;">
                                <i class="fas fa-search position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); font-size: 0.95rem; color: #3b82f6;"></i>
                            </div>
                        </div>
                        <div class="col-12 col-sm-2 col-md-2">
                            <select name="sort" class="form-select border-0 shadow-sm" style="font-size: 0.9rem; border-radius: 12px; background: rgba(255,255,255,0.95); padding: 10px 16px;">
                                <option value="az" {{ ($sortFilter ?? 'az') === 'az' ? 'selected' : '' }}>A - Z</option>
                                <option value="za" {{ ($sortFilter ?? 'az') === 'za' ? 'selected' : '' }}>Z - A</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-md-2">
                            <select name="invoice_type" class="form-select border-0 shadow-sm" style="font-size: 0.9rem; border-radius: 12px; background: rgba(255,255,255,0.95); padding: 10px 16px;">
                                @foreach($invoiceTypes as $type)
                                    <option value="{{ $type['value'] }}" {{ $invoiceTypeFilter == $type['value'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-md-2">
                            <select name="grade_level" class="form-select border-0 shadow-sm" style="font-size: 0.9rem; border-radius: 12px; background: rgba(255,255,255,0.95); padding: 10px 16px;">
                                <option value="">Semua Kelas</option>
                                @foreach($grades as $gradeLevel)
                                    <option value="{{ $gradeLevel }}" {{ request('grade_level') == $gradeLevel ? 'selected' : '' }}>Kelas {{ $gradeLevel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-md-2">
                            <select name="year" class="form-select border-0 shadow-sm" style="font-size: 0.9rem; border-radius: 12px; background: rgba(255,255,255,0.95); padding: 10px 16px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-md-2">
                            <button type="submit" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(251, 191, 36, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Print Options Tabs -->
        <div class="px-3 px-md-4 py-3" style="background: rgba(59, 130, 246, 0.03); border-bottom: 1px solid rgba(59, 130, 246, 0.1);">
            <ul class="nav nav-tabs border-bottom-0" role="tablist" style="gap: 1rem;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-3 py-2 fw-semibold" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly-pane" type="button" style="border: none; border-bottom: 3px solid transparent; color: #6b7280; transition: all 0.2s ease;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#6b7280'">
                        <i class="fas fa-calendar-days me-2" style="font-size: 0.85rem;"></i>Bulanan
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 py-2 fw-semibold" id="semester-tab" data-bs-toggle="tab" data-bs-target="#semester-pane" type="button" style="border: none; border-bottom: 3px solid transparent; color: #6b7280; transition: all 0.2s ease;" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#6b7280'">
                        <i class="fas fa-book me-2" style="font-size: 0.85rem;"></i>Semester
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 py-2 fw-semibold" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly-pane" type="button" style="border: none; border-bottom: 3px solid transparent; color: #6b7280; transition: all 0.2s ease;" onmouseover="this.style.color='#f59e0b'" onmouseout="this.style.color='#6b7280'">
                        <i class="fas fa-calendar me-2" style="font-size: 0.85rem;"></i>Tahunan
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <!-- Monthly Tab -->
                <div class="tab-pane fade show active" id="monthly-pane" role="tabpanel" tabindex="0">
                    <form method="GET" target="_blank" action="{{ route('dashboard.keuangan.print') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="scope" value="monthly">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-dark fw-semibold mb-2" style="font-size: 0.85rem;">Bulan</label>
                            <select name="month" class="form-select border-0 shadow-sm" style="border-radius: 8px;">
                                @php
                                    $monthOptions = [
                                        1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober',
                                        5 => 'November', 6 => 'Desember', 7 => 'Januari', 8 => 'Februari',
                                        9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
                                    ];
                                @endphp
                                @foreach($monthOptions as $monthNo => $monthLabel)
                                    <option value="{{ $monthNo }}" {{ $monthNo == 1 ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-dark fw-semibold mb-2" style="font-size: 0.85rem;">Tahun</label>
                            <select name="year" class="form-select border-0 shadow-sm" style="border-radius: 8px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn w-100 text-white fw-semibold" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border-radius: 8px; border: none; padding: 10px;">
                                <i class="fas fa-print me-2"></i>Print Laporan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Semester Tab -->
                <div class="tab-pane fade" id="semester-pane" role="tabpanel" tabindex="0">
                    <form method="GET" target="_blank" action="{{ route('dashboard.keuangan.print') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="scope" value="semester">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-dark fw-semibold mb-2" style="font-size: 0.85rem;">Semester</label>
                            <select name="semester" class="form-select border-0 shadow-sm" style="border-radius: 8px;">
                                <option value="ganjil">Ganjil (Jul-Des)</option>
                                <option value="genap">Genap (Jan-Jun)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-dark fw-semibold mb-2" style="font-size: 0.85rem;">Tahun</label>
                            <select name="year" class="form-select border-0 shadow-sm" style="border-radius: 8px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn w-100 text-white fw-semibold" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 8px; border: none; padding: 10px;">
                                <i class="fas fa-print me-2"></i>Print Semester
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Yearly Tab -->
                <div class="tab-pane fade" id="yearly-pane" role="tabpanel" tabindex="0">
                    <form method="GET" target="_blank" action="{{ route('dashboard.keuangan.print') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="scope" value="yearly">
                        <div class="col-12 col-md-6">
                            <label class="form-label text-dark fw-semibold mb-2" style="font-size: 0.85rem;">Tahun</label>
                            <select name="year" class="form-select border-0 shadow-sm" style="border-radius: 8px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <button type="submit" class="btn w-100 text-white fw-semibold" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 8px; border: none; padding: 10px;">
                                <i class="fas fa-print me-2"></i>Print Tahunan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="p-3 p-md-4">
            <!-- Desktop Table -->
            <div class="d-none d-lg-block table-responsive">
                <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0;">
                    <thead style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%); position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; width: 50px; letter-spacing: 0.3px; border: none;">No</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">NIS</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Nama Siswa</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Kelas</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Tahun Ajaran</th>
                            <th class="py-3 fw-bold text-dark text-end" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Total Tagihan</th>
                            <th class="py-3 fw-bold text-dark text-end" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Total Bayar</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none; min-width: 140px;">Progress</th>
                            <th class="py-3 fw-bold text-dark text-center" style="font-size: 0.85rem; width: 100px; letter-spacing: 0.3px; border: none;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index => $payment)
                        <tr style="border-bottom: 1px solid rgba(59, 130, 246, 0.08); transition: all 0.2s ease;" onmouseover="this.style.background='linear-gradient(90deg, rgba(59, 130, 246, 0.03) 0%, rgba(29, 78, 216, 0.03) 100%)'" onmouseout="this.style.background='transparent'">
                            <td class="text-dark fw-medium" style="font-size: 0.9rem;">{{ $index + 1 }}</td>
                            <td>
                                <code class="small px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%); color: #3b82f6; font-weight: 600; border: 1px solid rgba(59, 130, 246, 0.2);">{{ $payment->student->nis }}</code>
                            </td>
                            <td>
                                <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $payment->student->name }}</span>
                            </td>
                            <td>
                                @if($payment->student->current_grade_level)
                                    <span class="text-dark fw-semibold" style="font-size: 0.9rem;">Kelas {{ $payment->student->current_grade_level }} </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.9rem;">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);">
                                    {{ $payment->year }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-dark" style="font-size: 0.9rem;">Rp {{ number_format($payment->total_bill, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold" style="font-size: 0.9rem; color: #198754;">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-3">
                                <div class="d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-semibold" style="color: {{ $payment->payment_percentage >= 100 ? '#198754' : ($payment->payment_percentage > 0 ? '#f59e0b' : '#dc3545') }}; font-size: 0.75rem;">
                                            {{ $payment->payment_percentage }}%
                                        </span>
                                        <span class="small" style="color: #6b7280; font-size: 0.7rem;">
                                            @if($payment->payment_percentage >= 100)
                                                Lunas
                                            @elseif($payment->payment_percentage > 0)
                                                Cicilan
                                            @else
                                                Belum
                                            @endif
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 8px; border-radius: 10px; background: rgba(59, 130, 246, 0.1);">
                                        <div class="progress-bar" role="progressbar" 
                                            style="width: {{ $payment->payment_percentage }}%; 
                                                   background: {{ $payment->payment_percentage >= 100 ? 'linear-gradient(90deg, #198754 0%, #15803d 100%)' : ($payment->payment_percentage > 0 ? 'linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%)' : 'linear-gradient(90deg, #dc3545 0%, #b02a37 100%)') }}; 
                                                   border-radius: 10px; 
                                                   transition: width 0.5s ease;"
                                            aria-valuenow="{{ $payment->payment_percentage }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('dashboard.keuangan.detail', ['id' => $payment->student_id, 'year' => $payment->year]) }}" class="btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center mx-auto" title="Lihat Detail" 
                                    style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; transition: all 0.2s ease; text-decoration: none;"
                                    onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'"
                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                    <i class="fas fa-eye text-white" style="font-size: 0.85rem;"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="py-4">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                                        <i class="fas fa-wallet" style="font-size: 2.5rem; color: #3b82f6;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-2">Belum Ada Data Keuangan</h6>
                                    <p class="text-muted small mb-0">Data keuangan akan muncul setelah menambahkan siswa</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse

                        @if(count($payments) > 0)
                        <!-- Summary Row -->
                        <tr style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(29, 78, 216, 0.05) 100%); border-top: 2px solid rgba(59, 130, 246, 0.2);">
                            <td colspan="5" class="py-3">
                                <span class="fw-bold text-dark" style="font-size: 1rem; letter-spacing: 0.5px;">TOTAL</span>
                            </td>
                            <td class="text-end py-3">
                                <span class="fw-bold text-dark" style="font-size: 1rem;">Rp {{ number_format($totalBill, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-end py-3">
                                <span class="fw-bold" style="font-size: 1rem; color: #198754;">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </td>
                            <td class="text-center py-3" colspan="2">
                                <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600; font-size: 0.9rem;">
                                    Rata-rata: {{ $averageProgress }}%
                                </span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="d-lg-none">
                <div class="d-flex flex-column gap-3">
                    @forelse($payments as $index => $payment)
                    <div class="rounded-4 overflow-hidden shadow-sm border" style="border-color: rgba(59, 130, 246, 0.15) !important; transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(59, 130, 246, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.08)'">
                        <!-- Card Header -->
                        <div class="px-3 py-3" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                            <div>
                                <code class="small px-2 py-1 rounded" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">{{ $payment->student->nis }}</code>
                            </div>
                            <h3 class="fs-6 fw-bold text-white mb-1 mt-2">{{ $payment->student->name }}</h3>
                            <p class="text-white small mb-0" style="opacity: 0.9;">
                                @if($payment->student->current_grade_level)
                                    Kelas {{ $payment->student->current_grade_level }} - {{ $payment->student->major }}
                                @else
                                    -
                                @endif
                            </p>
                        </div>

                        <!-- Card Body -->
                        <div class="p-3 bg-white">
                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Tahun</p>
                                    <p class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">{{ $payment->year }}</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="text-secondary small mb-0" style="font-size: 0.75rem;">Progress Pembayaran</p>
                                    <p class="fw-bold mb-0" style="font-size: 0.85rem; color: {{ $payment->payment_percentage >= 100 ? '#198754' : ($payment->payment_percentage > 0 ? '#f59e0b' : '#dc3545') }};">
                                        {{ $payment->payment_percentage }}%
                                    </p>
                                </div>
                                <div class="progress" style="height: 10px; border-radius: 10px; background: rgba(59, 130, 246, 0.1);">
                                    <div class="progress-bar" role="progressbar" 
                                        style="width: {{ $payment->payment_percentage }}%; 
                                               background: {{ $payment->payment_percentage >= 100 ? 'linear-gradient(90deg, #198754 0%, #15803d 100%)' : ($payment->payment_percentage > 0 ? 'linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%)' : 'linear-gradient(90deg, #dc3545 0%, #b02a37 100%)') }}; 
                                               border-radius: 10px;"
                                        aria-valuenow="{{ $payment->payment_percentage }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Total Tagihan</p>
                                    <p class="fw-bold text-dark mb-0">Rp {{ number_format($payment->total_bill, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <p class="text-secondary small mb-1" style="font-size: 0.75rem;">Total Terbayar</p>
                                    <p class="fw-bold mb-0" style="color: #198754;">Rp {{ number_format($payment->total_paid, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="d-grid">
                                <a href="{{ route('dashboard.keuangan.detail', ['id' => $payment->student_id, 'year' => $payment->year]) }}" class="btn btn-sm fw-semibold" 
                                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 10px; border-radius: 8px; text-decoration: none;">
                                    <i class="fas fa-eye me-2"></i>Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                            <i class="fas fa-wallet" style="font-size: 2.5rem; color: #3b82f6;"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Belum Ada Data Keuangan</h6>
                        <p class="text-muted small mb-0">Data keuangan akan muncul setelah menambahkan siswa</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-3 mt-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-3 mb-3">
                    <p class="small text-secondary mb-0">
                        Menampilkan <strong>{{ $payments->count() }}</strong> dari <strong>{{ $payments->total() }}</strong> siswa
                        @if($payments->total() > 0)
                            (Halaman <strong>{{ $payments->currentPage() }}</strong> dari <strong>{{ $payments->lastPage() }}</strong>)
                        @endif
                    </p>
                </div>

                @if($payments->hasPages())
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    {{-- Tombol Previous --}}
                    @if($payments->onFirstPage())
                    <button class="btn btn-sm rounded-3 px-3 py-2" disabled style="background-color: #e9ecef; color: #adb5bd; cursor: not-allowed; border: none;">
                        <i class="fas fa-chevron-left me-2" style="font-size: 0.85rem;"></i>
                        <span class="d-none d-sm-inline">Sebelumnya</span>
                    </button>
                    @else
                    <a href="{{ $payments->previousPageUrl() }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                        <i class="fas fa-chevron-left me-2" style="font-size: 0.85rem;"></i>
                        <span class="d-none d-sm-inline">Sebelumnya</span>
                    </a>
                    @endif

                    {{-- Nomor Halaman --}}
                    <div class="d-flex gap-1 flex-wrap justify-content-center">
                        @foreach($payments->getUrlRange(1, $payments->lastPage()) as $page => $url)
                            @if($page >= $payments->currentPage() - 2 && $page <= $payments->currentPage() + 2)
                                @if($page == $payments->currentPage())
                                <button class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); min-width: 40px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
                                    {{ $page }}
                                </button>
                                @else
                                <a href="{{ $url }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 shadow-sm" style="background: #f3f4f6; color: #374151; transition: all 0.2s ease; min-width: 40px; text-decoration: none;" onmouseover="this.style.background='#e5e7eb'; this.style.color='#1f2937'" onmouseout="this.style.background='#f3f4f6'; this.style.color='#374151'">
                                    {{ $page }}
                                </a>
                                @endif
                            @endif
                        @endforeach
                    </div>

                    {{-- Tombol Next --}}
                    @if($payments->hasMorePages())
                    <a href="{{ $payments->nextPageUrl() }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                        <span class="d-none d-sm-inline">Berikutnya</span>
                        <i class="fas fa-chevron-right ms-2" style="font-size: 0.85rem;"></i>
                    </a>
                    @else
                    <button class="btn btn-sm rounded-3 px-3 py-2" disabled style="background-color: #e9ecef; color: #adb5bd; cursor: not-allowed; border: none;">
                        <span class="d-none d-sm-inline">Berikutnya</span>
                        <i class="fas fa-chevron-right ms-2" style="font-size: 0.85rem;"></i>
                    </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    /* Custom Tab Styling */
    .nav-link.active {
        color: #3b82f6 !important;
        border-bottom-color: #3b82f6 !important;
    }
    
    #semester-tab.active {
        color: #10b981 !important;
        border-bottom-color: #10b981 !important;
    }
    
    #yearly-tab.active {
        color: #f59e0b !important;
        border-bottom-color: #f59e0b !important;
    }
    
    .nav-link {
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        font-weight: 600;
    }
</style>

