@extends('layouts.app')

@section('title', 'Menunggu Verifikasi')

@section('content')
@php
    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $typeLabels = [
        'spp' => 'SPP',
        'uniform' => 'Seragam',
        'pts' => 'PTS',
        'pas' => 'PAS',
    ];
@endphp

<div class="container-fluid">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-0">Menunggu Verifikasi</h4>
            <div class="text-muted small">Daftar pembayaran yang masih berstatus pending</div>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            @if ($pendingPayments->count() === 0)
                <div class="p-4 text-muted">Tidak ada pembayaran yang menunggu verifikasi.</div>
            @else
                <!-- Table (md+) -->
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;">NIS</th>
                                <th>Nama Siswa</th>
                                <th style="width: 120px;">Kelas</th>
                                <th style="width: 180px;">Jenis</th>
                                <th style="width: 140px;">Periode</th>
                                <th style="width: 140px;" class="text-end">Nominal</th>
                                <th style="width: 120px;">Metode</th>
                                <th style="width: 170px;">Waktu</th>
                                <th style="width: 120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingPayments as $row)
                                @php
                                    $monthLabel = $monthNames[(int) $row->invoice_month] ?? ('Bulan ' . $row->invoice_month);
                                    $typeLabel = $typeLabels[$row->invoice_type] ?? strtoupper($row->invoice_type);

                                    if ($row->invoice_type === 'uniform' && !empty($row->invoice_subtype)) {
                                        $typeLabel .= ' (' . strtoupper($row->invoice_subtype) . ')';
                                    }

                                    $time = $row->paid_at ?: $row->payment_created_at;
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $row->nis }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->student_name }}</div>
                                        <div class="text-muted small">{{ $row->major ?: '-' }}</div>
                                    </td>
                                    <td>{{ $row->current_grade_level ? 'Kelas ' . $row->current_grade_level : '-' }}</td>
                                    <td>{{ $typeLabel }}</td>
                                    <td>{{ $monthLabel }} {{ $row->invoice_year }}</td>
                                    <td class="text-end">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
                                    <td>{{ strtoupper($row->method) }}</td>
                                    <td class="text-muted small">
                                        {{ $time ? \Carbon\Carbon::parse($time)->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('dashboard.keuangan.detail', ['id' => $row->student_id, 'year' => $row->invoice_year]) }}" class="btn btn-primary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards (sm) -->
                <div class="d-md-none p-3">
                    <div class="d-flex flex-column gap-3">
                        @foreach ($pendingPayments as $row)
                            @php
                                $monthLabel = $monthNames[(int) $row->invoice_month] ?? ('Bulan ' . $row->invoice_month);
                                $typeLabel = $typeLabels[$row->invoice_type] ?? strtoupper($row->invoice_type);

                                if ($row->invoice_type === 'uniform' && !empty($row->invoice_subtype)) {
                                    $typeLabel .= ' (' . strtoupper($row->invoice_subtype) . ')';
                                }

                                $time = $row->paid_at ?: $row->payment_created_at;
                            @endphp

                            <div class="rounded-3 shadow-sm border bg-white overflow-hidden">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold" style="font-size: 0.95rem;">{{ $row->student_name }}</div>
                                            <div class="text-muted small">NIS {{ $row->nis }} • {{ $row->current_grade_level ? 'Kelas ' . $row->current_grade_level : '-' }}</div>
                                            <div class="text-muted small">{{ $row->major ?: '-' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">Rp {{ number_format($row->amount, 0, ',', '.') }}</div>
                                            <div class="text-muted small">{{ strtoupper($row->method) }}</div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-2">
                                        <div class="col-6">
                                            <div class="text-muted small">Jenis</div>
                                            <div class="fw-semibold" style="font-size: 0.9rem;">{{ $typeLabel }}</div>
                                        </div>
                                        <div class="col-6 text-end">
                                            <div class="text-muted small">Periode</div>
                                            <div class="fw-semibold" style="font-size: 0.9rem;">{{ $monthLabel }} {{ $row->invoice_year }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-muted small">
                                        Waktu: {{ $time ? \Carbon\Carbon::parse($time)->format('d/m/Y H:i') : '-' }}
                                    </div>

                                    <div class="d-grid mt-3">
                                        <a href="{{ route('dashboard.keuangan.detail', ['id' => $row->student_id, 'year' => $row->invoice_year]) }}" class="btn btn-primary btn-sm">
                                            Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if ($pendingPayments->hasPages())
            <div class="card-footer bg-white">
                {{ $pendingPayments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
