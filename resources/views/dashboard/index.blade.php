@extends('layouts.app')

@section('title', 'Home - Dashboard Sekolah')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan informasi sekolah real-time')

@section('content')
<style>
    .stats-card {
        border-radius: 12px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        min-height: 160px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .stats-card .icon {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 3rem;
        opacity: 0.2;
    }
    .stats-number {
        font-size: 3.5rem;
        font-weight: bold;
        line-height: 1;
        margin: 8px 0;
    }
    .stats-label {
        font-size: 1rem;
        opacity: 0.95;
        margin-bottom: 4px;
        font-weight: 500;
    }
    .stats-detail {
        font-size: 0.85rem;
        opacity: 0.85;
        margin-top: 8px;
    }
    .info-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        color: white;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .info-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid #667eea;
    }
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }
    .info-image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .info-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .chart-container {
        position: relative;
        height: 320px;
        width: 100%;
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    .realtime-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .activity-item {
        background: white;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border-left: 4px solid #3b82f6;
        transition: all 0.3s ease;
    }
    .activity-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    @media (max-width: 575.98px) {
        .stats-card {
            padding: 16px;
            min-height: auto;
        }
        .stats-card .icon {
            right: 14px;
            top: 14px;
            font-size: 2.25rem;
        }
        .chart-container {
            height: 240px;
            padding: 16px;
        }
        .info-section {
            padding: 18px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Welcome Header dengan Real-time Badge -->
    <div class="mb-4">
        <div class="bg-white rounded-3 shadow-sm p-4" style="border-left: 4px solid #667eea;">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h2 class="fs-4 fw-bold text-dark mb-1">
                        
                        Halaman Utama Dashboard
                    </h2>
                    <p class="text-muted mb-0">Data real-time dari database - Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}</p>
                </div>
                <div class="realtime-badge flex-shrink-0">
                    <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                    <span>Live Data</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards: Data Siswa Per Jurusan dengan Distribusi Kelas -->
    <div class="row g-4 mb-4">
        @php
        $majorColors = [
            'Pemasaran' => ['from' => '#ec4899', 'to' => '#be185d', 'icon' => 'fa-store'],
            'Teknik Komputer dan Jaringan' => ['from' => '#8b5cf6', 'to' => '#6d28d9', 'icon' => 'fa-laptop-code'],
            'Teknik Bisnis Sepeda Motor' => ['from' => '#f59e0b', 'to' => '#d97706', 'icon' => 'fa-motorcycle'],
        ];
        
        $jurusanData = [
            'Pemasaran' => [
                'total' => $jurusanPemasaran,
                'grades' => []
            ],
            'Teknik Komputer dan Jaringan' => [
                'total' => $jurusanTKJ,
                'grades' => []
            ],
            'Teknik Bisnis Sepeda Motor' => [
                'total' => $jurusanTBSM,
                'grades' => []
            ]
        ];
        
        // Get grade distribution for each major
        foreach ($jurusanData as $major => $data) {
            $gradeData = DB::table('students')
                ->selectRaw('current_grade_level, COUNT(*) as count')
                ->where('major', $major)
                ->whereNotNull('current_grade_level')
                ->groupBy('current_grade_level')
                ->pluck('count', 'current_grade_level')
                ->toArray();
            
            $jurusanData[$major]['grades'] = [
                '10' => $gradeData[10] ?? 0,
                '11' => $gradeData[11] ?? 0,
                '12' => $gradeData[12] ?? 0,
            ];
        }
        @endphp

        @foreach($jurusanData as $major => $data)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="position-relative overflow-hidden rounded-4 p-4 h-100" style="background: linear-gradient(135deg, {{ $majorColors[$major]['from'] }} 0%, {{ $majorColors[$major]['to'] }} 100%); box-shadow: 0 8px 16px rgba(0,0,0,0.15); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.25)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)'">
                <!-- Header -->
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-white border-opacity-25">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 48px; height: 48px; backdrop-filter: blur(10px);">
                        <i class="fas {{ $majorColors[$major]['icon'] }} text-white" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-0 fw-bold" style="font-size: 0.95rem; letter-spacing: 0.3px;">{{ $major }}</p>
                        <p class="text-white mb-0 fw-medium" style="font-size: 0.75rem; opacity: 0.8;">Distribusi per Kelas</p>
                    </div>
                </div>

                <!-- Grade Distribution -->
                <div class="row g-2 mb-3">
                    @foreach(['10', '11', '12'] as $grade)
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 rounded-3 p-2 text-center" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                            <p class="text-white mb-1 fw-semibold" style="font-size: 0.7rem; opacity: 0.9;">KELAS {{ $grade }}</p>
                            <h4 class="fs-5 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);">{{ $data['grades'][$grade] }}</h4>
                            <p class="text-white mb-0" style="font-size: 0.65rem; opacity: 0.7;">siswa</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="pt-3 border-top border-white border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-white fw-semibold" style="font-size: 0.85rem; opacity: 0.9;">Total Siswa:</span>
                        <span class="text-white fw-bold" style="font-size: 1.1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">{{ $data['total'] }} siswa</span>
                    </div>
                </div>

                <!-- Background Decoration -->
                <div class="position-absolute" style="right: -30px; bottom: -30px; opacity: 0.08;">
                    <i class="fas {{ $majorColors[$major]['icon'] }}" style="font-size: 8rem;"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Stats Cards Row 2: Statistik Keuangan Real-time -->
    <div class="row g-4 mb-4">
        <!-- Total Tagihan -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <i class="fas fa-file-invoice-dollar icon"></i>
                <p class="stats-label">Total Tagihan</p>
                <div class="stats-number" style="font-size: 1.6rem;">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                <div class="stats-detail">
                    <i class="fas fa-database me-1"></i> {{ number_format($invoiceLunas + $invoiceBelumLunas) }} invoice
                </div>
            </div>
        </div>

        <!-- Total Terbayar -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fas fa-check-circle icon"></i>
                <p class="stats-label">Total Terbayar</p>
                <div class="stats-number" style="font-size: 1.6rem;">Rp {{ number_format($totalTerbayar, 0, ',', '.') }}</div>
                <div class="stats-detail">
                    <i class="fas fa-percentage me-1"></i> {{ number_format($persentasePembayaran, 1) }}% dari tagihan
                </div>
            </div>
        </div>

        <!-- Total Tunggakan -->
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <i class="fas fa-exclamation-triangle icon"></i>
                <p class="stats-label">Total Tunggakan</p>
                <div class="stats-number" style="font-size: 1.6rem;">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</div>
                <div class="stats-detail">
                    <i class="fas fa-file-excel me-1"></i> {{ number_format($invoiceBelumLunas) }} invoice belum lunas
                </div>
            </div>
        </div>

        <!-- Pembayaran Pending -->
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('dashboard.keuangan.pending') }}" class="text-decoration-none" style="color: inherit;">
            <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); cursor: pointer;" title="Lihat daftar pembayaran pending">
                <i class="fas fa-clock icon"></i>
                <p class="stats-label">Menunggu Verifikasi</p>
                <div class="stats-number" style="font-size: 2.5rem;">{{ number_format($pembayaranPending) }}</div>
                <div class="stats-detail">
                    <i class="fas fa-hourglass-half me-1"></i> Pembayaran pending
                </div>
            </div>
            </a>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Statistik Keuangan Chart -->
        <div class="col-12">
            <div class="chart-container">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fas fa-chart-bar me-2" style="color: #10b981;"></i>
                    Pembayaran SPP per Bulan (Tahun {{ date('Y') }})
                </h6>
                <canvas id="keuanganChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Informasi & Pengumuman Section -->
    <div class="info-section">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-bullhorn me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <h5 class="mb-0 fw-bold">Informasi & Pengumuman Terbaru</h5>
                    <small style="opacity: 0.9;">Data real-time dari database</small>
                </div>
            </div>
            <a href="{{ route('information.index') }}" class="btn btn-light btn-sm">
                <i class="fas fa-cog me-1"></i> Kelola Informasi
            </a>
        </div>

        <div class="row">
            @if($informasiTerbaru && $informasiTerbaru->count() > 0)
                @foreach($informasiTerbaru->take(3) as $info)
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="info-card">
                        <div class="info-image">
                            @if($info->image_path)
                                <img src="{{ asset('storage/' . $info->image_path) }}" alt="{{ $info->title }}">
                            @else
                                <div class="text-center">
                                    <i class="fas fa-bullhorn mb-2" style="font-size: 2.5rem;"></i>
                                    <div>Informasi</div>
                                </div>
                            @endif
                        </div>
                        <h6 class="fw-bold text-dark mb-2">{{ Str::limit($info->title, 50) }}</h6>
                        <p class="text-muted small mb-3" style="line-height: 1.6;">{{ Str::limit(strip_tags($info->body), 100) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                {{ \Carbon\Carbon::parse($info->created_at)->format('d M Y') }}
                            </small>
                            <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-circle" style="font-size: 6px;"></i> Aktif
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="info-card text-center py-5">
                        <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3.5rem; opacity: 0.3;"></i>
                        <h6 class="text-muted fw-bold">Belum ada informasi terbaru</h6>
                        <p class="text-muted small mb-3">Tambahkan pengumuman atau informasi untuk ditampilkan di sini</p>
                        <a href="{{ route('information.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Informasi Baru
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>


    <!-- Footer Info -->
    <div class="mt-4">
        <div class="bg-white rounded-3 shadow-sm p-3">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div class="text-muted small">
                    <i class="fas fa-sync-alt me-2"></i>
                    Data diperbarui secara real-time dari database
                </div>
                <div class="text-muted small">
                    <i class="fas fa-clock me-2"></i>
                    Terakhir dimuat: {{ now()->format('d/m/Y H:i:s') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Keuangan Chart (Bar) dengan format Rupiah
const keuanganCtx = document.getElementById('keuanganChart').getContext('2d');
const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const pembayaranData = @json($pembayaranPerBulan);
const pembayaranValues = [];

// Prepare data for 12 months
for (let i = 1; i <= 12; i++) {
    pembayaranValues.push(pembayaranData[i] || 0);
}

const keuanganChart = new Chart(keuanganCtx, {
    type: 'bar',
    data: {
        labels: monthNames,
        datasets: [{
            label: 'Pembayaran SPP',
            data: pembayaranValues,
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                    }
                },
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                cornerRadius: 8
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                        } else if (value >= 1000) {
                            return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                        }
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    },
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});

console.log('Dashboard loaded with real-time data');
console.log('Pemasaran:', {{ $jurusanPemasaran }});
console.log('TKJ:', {{ $jurusanTKJ }});
console.log('TBSM:', {{ $jurusanTBSM }});
console.log('Total Tagihan:', 'Rp {{ number_format($totalTagihan) }}');
console.log('Total Terbayar:', 'Rp {{ number_format($totalTerbayar) }}');
console.log('Total Tunggakan:', 'Rp {{ number_format($totalTunggakan) }}');
</script>
@endsection
