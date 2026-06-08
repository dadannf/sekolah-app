@extends('layouts.app')

@section('title', 'Informasi Sekolah - Dashboard Sekolah')
@section('page-title', 'Informasi Sekolah')
@section('page-subtitle', 'Detail lengkap profil sekolah')

@section('content')
<div class="d-flex flex-column gap-4">
    <!-- School Header Card -->
    <div class="bg-white rounded-3 shadow overflow-hidden">
        <div class="school-hero text-white text-center" style="background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);">
            <div class="school-hero-avatar mx-auto rounded-circle d-flex align-items-center justify-content-center mb-4" style="background-color: rgba(255, 255, 255, 0.2);">
                <i class="fas fa-school display-4"></i>
            </div>
            <h1 class="display-6 fw-bold mb-2">{{ $sekolah['nama'] }}</h1>
            <p class="opacity-75">NPSN: {{ $sekolah['npsn'] }}</p>
            <div class="mt-3 d-inline-flex align-items-center bg-warning text-dark px-4 py-2 rounded-pill fw-semibold">
                <i class="fas fa-star me-2"></i>
                Akreditasi {{ $sekolah['akreditasi'] }}
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="row g-3 g-lg-4">
        <!-- Contact Details -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow p-4">
                <h3 class="fs-5 fw-bold text-dark mb-4">
                    <i class="fas fa-address-card me-2" style="color: #6d28d9;"></i>
                    Informasi Kontak
                </h3>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1">Alamat</p>
                            <p class="text-dark fw-medium mb-0">{{ $sekolah['alamat'] }}</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-phone text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1">Telepon</p>
                            <p class="text-dark fw-medium mb-0">{{ $sekolah['telepon'] }}</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: rgba(109, 40, 217, 0.1);">
                            <i class="fas fa-envelope" style="color: #6d28d9;"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1">Email</p>
                            <p class="text-dark fw-medium mb-0">{{ $sekolah['email'] }}</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-user-tie text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-1">Kepala Sekolah</p>
                            <p class="text-dark fw-medium mb-0">{{ $sekolah['kepala_sekolah'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow p-4">
                <h3 class="fs-5 fw-bold text-dark mb-4">
                    <i class="fas fa-chart-bar me-2" style="color: #6d28d9;"></i>
                    Statistik Sekolah
                </h3>
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-3 border-start border-4 border-primary" style="background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px;">
                                    <i class="fas fa-user-graduate fs-5"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Total Siswa</p>
                                    <p class="fs-4 fw-bold text-dark mb-0">{{ $sekolah['jumlah_siswa'] }}</p>
                                </div>
                            </div>
                            <i class="fas fa-arrow-up text-primary"></i>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 border-start border-4 border-success" style="background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.1) 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px;">
                                    <i class="fas fa-chalkboard-teacher fs-5"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Total Guru</p>
                                    <p class="fs-4 fw-bold text-dark mb-0">{{ $sekolah['jumlah_guru'] }}</p>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 border-start border-4 border-start border-4" style="border-color: #6d28d9 !important; background: linear-gradient(90deg, rgba(109, 40, 217, 0.05) 0%, rgba(109, 40, 217, 0.1) 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background-color: #6d28d9;">
                                    <i class="fas fa-door-open fs-5"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Total Kelas</p>
                                    <p class="fs-4 fw-bold text-dark mb-0">{{ $sekolah['jumlah_kelas'] }}</p>
                                </div>
                            </div>
                            <i class="fas fa-building" style="color: #6d28d9;"></i>
                        </div>
                    </div>

                    <div class="p-3 rounded-3 border-start border-4 border-warning" style="background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.1) 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px;">
                                    <i class="fas fa-trophy fs-5"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-0">Akreditasi</p>
                                    <p class="fs-4 fw-bold text-dark mb-0">{{ $sekolah['akreditasi'] }}</p>
                                </div>
                            </div>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Facilities & Achievement -->
    <div class="row g-3 g-lg-4">
        <!-- Facilities -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow p-4">
                <h3 class="fs-5 fw-bold text-dark mb-4">
                    <i class="fas fa-building me-2" style="color: #6d28d9;"></i>
                    Fasilitas Sekolah
                </h3>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 bg-primary bg-opacity-10 rounded-3">
                            <i class="fas fa-book text-primary fs-5"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Perpustakaan</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">10,000+ Koleksi</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 bg-success bg-opacity-10 rounded-3">
                            <i class="fas fa-flask text-success fs-5"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Lab. IPA</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">3 Laboratorium</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 rounded-3" style="background-color: rgba(109, 40, 217, 0.1);">
                            <i class="fas fa-desktop fs-5" style="color: #6d28d9;"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Lab. Komputer</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">2 Laboratorium</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 bg-warning bg-opacity-10 rounded-3">
                            <i class="fas fa-futbol text-warning fs-5"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Lap. Olahraga</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">Multi Fungsi</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 bg-danger bg-opacity-10 rounded-3">
                            <i class="fas fa-mosque text-danger fs-5"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Musholla</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">Kapasitas 200</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-3 bg-info bg-opacity-10 rounded-3">
                            <i class="fas fa-utensils text-info fs-5"></i>
                            <div>
                                <p class="fw-semibold text-dark small mb-0">Kantin</p>
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">Sehat & Bersih</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Achievements -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow p-4">
                <h3 class="fs-5 fw-bold text-dark mb-4">
                    <i class="fas fa-award me-2" style="color: #6d28d9;"></i>
                    Prestasi Terkini
                </h3>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border-start border-4 border-warning" style="background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.1) 100%);">
                        <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-trophy text-white"></i>
                        </div>
                        <div>
                            <h4 class="fw-semibold text-dark mb-1 small">Juara 1 OSN Matematika</h4>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Tingkat Provinsi 2025</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border-start border-4 border-primary" style="background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-medal text-white"></i>
                        </div>
                        <div>
                            <h4 class="fw-semibold text-dark mb-1 small">Juara 2 Debat Bahasa Inggris</h4>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Tingkat Nasional 2025</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border-start border-4 border-success" style="background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.1) 100%);">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-star text-white"></i>
                        </div>
                        <div>
                            <h4 class="fw-semibold text-dark mb-1 small">Sekolah Adiwiyata</h4>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Peduli Lingkungan 2024</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border-start border-4" style="border-color: #6d28d9 !important; background: linear-gradient(90deg, rgba(109, 40, 217, 0.05) 0%, rgba(109, 40, 217, 0.1) 100%);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #6d28d9;">
                            <i class="fas fa-certificate text-white"></i>
                        </div>
                        <div>
                            <h4 class="fw-semibold text-dark mb-1 small">Sekolah Referensi</h4>
                            <p class="text-muted mb-0" style="font-size: 0.875rem;">Kementerian Pendidikan 2025</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vision & Mission -->
    <div class="bg-white rounded-3 shadow p-4">
        <h3 class="fs-5 fw-bold text-dark mb-4">
            <i class="fas fa-bullseye me-2" style="color: #6d28d9;"></i>
            Visi & Misi Sekolah
        </h3>
        <div class="row g-3 g-lg-4">
            <!-- Vision -->
            <div class="col-lg-6">
                <div class="p-4 rounded-3 border-start border-4" style="border-color: #6d28d9 !important; background: linear-gradient(135deg, rgba(109, 40, 217, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);">
                    <h4 class="fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="fas fa-eye me-2" style="color: #6d28d9;"></i>
                        Visi
                    </h4>
                    <p class="text-dark mb-0" style="line-height: 1.7;">
                        Menjadi sekolah unggulan yang menghasilkan lulusan berkualitas, berakhlak mulia, 
                        berwawasan global, dan peduli lingkungan berdasarkan nilai-nilai Pancasila.
                    </p>
                </div>
            </div>

            <!-- Mission -->
            <div class="col-lg-6">
                <div class="p-4 rounded-3 border-start border-4 border-primary" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);">
                    <h4 class="fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="fas fa-rocket text-primary me-2"></i>
                        Misi
                    </h4>
                    <ul class="list-unstyled d-flex flex-column gap-2 text-dark mb-0">
                        <li class="d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Menyelenggarakan pembelajaran berkualitas dan inovatif</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Membentuk karakter siswa yang berakhlak mulia</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Mengembangkan potensi siswa secara optimal</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- School Program -->
    <div class="bg-white rounded-3 shadow p-4">
        <h3 class="fs-5 fw-bold text-dark mb-4">
            <i class="fas fa-calendar-check me-2" style="color: #6d28d9;"></i>
            Program Unggulan
        </h3>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="p-4 bg-primary bg-opacity-10 rounded-3 text-center">
                    <div class="mx-auto rounded-circle bg-primary d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fas fa-graduation-cap text-white fs-4"></i>
                    </div>
                    <h4 class="fw-semibold text-dark mb-1 small">Kelas Olimpiade</h4>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Persiapan OSN & KSN</p>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="p-4 bg-success bg-opacity-10 rounded-3 text-center">
                    <div class="mx-auto rounded-circle bg-success d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fas fa-globe text-white fs-4"></i>
                    </div>
                    <h4 class="fw-semibold text-dark mb-1 small">English Club</h4>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Bahasa Inggris Aktif</p>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="p-4 rounded-3 text-center" style="background-color: rgba(109, 40, 217, 0.1);">
                    <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background-color: #6d28d9;">
                        <i class="fas fa-laptop-code text-white fs-4"></i>
                    </div>
                    <h4 class="fw-semibold text-dark mb-1 small">Coding Class</h4>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Programming & Robotik</p>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="p-4 bg-warning bg-opacity-10 rounded-3 text-center">
                    <div class="mx-auto rounded-circle bg-warning d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                        <i class="fas fa-paint-brush text-white fs-4"></i>
                    </div>
                    <h4 class="fw-semibold text-dark mb-1 small">Seni & Budaya</h4>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Musik, Tari, Teater</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
