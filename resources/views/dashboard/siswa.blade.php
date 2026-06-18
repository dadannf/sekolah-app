@extends('layouts.app')

@section('title', 'Data Siswa - Dashboard Sekolah')
@section('page-title', 'Data Siswa')
@section('page-subtitle', 'Kelola dan lihat data siswa')

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

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Validasi gagal!</strong>
        <div class="mt-1" style="font-size: 0.9rem;">
            @foreach($errors->all() as $err)
                <div>- {{ $err }}</div>
            @endforeach
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Stats Summary -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-6 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4 transition-all" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(59, 130, 246, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(59, 130, 246, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-user-graduate text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TOTAL SISWA</p>
                        <h3 class="fs-4 fs-md-3 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $totalSiswa }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-user-graduate" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #198754 0%, #198754 100%); box-shadow: 0 8px 16px rgba(25, 135, 84, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(25, 135, 84, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(25, 135, 84, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-user-check text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">SISWA AKTIF</p>
                        <h3 class="fs-4 fs-md-3 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $totalAktif }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-user-check" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #696b6dff 0%, #696b6dff 100%); box-shadow: 0 8px 16px rgba(105, 107, 109, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(105, 107, 109, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(105, 107, 109, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-graduation-cap text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">LULUS</p>
                        <h3 class="fs-4 fs-md-3 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $totalLulus }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-graduation-cap" style="font-size: 6rem;"></i>
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
                        <i class="fas fa-users me-2" style="font-size: 1.3rem;"></i>
                        Daftar Siswa
                    </h2>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-sm-7 col-md-8">
                            <form method="GET" action="{{ route('dashboard.siswa') }}">
                                <div class="position-relative mb-2">
                                    <input type="text" id="searchStudentInput" name="search" value="{{ request('search') }}" placeholder="Cari NIS, Nama, Jurusan, atau Kelas..." class="form-control ps-5 border-0" style="background: rgba(255,255,255,0.95); font-size: 0.9rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 10px 16px 10px 45px;">
                                    <i class="fas fa-search position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); font-size: 0.95rem; color: #3b82f6;"></i>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <label for="studentSort" class="text-white fw-semibold small mb-0" style="letter-spacing: 0.3px;">Sortir:</label>
                                    <select name="sort" class="form-select form-select-sm border-0 fw-semibold" onchange="this.form.submit()" style="max-width: 180px; background: rgba(255,255,255,0.95); border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                        <option value="az" @selected(($sort ?? 'az') === 'az')>A - Z</option>
                                        <option value="za" @selected(($sort ?? 'az') === 'za')>Z - A</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm text-white fw-semibold ms-2" style="background: rgba(255,255,255,0.2); border-radius: 8px;">Cari</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-12 col-sm-5 col-md-4">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; transition: all 0.3s ease;" data-bs-toggle="modal" data-bs-target="#addStudentModal" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(251, 191, 36, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                                    <i class="fas fa-user-plus me-2"></i>
                                    <span class="d-none d-sm-inline">Tambah Siswa</span>
                                    <span class="d-inline d-sm-none">Tambah</span>
                                </button>

                                <form id="importExcelForm" class="d-grid gap-2" action="{{ route('dashboard.siswa.import-excel') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="excel" id="excelFileInput" class="d-none" accept=".xlsx,.xls" onchange="document.getElementById('importExcelForm').submit()">
                                    <a href="{{ route('dashboard.siswa.template-excel') }}" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(255,255,255,0.28) 0%, rgba(255,255,255,0.18) 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; backdrop-filter: blur(8px);" onclick="event.stopPropagation();">
                                        <i class="fas fa-download me-2"></i>
                                        Download Template
                                    </a>
                                    <button type="button" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #198754 0%, #198754 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; transition: all 0.3s ease;" onclick="document.getElementById('excelFileInput').click()" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(25, 135, 84, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                                        <i class="fas fa-file-excel me-2"></i>
                                        Import Excel
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="p-3 p-md-4" id="siswaTableAndPaginationWrapper">
            <form id="bulkDeleteForm" action="{{ route('dashboard.siswa.bulk-delete') }}" method="POST">
                @csrf
                <input type="hidden" id="selectAllStudentsFlag" name="select_all_students" value="false">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary fw-semibold" style="border-radius: 10px;">
                            <i class="fas fa-check-square me-1"></i>Pilih Semua
                        </button>
                        <button type="button" id="clearSelectionBtn" class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius: 10px;">
                            <i class="fas fa-square me-1"></i>Bersihkan
                        </button>
                    </div>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger fw-semibold" style="border-radius: 10px;" disabled onclick="submitBulkDelete()">
                        <i class="fas fa-trash me-1"></i>Hapus Terpilih
                    </button>
                </div>
            <!-- Desktop Table -->
            <div class="d-none d-lg-block table-responsive">
                <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0;">
                    <thead style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(29, 78, 216, 0.08) 100%); position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th class="py-3 fw-bold text-dark text-center" style="font-size: 0.85rem; width: 50px; letter-spacing: 0.3px; border: none;">
                                <input type="checkbox" id="selectAllDesktop" class="form-check-input" style="cursor: pointer;">
                            </th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; width: 50px; letter-spacing: 0.3px; border: none;">No</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; width: 80px; letter-spacing: 0.3px; border: none;">Foto</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">NISN</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Nama Lengkap</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Jurusan</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Kelas</th>
                            <th class="py-3 fw-bold text-dark" style="font-size: 0.85rem; letter-spacing: 0.3px; border: none;">Status</th>
                            <th class="py-3 fw-bold text-dark text-center" style="font-size: 0.85rem; width: 120px; letter-spacing: 0.3px; border: none;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswa as $index => $s)
                        <tr style="border-bottom: 1px solid rgba(59, 130, 246, 0.08); transition: all 0.2s ease;" onmouseover="this.style.background='linear-gradient(90deg, rgba(59, 130, 246, 0.03) 0%, rgba(29, 78, 216, 0.03) 100%)'" onmouseout="this.style.background='transparent'">
                            <td class="text-center">
                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="form-check-input student-checkbox desktop-student-checkbox" style="cursor: pointer;">
                            </td>
                            <td class="text-dark fw-medium" style="font-size: 0.9rem;">{{ $index + 1 }}</td>
                            <td>
                                <img src="{{ $s->photo_url }}" alt="{{ $s->name }}" class="rounded-circle shadow-sm" style="width: 48px; height: 48px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </td>
                            <td class="text-dark">
                                <code class="small px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%); color: #3b82f6; font-weight: 600; border: 1px solid rgba(59, 130, 246, 0.2);">{{ $s->nisn ?? '-' }}</code>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;">{{ $s->name }}</span>
                                    <small class="text-muted" style="font-size: 0.8rem;">NIS: <span class="fw-semibold">{{ $s->nis }}</span></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 0.95rem;">{{ $s->major ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($s->current_grade_level)
                                    <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);">
                                        <i class="fas fa-school me-1" style="font-size: 0.75rem;"></i>
                                        Kelas {{ $s->current_grade_level }}
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        Belum ada kelas
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($s->student_status === 'active')
                                    <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #198754 0%, #198754 100%); color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(25, 135, 84, 0.3);">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Aktif
                                    </span>
                                @elseif($s->student_status === 'graduated')
                                    <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #696b6dff 0%, #696b6dff 100%); color: white; font-weight: 600; box-shadow: 0 2px 6px rgba(91, 96, 102, 0.3);">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        Lulus
                                    </span>
                                @else
                                    <span class="badge px-3 py-2 rounded-pill" style="background-color: #dc3545 !important; color: rgb(253, 253, 253) !important; font-weight: 600;">
                                        <i class="fas fa-pause-circle me-1"></i>
                                        Tidak Aktif
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" title="Lihat Detail" 
                                        onclick='showStudentDetail(@json($s))'
                                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                        <i class="fas fa-eye text-white" style="font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" title="Edit" 
                                        onclick='showEditModal(@json($s))'
                                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(251, 191, 36, 0.4)'"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                        <i class="fas fa-edit text-white" style="font-size: 0.85rem;"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" title="Hapus" 
                                        onclick="confirmDelete({{ $s->id }}, '{{ $s->name }}')"
                                        style="width: 32px; height: 32px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); border: none; transition: all 0.2s ease;"
                                        onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(255, 107, 107, 0.4)'"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                                        <i class="fas fa-trash text-white" style="font-size: 0.85rem;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="py-4">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                                        <i class="fas fa-users" style="font-size: 2.5rem; color: #3b82f6;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-2">Belum Ada Data Siswa</h6>
                                    <p class="text-muted small mb-0">Klik tombol "Tambah Siswa" untuk menambahkan data siswa baru</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="d-lg-none">
                <div class="d-flex flex-column gap-3">
                    @forelse($siswa as $index => $s)
                    <div class="rounded-4 p-3 position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.95) 100%); border: 1px solid rgba(59, 130, 246, 0.15); box-shadow: 0 4px 12px rgba(0,0,0,0.08); backdrop-filter: blur(10px);">
                        <div class="position-absolute" style="top: -30px; right: -30px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                        <div class="d-flex align-items-start justify-content-between mb-3 gap-3">
                            <div class="d-flex align-items-center flex-grow-1">
                                <img src="{{ $s->photo_url }}" alt="{{ $s->name }}" class="rounded-circle me-3 flex-shrink-0 shadow-sm" style="width: 50px; height: 50px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="flex-grow-1 min-w-0">
                                    <h3 class="fw-bold text-dark mb-1" style="font-size: 1rem;">{{ $s->name }}</h3>
                                    <p class="text-muted mb-0" style="font-size: 0.8rem;">
                                        <span class="d-inline-block me-2">NIS: <code class="small px-2 py-1 rounded" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; font-weight: 600;">{{ $s->nis }}</code></span><br>
                                        <span class="d-inline-block">NISN: <code class="small px-2 py-1 rounded" style="background: rgba(29, 78, 216, 0.1); color: #1d4ed8; font-weight: 600;">{{ $s->nisn ?? '-' }}</code></span>
                                    </p>
                                    <p class="text-dark mb-0 mt-1 fw-semibold" style="font-size: 0.85rem;">
                                        <i class="fas fa-briefcase me-1" style="color: #3b82f6; font-size: 0.75rem;"></i>{{ $s->major ?? '-' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="form-check-input student-checkbox mobile-student-checkbox" style="cursor: pointer; width: 1.15rem; height: 1.15rem; margin-top: 0.25rem;">
                            </div>
                            @if($s->student_status === 'active')
                                <span class="badge px-3 py-2 rounded-pill flex-shrink-0 ms-2" style="background: linear-gradient(135deg, #198754 0%, #198754 100%); color: white; font-weight: 600; font-size: 0.7rem; box-shadow: 0 2px 6px rgba(25, 135, 84, 0.3);">Aktif</span>
                            @elseif($s->student_status === 'graduated')
                                <span class="badge px-3 py-2 rounded-pill flex-shrink-0 ms-2" style="background: linear-gradient(135deg, #696b6dff 0%, #696b6dff 100%); color: white; font-weight: 600; font-size: 0.7rem; box-shadow: 0 2px 6px rgba(105, 107, 109, 0.3);">Lulus</span>
                            @else
                                <span class="badge px-3 py-2 rounded-pill flex-shrink-0 ms-2" style="background-color: #dc3545 !important; color: rgb(253, 253, 253) !important; font-weight: 600; font-size: 0.7rem;">Tidak Aktif</span>
                            @endif
                        </div>
                        <div class="mb-3">
                            @if($s->current_grade_level)
                                <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600; font-size: 0.75rem; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);">
                                    <i class="fas fa-school me-1" style="font-size: 0.7rem;"></i>Kelas {{ $s->current_grade_level }}
                                </span>
                            @else
                                <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; font-weight: 600; font-size: 0.75rem; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);">
                                    <i class="fas fa-exclamation-circle me-1" style="font-size: 0.7rem;"></i>
                                    Belum ada kelas
                                </span>
                            @endif
                        </div>
                        <div class="d-flex gap-2 pt-3 border-top" style="border-color: rgba(59, 130, 246, 0.1) !important;">
                            <button type="button" class="btn btn-sm flex-fill text-white border-0 shadow-sm fw-semibold" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); font-size: 0.85rem; border-radius: 10px; padding: 8px 12px; transition: all 0.2s ease;"
                                onclick='showStudentDetail(@json($s))'
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                                <i class="fas fa-eye" style="font-size: 0.8rem;"></i>
                                <span class="d-none d-sm-inline ms-1">Detail</span>
                            </button>
                            <button type="button" class="btn btn-sm flex-fill text-white border-0 shadow-sm fw-semibold" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.85rem; border-radius: 10px; padding: 8px 12px; transition: all 0.2s ease;" 
                                onclick='showEditModal(@json($s))'
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(251, 191, 36, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                                <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                                <span class="d-none d-sm-inline ms-1">Edit</span>
                            </button>
                            <button type="button" class="btn btn-sm text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); font-size: 0.85rem; border-radius: 10px; padding: 8px 16px; transition: all 0.2s ease;" 
                                onclick="confirmDelete({{ $s->id }}, '{{ $s->name }}')"
                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(255, 107, 107, 0.4)'"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                                <i class="fas fa-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                            <i class="fas fa-users" style="font-size: 2.5rem; color: #3b82f6;"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Belum Ada Data Siswa</h6>
                        <p class="text-muted small mb-0">Klik tombol "Tambah Siswa" untuk menambahkan data siswa baru</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-3 mt-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-3 mb-3">
                    <p class="small text-secondary mb-0">
                        Menampilkan <strong>{{ $siswa->count() }}</strong> dari <strong>{{ $siswa->total() }}</strong> siswa
                        @if($siswa->total() > 0)
                            (Halaman <strong>{{ $siswa->currentPage() }}</strong> dari <strong>{{ $siswa->lastPage() }}</strong>)
                        @endif
                    </p>
                </div>

                @if($siswa->hasPages())
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    {{-- Tombol Previous --}}
                    @if($siswa->onFirstPage())
                    <button class="btn btn-sm rounded-3 px-3 py-2" disabled style="background-color: #e9ecef; color: #adb5bd; cursor: not-allowed; border: none;">
                        <i class="fas fa-chevron-left me-2" style="font-size: 0.85rem;"></i>
                        <span class="d-none d-sm-inline">Sebelumnya</span>
                    </button>
                    @else
                    <a href="{{ $siswa->previousPageUrl() }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                        <i class="fas fa-chevron-left me-2" style="font-size: 0.85rem;"></i>
                        <span class="d-none d-sm-inline">Sebelumnya</span>
                    </a>
                    @endif

                    {{-- Nomor Halaman --}}
                    <div class="d-flex gap-1 flex-wrap justify-content-center">
                        @foreach($siswa->getUrlRange(1, $siswa->lastPage()) as $page => $url)
                            @if($page >= $siswa->currentPage() - 2 && $page <= $siswa->currentPage() + 2)
                                @if($page == $siswa->currentPage())
                                <button class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); min-width: 40px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
                                    {{ $page }}
                                </button>
                                @else
                                <a href="{{ $url }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold border-0 shadow-sm" style="background: #f3f4f6; color: #374151; transition: all 0.2s ease; min-width: 40px;" onmouseover="this.style.background='#e5e7eb'; this.style.color='#1f2937'" onmouseout="this.style.background='#f3f4f6'; this.style.color='#374151'">
                                    {{ $page }}
                                </a>
                                @endif
                            @endif
                        @endforeach
                    </div>

                    {{-- Tombol Next --}}
                    @if($siswa->hasMorePages())
                    <a href="{{ $siswa->nextPageUrl() }}" class="btn btn-sm rounded-3 px-3 py-2 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
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

    <!-- Distribusi Kelas per Jurusan -->
    <div class="row g-3 g-md-4 mb-4">
        @php
        $majorColors = [
            'Pemasaran' => ['from' => '#ec4899', 'to' => '#be185d'],
            'Teknik Komputer dan Jaringan' => ['from' => '#8b5cf6', 'to' => '#6d28d9'],
            'Teknik Bisnis Sepeda Motor' => ['from' => '#f59e0b', 'to' => '#d97706'],
        ];
        @endphp

        @foreach($gradesByMajor as $major => $grades)
        <div class="col-12 col-md-6 col-lg-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4 h-100" style="background: linear-gradient(135deg, {{ $majorColors[$major]['from'] }} 0%, {{ $majorColors[$major]['to'] }} 100%); box-shadow: 0 8px 16px rgba(0,0,0,0.15); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.25)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)'">
                <!-- Header -->
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-white border-opacity-25">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 48px; height: 48px; backdrop-filter: blur(10px);">
                        <i class="fas fa-layer-group text-white" style="font-size: 1.25rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-0 fw-bold" style="font-size: 0.95rem; letter-spacing: 0.3px;">{{ $major }}</p>
                        <p class="text-white mb-0 fw-medium" style="font-size: 0.75rem; opacity: 0.8;">Distribusi per Kelas</p>
                    </div>
                </div>

                <!-- Grade Distribution -->
                <div class="row g-2">
                    @foreach(['10', '11', '12'] as $grade)
                    <div class="col-4">
                        <div class="bg-white bg-opacity-10 rounded-3 p-2 text-center" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                            <p class="text-white mb-1 fw-semibold" style="font-size: 0.7rem; opacity: 0.9;">KELAS {{ $grade }}</p>
                            <h4 class="fs-5 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);">{{ $grades[$grade] }}</h4>
                            <p class="text-white mb-0" style="font-size: 0.65rem; opacity: 0.7;">siswa</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="mt-3 pt-3 border-top border-white border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-white fw-semibold" style="font-size: 0.85rem; opacity: 0.9;">Total Siswa:</span>
                        <span class="text-white fw-bold" style="font-size: 1.1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">{{ array_sum($grades) }} siswa</span>
                    </div>
                </div>

                <!-- Background Decoration -->
                <div class="position-absolute" style="right: -30px; bottom: -30px; opacity: 0.08;">
                    <i class="fas fa-layer-group" style="font-size: 8rem;"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Tambah Siswa -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);">
                <h5 class="modal-title text-white fw-bold fs-6 fs-md-5" id="addStudentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Tambah Siswa Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form action="{{ route('dashboard.siswa.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2 g-md-3">
                        <!-- Data Identitas -->
                        <div class="col-12">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #8b5cf6; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-id-card me-2" style="color: #8b5cf6; font-size: 0.9rem;"></i>Data Identitas
                            </h6>
                        </div>

                        <!-- Alert for duplicate NIS/NISN -->
                        <div class="col-12" id="duplicate-alert" style="display: none;">
                            <div class="alert alert-danger mb-2 d-flex align-items-center" role="alert" style="font-size: 0.85rem; border-left: 4px solid #dc3545; animation: shake 0.5s;">
                                <i class="fas fa-exclamation-circle me-2" style="font-size: 1.2rem;"></i>
                                <div>
                                    <strong>Data Duplikat!</strong><br>
                                    <span id="duplicate-message"></span>
                                </div>
                            </div>
                        </div>
                        
                        <style>
                            @keyframes shake {
                                0%, 100% { transform: translateX(0); }
                                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                                20%, 40%, 60%, 80% { transform: translateX(5px); }
                            }
                        </style>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">NIS <span class="text-danger">*</span></label>
                            <input type="text" id="nis" name="nis" class="form-control form-control-sm" required placeholder="Nomor Induk Siswa" style="font-size: 0.85rem;">
                            <div class="invalid-feedback d-block" id="nis-feedback" style="font-size: 0.75rem;"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">NISN <span class="text-danger">*</span></label>
                            <input type="text" id="nisn" name="nisn" class="form-control form-control-sm" required placeholder="Nomor Induk Siswa Nasional" style="font-size: 0.85rem;">
                            <div class="invalid-feedback d-block" id="nisn-feedback" style="font-size: 0.75rem;"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">NIK</label>
                            <input type="text" name="national_id_number" class="form-control form-control-sm" placeholder="Nomor Induk Kependudukan" maxlength="16" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" required placeholder="Nama lengkap siswa" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tempat Lahir</label>
                            <input type="text" name="place_of_birth" class="form-control form-control-sm" placeholder="Kota/Kabupaten kelahiran" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tanggal Lahir</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-sm" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Jenis Kelamin</label>
                            <select name="gender" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="">-- Pilih --</option>
                                <option value="M">Laki-laki</option>
                                <option value="F">Perempuan</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nomor Telepon</label>
                            <input type="text" name="phone_number" class="form-control form-control-sm" placeholder="08xx-xxxx-xxxx" style="font-size: 0.85rem;">
                        </div>

                        <!-- Data Akademik -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #3b82f6; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-graduation-cap me-2" style="color: #3b82f6; font-size: 0.9rem;"></i>Data Akademik
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Jurusan</label>
                            <select name="major" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="">-- Pilih Jurusan --</option>
                                <option value="Pemasaran">Pemasaran</option>
                                <option value="Teknik Komputer dan Jaringan">Teknik Komputer dan Jaringan</option>
                                <option value="Teknik Bisnis Sepeda Motor">Teknik Bisnis Sepeda Motor</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Status</label>
                            <select name="student_status" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="active" selected>Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                                <option value="graduated">Lulus</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select name="current_grade_level" class="form-select form-select-sm" required style="font-size: 0.85rem;">
                                <option value="">-- Pilih Tingkat Kelas --</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>
                        </div>

                        <!-- Data Keluarga -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #10b981; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-users me-2" style="color: #10b981; font-size: 0.9rem;"></i>Data Keluarga
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Ayah</label>
                            <input type="text" name="father_name" class="form-control form-control-sm" placeholder="Nama lengkap ayah" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Ibu</label>
                            <input type="text" name="mother_name" class="form-control form-control-sm" placeholder="Nama lengkap ibu" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kontak -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #8b5cf6; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-address-book me-2" style="color: #8b5cf6; font-size: 0.9rem;"></i>Kontak
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Email Siswa</label>
                            <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="email@siswa.com" value="{{ old('email') }}" style="font-size: 0.85rem;">
                            @error('email')
                                <div class="invalid-feedback" style="font-size: 0.75rem;">
                                    {{ $message }}
                                </div>
                            @enderror
                            <small class="text-muted" style="font-size: 0.75rem;">Email pribadi siswa (opsional)</small>
                        </div>

                        <!-- Alamat -->
                        <div class="col-12 mt-2 mt-md-3">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Alamat Lengkap</label>
                            <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap tempat tinggal" style="font-size: 0.85rem;"></textarea>
                        </div>

                        <!-- Foto -->
                        <div class="col-12 mt-2 mt-md-3">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Foto Siswa</label>
                            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" style="font-size: 0.85rem;">
                            <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, PNG, maksimal 2MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="font-size: 0.85rem;">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white btn-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%); font-size: 0.85rem;">
                        <i class="fas fa-save me-1"></i>Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Siswa -->
<div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                <h5 class="modal-title text-white fw-bold fs-6 fs-md-5" id="studentDetailModalLabel">
                    <i class="fas fa-user-circle me-2"></i>Detail Siswa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="max-height: 75vh; overflow-y: auto;">
                <!-- Photo & Basic Info Section -->
                <div class="text-center py-4" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(30, 64, 175, 0.05) 100%);">
                    <img id="detail-photo" src="" alt="Foto Siswa" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <h4 class="fw-bold text-dark mb-1" id="detail-name"></h4>
                    <p class="text-muted mb-2" id="detail-nis"></p>
                    <span class="badge px-3 py-2" id="detail-status-badge"></span>
                </div>

                <!-- Detailed Information Sections -->
                <div class="p-3 p-md-4">
                    <!-- Data Identitas -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="color: #3b82f6;">
                            <i class="fas fa-id-card me-2" style="color: #3b82f6;"></i>Data Identitas
                        </h6>
                        <div class="row g-2 g-md-3">
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-hashtag"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">NIS</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-nis-value"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-id-badge"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">NISN</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-nisn"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-fingerprint"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">NIK</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-nik"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-venus-mars"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Jenis Kelamin</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-gender"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Tempat Lahir</p>
                                            <p class="fw-semibold text-dark mb-0 text-truncate" id="detail-birth-place"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-calendar-alt"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Tanggal Lahir</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-birth-date"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Nomor Telepon</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-phone"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Email</p>
                                            <p class="fw-semibold text-dark mb-0 text-truncate" id="detail-email"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Akademik -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="color: #3b82f6;">
                            <i class="fas fa-graduation-cap me-2" style="color: #3b82f6;"></i>Data Akademik
                        </h6>
                        <div class="row g-2 g-md-3">
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-chalkboard"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Kelas</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-class"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Tahun Ajaran</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-academic-year"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-book"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Jurusan</p>
                                            <p class="fw-semibold text-dark mb-0 text-truncate" id="detail-major"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(59,130,246,0.12); color: #3b82f6;">
                                            <i class="fas fa-user-check"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Status</p>
                                            <p class="fw-semibold text-dark mb-0" id="detail-status"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Keluarga -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="color: #10b981;">
                            <i class="fas fa-users me-2" style="color: #10b981;"></i>Data Keluarga
                        </h6>
                        <div class="row g-2 g-md-3">
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(16,185,129,0.12); color: #10b981;">
                                            <i class="fas fa-user-tie"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Nama Ayah</p>
                                            <p class="fw-semibold text-dark mb-0 text-truncate" id="detail-father"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(16,185,129,0.12); color: #10b981;">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-muted small fw-semibold mb-1">Nama Ibu</p>
                                            <p class="fw-semibold text-dark mb-0 text-truncate" id="detail-mother"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="color: #8b5cf6;">
                            <i class="fas fa-map-marker-alt me-2" style="color: #8b5cf6;"></i>Alamat
                        </h6>
                        <div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3">
                            <div class="d-flex align-items-start gap-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: rgba(139,92,246,0.12); color: #8b5cf6;">
                                    <i class="fas fa-location-arrow"></i>
                                </span>
                                <p class="text-dark mb-0" id="detail-address" style="white-space: pre-wrap;"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Semua Field (Tabel students) -->
                    <div class="mb-2" id="detail-all-fields-section">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom" style="color: #0ea5e9;">
                            <i class="fas fa-table me-2" style="color: #0ea5e9;"></i>Detil Data Siswa
                        </h6>
                        <div class="row g-2 g-md-3" id="detail-all-fields"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-user-times text-danger" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
                <h6 class="fw-bold text-dark mb-3">Apakah Anda yakin ingin menghapus data siswa ini?</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <p class="fw-bold text-dark mb-1" id="delete-student-name"></p>
                    <p class="text-muted small mb-0">NIS: <span id="delete-student-nis"></span></p>
                </div>
                <div class="alert alert-warning mb-0" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Perhatian!</strong> Tindakan ini akan menghapus:
                    <ul class="text-start mb-0 mt-2">
                        <li>Data siswa</li>
                        <li>Data enrollment/pendaftaran kelas</li>
                        <li>Data absensi</li>
                        <li>Data nilai</li>
                        <li>Akun user terkait</li>
                    </ul>
                    <p class="mb-0 mt-2"><strong>Data yang terhapus tidak dapat dikembalikan!</strong></p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <form id="deleteStudentForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Ya, Hapus Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Siswa -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h5 class="modal-title text-white fw-bold fs-6 fs-md-5" id="editStudentModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Edit Data Siswa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStudentForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2 g-md-3">
                        <!-- Data Identitas -->
                        <div class="col-12">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #f59e0b; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-id-card me-2" style="color: #f59e0b; font-size: 0.9rem;"></i>Data Identitas
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">NIS <span class="text-danger">*</span></label>
                            <input type="text" id="edit-nis" name="nis" class="form-control form-control-sm" required placeholder="Nomor Induk Siswa" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">NISN <span class="text-danger">*</span></label>
                            <input type="text" id="edit-nisn" name="nisn" class="form-control form-control-sm" required placeholder="Nomor Induk Siswa Nasional" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" id="edit-name" name="name" class="form-control form-control-sm" required placeholder="Nama lengkap siswa" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tempat Lahir</label>
                            <input type="text" id="edit-place_of_birth" name="place_of_birth" class="form-control form-control-sm" placeholder="Kota/Kabupaten kelahiran" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tanggal Lahir</label>
                            <input type="date" id="edit-date_of_birth" name="date_of_birth" class="form-control form-control-sm" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Jenis Kelamin</label>
                            <select id="edit-gender" name="gender" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="">-- Pilih --</option>
                                <option value="M">Laki-laki</option>
                                <option value="F">Perempuan</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nomor Telepon</label>
                            <input type="text" id="edit-phone_number" name="phone_number" class="form-control form-control-sm" placeholder="08xx-xxxx-xxxx" style="font-size: 0.85rem;">
                        </div>

                        <!-- Data Akademik -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #3b82f6; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-graduation-cap me-2" style="color: #3b82f6; font-size: 0.9rem;"></i>Data Akademik
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Jurusan</label>
                            <select id="edit-major" name="major" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="">-- Pilih Jurusan --</option>
                                <option value="Pemasaran">Pemasaran</option>
                                <option value="Teknik Komputer dan Jaringan">Teknik Komputer dan Jaringan</option>
                                <option value="Teknik Bisnis Sepeda Motor">Teknik Bisnis Sepeda Motor</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Status</label>
                            <select id="edit-student_status" name="student_status" class="form-select form-select-sm" style="font-size: 0.85rem;">
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                                <option value="graduated">Lulus</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Tingkat Kelas <span class="text-danger">*</span></label>
                            <select id="edit-current_grade_level" name="current_grade_level" class="form-select form-select-sm" required style="font-size: 0.85rem;">
                                <option value="">-- Pilih Tingkat Kelas --</option>
                                <option value="10">Kelas 10</option>
                                <option value="11">Kelas 11</option>
                                <option value="12">Kelas 12</option>
                            </select>

                        <!-- Data Keluarga -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #10b981; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-users me-2" style="color: #10b981; font-size: 0.9rem;"></i>Data Keluarga
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Ayah</label>
                            <input type="text" id="edit-father_name" name="father_name" class="form-control form-control-sm" placeholder="Nama lengkap ayah" style="font-size: 0.85rem;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Nama Ibu</label>
                            <input type="text" id="edit-mother_name" name="mother_name" class="form-control form-control-sm" placeholder="Nama lengkap ibu" style="font-size: 0.85rem;">
                        </div>

                        <!-- Kontak -->
                        <div class="col-12 mt-3 mt-md-4">
                            <h6 class="fw-bold text-dark mb-2 mb-md-3" style="border-left: 4px solid #8b5cf6; padding-left: 10px; font-size: 0.95rem;">
                                <i class="fas fa-address-book me-2" style="color: #8b5cf6; font-size: 0.9rem;"></i>Kontak
                            </h6>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Email Siswa</label>
                            <input type="email" id="edit-email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="email@siswa.com" style="font-size: 0.85rem;">
                            @error('email')
                                <div class="invalid-feedback" style="font-size: 0.75rem;">
                                    {{ $message }}
                                </div>
                            @enderror
                            <small class="text-muted" style="font-size: 0.75rem;">Email pribadi siswa (opsional)</small>
                        </div>

                        <!-- Alamat -->
                        <div class="col-12 mt-2 mt-md-3">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Alamat Lengkap</label>
                            <textarea id="edit-address" name="address" class="form-control form-control-sm" rows="2" placeholder="Alamat lengkap tempat tinggal" style="font-size: 0.85rem;"></textarea>
                        </div>

                        <!-- Foto -->
                        <div class="col-12 mt-2 mt-md-3">
                            <label class="form-label fw-semibold mb-1" style="font-size: 0.85rem;">Foto Siswa</label>
                            <div class="mb-2">
                                <img id="edit-current-photo" src="" alt="Current Photo" class="rounded" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #e5e7eb;">
                            </div>
                            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" style="font-size: 0.85rem;">
                            <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, PNG, maksimal 2MB. Kosongkan jika tidak ingin mengubah foto.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="font-size: 0.85rem;">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white btn-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.85rem;">
                        <i class="fas fa-save me-1"></i>Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
console.log('Validation script loaded!'); // Debug

// Show edit modal and populate with student data
function showEditModal(student) {
    console.log('Opening edit modal for:', student); // Debug
    
    // Set form action
    const form = document.getElementById('editStudentForm');
    form.action = `/dashboard/siswa/${student.id}`;
    
    // Populate form fields
    document.getElementById('edit-nis').value = student.nis || '';
    document.getElementById('edit-nisn').value = student.nisn || '';
    document.getElementById('edit-name').value = student.name || '';
    document.getElementById('edit-place_of_birth').value = student.place_of_birth || '';
    document.getElementById('edit-date_of_birth').value = student.date_of_birth || '';
    document.getElementById('edit-gender').value = student.gender || '';
    document.getElementById('edit-phone_number').value = student.phone_number || '';
    document.getElementById('edit-major').value = student.major || '';
    document.getElementById('edit-student_status').value = student.student_status || 'active';
    document.getElementById('edit-father_name').value = student.father_name || '';
    document.getElementById('edit-mother_name').value = student.mother_name || '';
    document.getElementById('edit-email').value = student.email || '';
    document.getElementById('edit-address').value = student.address || '';
    
    // Set current photo
    const photoUrl = student.photo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.name) + '&background=6b7280&color=fff&size=200';
    document.getElementById('edit-current-photo').src = photoUrl;
    
    // Set current grade level
    if (student.current_grade_level) {
        document.getElementById('edit-current_grade_level').value = student.current_grade_level;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    modal.show();
}

// Confirm delete student
function confirmDelete(studentId, studentName) {
    // Set student info in modal
    document.getElementById('delete-student-name').textContent = studentName;
    document.getElementById('delete-student-nis').textContent = studentId;
    
    // Set form action
    const form = document.getElementById('deleteStudentForm');
    form.action = `/dashboard/siswa/${studentId}`;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

function updateBulkDeleteButton() {
    const selectAllFlag = document.getElementById('selectAllStudentsFlag');
    const isFlagSet = selectAllFlag && selectAllFlag.value === 'true';
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;

    if (bulkDeleteBtn) {
        if (isFlagSet) {
            // Show total when select all is active
            const totalCount = {{ $totalSiswa }};
            bulkDeleteBtn.disabled = false;
            bulkDeleteBtn.innerHTML = `<i class="fas fa-trash me-1"></i>Hapus Semua (${totalCount})`;
        } else {
            bulkDeleteBtn.disabled = checkedCount === 0;
            bulkDeleteBtn.innerHTML = checkedCount > 0
                ? `<i class="fas fa-trash me-1"></i>Hapus Terpilih (${checkedCount})`
                : '<i class="fas fa-trash me-1"></i>Hapus Terpilih';
        }
    }
}

function submitBulkDelete() {
    const selectAllFlag = document.getElementById('selectAllStudentsFlag');
    const isFlagSet = selectAllFlag && selectAllFlag.value === 'true';
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;

    if (!isFlagSet && checkedCount === 0) {
        alert('Pilih minimal satu siswa untuk dihapus.');
        return;
    }

    let confirmMessage;
    if (isFlagSet) {
        const totalCount = {{ $totalSiswa }};
        confirmMessage = `Apakah Anda yakin ingin menghapus SEMUA ${totalCount} siswa beserta data keuangannya? Tindakan ini tidak dapat dibatalkan!`;
    } else {
        confirmMessage = checkedCount === 1
            ? 'Apakah Anda yakin ingin menghapus 1 siswa terpilih beserta data keuangannya?'
            : `Apakah Anda yakin ingin menghapus ${checkedCount} siswa terpilih beserta data keuangannya?`;
    }

    if (confirm(confirmMessage)) {
        document.getElementById('bulkDeleteForm')?.submit();
    }
}

function syncSelectAllState() {
    const checkboxes = Array.from(document.querySelectorAll('.student-checkbox'));
    const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
    const selectAllDesktop = document.getElementById('selectAllDesktop');

    if (selectAllDesktop && checkboxes.length > 0) {
        selectAllDesktop.checked = checkedCount === checkboxes.length;
        selectAllDesktop.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }

    updateBulkDeleteButton();
}

function toggleAllStudents(checked) {
    const selectAllFlag = document.getElementById('selectAllStudentsFlag');
    
    if (checked) {
        // When selecting all, set flag to true and check visible checkboxes
        if (selectAllFlag) {
            selectAllFlag.value = 'true';
        }
        document.querySelectorAll('.student-checkbox').forEach((checkbox) => {
            checkbox.checked = true;
        });
    } else {
        // When clearing, set flag to false and uncheck all
        if (selectAllFlag) {
            selectAllFlag.value = 'false';
        }
        document.querySelectorAll('.student-checkbox').forEach((checkbox) => {
            checkbox.checked = false;
        });
    }
    syncSelectAllState();
}

document.addEventListener('DOMContentLoaded', function () {
    const selectAllDesktop = document.getElementById('selectAllDesktop');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');

    document.querySelectorAll('.student-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectAllState);
    });

    if (selectAllDesktop) {
        selectAllDesktop.addEventListener('change', function () {
            toggleAllStudents(this.checked);
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            toggleAllStudents(true);
        });
    }

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            toggleAllStudents(false);
        });
    }

    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function (event) {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            if (checkedCount === 0) {
                event.preventDefault();
                alert('Pilih minimal satu siswa untuk dihapus.');
            }
        });
    }

    syncSelectAllState();
});

// Show student detail modal
function showStudentDetail(student) {
    console.log('Showing detail for:', student); // Debug

    const EMPTY_VALUE = 'Belum diisi';

    const STUDENT_TABLE_COLUMNS = Array.isArray(window.STUDENT_TABLE_COLUMNS)
        ? window.STUDENT_TABLE_COLUMNS
        : @json($studentColumns ?? []);

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function humanizeColumn(column) {
        const map = {
            'id': 'ID',
            'user_id': 'ID Pengguna',
            'nis': 'NIS',
            'nisn': 'NISN',
            'national_id_number': 'NIK',
            'place_of_birth': 'Tempat Lahir',
            'date_of_birth': 'Tanggal Lahir',
            'current_grade_level': 'Kelas',
            'phone_number': 'Nomor Telepon',
            'student_status': 'Status',
            'email': 'Email',
            'major': 'Jurusan',
            'father_name': 'Nama Ayah',
            'mother_name': 'Nama Ibu',
            'address': 'Alamat',
            'family_card_number': 'Nomor Kartu Keluarga',
            'child_order': 'Anak Ke',
            'religion': 'Agama',
            'address_rt': 'RT',
            'address_rw': 'RW',
            'address_dusun': 'Dusun',
            'address_kelurahan': 'Kelurahan/Desa',
            'address_kecamatan': 'Kecamatan',
            'address_postal_code': 'Kode Pos',
            'residence_type': 'Jenis Tempat Tinggal',
            'transportation': 'Transportasi',
            'telephone': 'Telepon',
            'mobile_phone': 'Nomor HP',
            'photo_path': 'Lokasi Foto',
            'created_at': 'Dibuat',
            'updated_at': 'Diperbarui',

            // Field tambahan (umumnya masih Inggris)
            'siblings_count': 'Jumlah Saudara',
            'previous_school': 'Asal Sekolah',
            'previous_school_npsn': 'NPSN Asal Sekolah',
            'hobby': 'Hobi',
            'aspiration': 'Cita-cita',
            'birth_certificate_registration_no': 'No Registrasi Akta Kelahiran',
            'shirt_size': 'Ukuran Baju',
            'diploma_serial_no': 'No Seri Ijazah',
            'notes': 'Catatan',
            'mother_income': 'Penghasilan Ibu',
            'father_income': 'Penghasilan Ayah',
            'father_birth_year': 'Tahun Lahir Ayah',
            'father_nik': 'NIK Ayah',
            'father_education': 'Pendidikan Ayah',
            'father_job': 'Pekerjaan Ayah',
            'mother_birth_year': 'Tahun Lahir Ibu',
            'mother_nik': 'NIK Ibu',
            'mother_education': 'Pendidikan Ibu',
            'mother_job': 'Pekerjaan Ibu',
            'weight_kg': 'Berat (Kg)',
            'height_cm': 'Tinggi (Cm)',
            'waist_cm': 'Lingkar Pinggang (Cm)',
            'distance_to_school': 'Jarak ke Sekolah',
            'travel_time': 'Waktu Tempuh',
        };
        if (map[column]) return map[column];

        const base = String(column)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (c) => c.toUpperCase());

        // Fallback: terjemahkan token Inggris umum ke Indonesia
        const tokenMap = {
            id: 'ID',
            user: 'Pengguna',
            family: 'Keluarga',
            card: 'Kartu',
            number: 'Nomor',
            siblings: 'Saudara',
            count: 'Jumlah',
            previous: 'Sebelumnya',
            school: 'Sekolah',
            hobby: 'Hobi',
            aspiration: 'Cita-cita',
            birth: 'Kelahiran',
            certificate: 'Akta',
            registration: 'Registrasi',
            no: 'No',
            shirt: 'Baju',
            size: 'Ukuran',
            diploma: 'Ijazah',
            serial: 'Seri',
            notes: 'Catatan',
            mother: 'Ibu',
            father: 'Ayah',
            income: 'Penghasilan',
            weight: 'Berat',
            height: 'Tinggi',
            waist: 'Pinggang',
            distance: 'Jarak',
            to: 'ke',
            travel: 'Tempuh',
            time: 'Waktu',
            npsn: 'NPSN',
            kg: 'Kg',
            cm: 'Cm',
        };

        return base
            .split(' ')
            .map((w) => {
                const lower = String(w).toLowerCase();
                return tokenMap[lower] || w;
            })
            .join(' ')
            .replace(/\bKe\b/g, 'ke');
    }

    function formatDate(value) {
        if (!value) return null;
        const d = new Date(value);
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function formatValue(column, value) {
        if (value === null || value === undefined) return EMPTY_VALUE;
        if (typeof value === 'string' && value.trim() === '') return EMPTY_VALUE;
        if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';

        // Date-ish columns
        if (String(column).endsWith('_at') || String(column).includes('date')) {
            const formatted = formatDate(value);
            if (formatted) return formatted;
        }

        // Objects/arrays should be stringified safely
        if (typeof value === 'object') {
            try {
                return JSON.stringify(value);
            } catch (e) {
                return String(value);
            }
        }

        return String(value);
    }

    function iconForColumn(column) {
        const c = String(column || '');
        const iconMap = {
            'family_card_number': 'fa-id-card',
            'child_order': 'fa-sort-numeric-up',
            'religion': 'fa-praying-hands',
            'address_rt': 'fa-map-signs',
            'address_rw': 'fa-map-signs',
            'address_dusun': 'fa-home',
            'address_kelurahan': 'fa-map-marker-alt',
            'address_kecamatan': 'fa-map',
            'address_postal_code': 'fa-mail-bulk',
            'residence_type': 'fa-building',
            'transportation': 'fa-bus',
            'telephone': 'fa-phone',
            'mobile_phone': 'fa-mobile-alt',
            'father_birth_year': 'fa-calendar',
            'father_nik': 'fa-fingerprint',
            'father_education': 'fa-graduation-cap',
            'father_job': 'fa-briefcase',
            'father_income': 'fa-money-bill-wave',
            'mother_birth_year': 'fa-calendar',
            'mother_nik': 'fa-fingerprint',
            'mother_education': 'fa-graduation-cap',
            'mother_job': 'fa-briefcase',
            'mother_income': 'fa-money-bill-wave',
            'weight_kg': 'fa-weight',
            'height_cm': 'fa-ruler-vertical',
            'waist_cm': 'fa-ruler',
            'shirt_size': 'fa-tshirt',
            'created_at': 'fa-calendar-plus',
            'updated_at': 'fa-calendar-check',
        };
        return iconMap[c] || 'fa-info-circle';
    }

    function renderAllStudentFields(studentObj) {
        const container = document.getElementById('detail-all-fields');
        const section = document.getElementById('detail-all-fields-section');
        if (!container) return;
        container.innerHTML = '';

        let metaCreatedAt = null;
        let metaUpdatedAt = null;

        const addressHomeCols = new Set([
            'address_rt',
            'address_rw',
            'address_dusun',
            'address_kelurahan',
            'address_kecamatan',
            'address_postal_code',
        ]);

        const familyCols = new Set([
            'father_birth_year',
            'father_nik',
            'father_education',
            'father_job',
            'father_income',
            'mother_birth_year',
            'mother_nik',
            'mother_education',
            'mother_job',
            'mother_income',
        ]);

        const completeCols = new Set([
            'weight_kg',
            'height_cm',
            'waist_cm',
            'shirt_size',
        ]);

        const groups = {
            address: [],
            family: [],
            complete: [],
            others: [],
        };

        function buildCard(col, value, accent, accentBg) {
            return (
                '<div class="border rounded-3 bg-light bg-opacity-50 p-2 p-md-3 h-100">' +
                    '<div class="d-flex align-items-start gap-2">' +
                        '<span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 30px; height: 30px; background: ' + accentBg + '; color: ' + accent + ';">' +
                            '<i class="fas ' + escapeHtml(iconForColumn(col)) + '"></i>' +
                        '</span>' +
                        '<div class="min-w-0">' +
                            '<p class="text-muted small fw-semibold mb-1">' + escapeHtml(humanizeColumn(col)) + '</p>' +
                            '<p class="fw-semibold text-dark mb-0 text-truncate">' + escapeHtml(formatValue(col, value)) + '</p>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        }

        function appendGroup(title, iconClass, accent, accentBg, items) {
            if (!items || !items.length) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'col-12';

            wrapper.innerHTML =
                '<div class="d-flex align-items-center gap-2 mt-2 mb-2">' +
                    '<span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 28px; height: 28px; background: ' + accentBg + '; color: ' + accent + ';">' +
                        '<i class="fas ' + escapeHtml(iconClass) + '"></i>' +
                    '</span>' +
                    '<div class="fw-bold" style="color: ' + accent + ';">' + escapeHtml(title) + '</div>' +
                '</div>';

            const row = document.createElement('div');
            row.className = 'row g-2 g-md-3';

            items.forEach(({ col, value }) => {
                const colEl = document.createElement('div');
                colEl.className = 'col-12 col-sm-6 col-md-4';
                colEl.innerHTML = buildCard(col, value, accent, accentBg);
                row.appendChild(colEl);
            });

            wrapper.appendChild(row);
            container.appendChild(wrapper);
        }

        // Jangan tampilkan ulang field yang sudah ditampilkan di section atas.
        const excluded = new Set([
            // Header/basic
            'name', 'nis',
            // Identitas
            'nisn', 'national_id_number', 'gender', 'place_of_birth', 'date_of_birth', 'phone_number', 'email',
            // Akademik
            'current_grade_level', 'major', 'student_status',
            // Keluarga
            'father_name', 'mother_name',
            // Alamat
            'address',
            // Foto (di header)
            'photo_path'
        ]);

        (STUDENT_TABLE_COLUMNS || []).forEach((col) => {
            const key = String(col);
            if (excluded.has(key)) return;

            if (key === 'created_at') {
                metaCreatedAt = studentObj ? studentObj[col] : null;
                return;
            }
            if (key === 'updated_at') {
                metaUpdatedAt = studentObj ? studentObj[col] : null;
                return;
            }

            const value = studentObj ? studentObj[col] : null;

            if (addressHomeCols.has(key)) {
                groups.address.push({ col: key, value });
                return;
            }

            if (familyCols.has(key)) {
                groups.family.push({ col: key, value });
                return;
            }

            if (completeCols.has(key)) {
                groups.complete.push({ col: key, value });
                return;
            }

            groups.others.push({ col: key, value });
        });

        // Render kategori sesuai urutan permintaan
        appendGroup('Alamat Rumah', 'fa-home', '#8b5cf6', 'rgba(139,92,246,0.12)', groups.address);
        appendGroup('Data Keluarga', 'fa-users', '#10b981', 'rgba(16,185,129,0.12)', groups.family);
        appendGroup('Data Lengkap', 'fa-clipboard-list', '#3b82f6', 'rgba(59,130,246,0.12)', groups.complete);
        appendGroup('Dan Lain-lain', 'fa-layer-group', '#0ea5e9', 'rgba(14,165,233,0.12)', groups.others);

        // Meta: Dibuat/Diperbarui (tanpa card, tetap estetik di bawah section)
        if (metaCreatedAt || metaUpdatedAt) {
            const metaEl = document.createElement('div');
            metaEl.className = 'col-12';

            const createdLabel = escapeHtml(humanizeColumn('created_at'));
            const updatedLabel = escapeHtml(humanizeColumn('updated_at'));

            const createdValue = escapeHtml(formatValue('created_at', metaCreatedAt));
            const updatedValue = escapeHtml(formatValue('updated_at', metaUpdatedAt));

            metaEl.innerHTML =
                '<div class="pt-2 mt-1 border-top">' +
                    '<div class="d-flex flex-column flex-md-row gap-2 gap-md-3 small">' +
                        '<div class="d-flex align-items-center gap-2 text-muted">' +
                            '<i class="fas fa-calendar-plus" style="color:#0ea5e9;"></i>' +
                            '<span class="fw-semibold">' + createdLabel + ':</span>' +
                            '<span class="text-dark fw-semibold">' + createdValue + '</span>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-2 text-muted">' +
                            '<i class="fas fa-calendar-check" style="color:#0ea5e9;"></i>' +
                            '<span class="fw-semibold">' + updatedLabel + ':</span>' +
                            '<span class="text-dark fw-semibold">' + updatedValue + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            container.appendChild(metaEl);
        }

        if (section) {
            section.style.display = container.children.length ? '' : 'none';
        }
    }
    
    // Set photo - menggunakan accessor photo_url dari model Student
    const photoUrl = student.photo_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.name || 'Student') + '&background=3b82f6&color=fff&size=200&bold=true';
    document.getElementById('detail-photo').src = photoUrl;
    
    // Set basic info
    document.getElementById('detail-name').textContent = student.name || EMPTY_VALUE;
    document.getElementById('detail-nis').textContent = 'NIS: ' + (student.nis || EMPTY_VALUE);
    
    // Set status badge
    const statusBadge = document.getElementById('detail-status-badge');
    const studentStatus = student.student_status || 'active';
    if (studentStatus === 'active') {
        statusBadge.className = 'badge bg-success px-3 py-2';
        statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i> Aktif';
    } else if (studentStatus === 'graduated') {
        statusBadge.className = 'badge px-3 py-2';
        statusBadge.style.background = 'linear-gradient(135deg, #696b6dff 0%, #696b6dff 100%)';
        statusBadge.style.color = '#fff';
        statusBadge.innerHTML = '<i class="fas fa-graduation-cap me-1"></i> Lulus';
    } else {
        statusBadge.className = 'badge px-3 py-2';
        statusBadge.style.backgroundColor = '#dc3545';
        statusBadge.style.color = 'rgb(253, 253, 253)';
        statusBadge.innerHTML = '<i class="fas fa-pause-circle me-1"></i> Tidak Aktif';
    }
    
    // Data Identitas
    document.getElementById('detail-nis-value').textContent = student.nis || EMPTY_VALUE;
    document.getElementById('detail-nisn').textContent = student.nisn || EMPTY_VALUE;
    document.getElementById('detail-nik').textContent = student.national_id_number || EMPTY_VALUE;
    document.getElementById('detail-gender').textContent = student.gender_label || EMPTY_VALUE;
    document.getElementById('detail-birth-place').textContent = student.place_of_birth || EMPTY_VALUE;
    const birthDate = student.date_of_birth;
    document.getElementById('detail-birth-date').textContent = birthDate ? new Date(birthDate).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : EMPTY_VALUE;
    document.getElementById('detail-phone').textContent = student.phone_number || EMPTY_VALUE;
    
    // Email - check both student.email and student.user.email
    const email = student.email || (student.user ? student.user.email : null) || EMPTY_VALUE;
    document.getElementById('detail-email').textContent = email;
    
    // Data Akademik
    let className = EMPTY_VALUE;
    if (student.current_grade_level) {
        className = 'Kelas ' + student.current_grade_level;
    }
    document.getElementById('detail-class').textContent = className;

    // Tahun Ajaran (tampilkan jika ada)
    const academicYearEl = document.getElementById('detail-academic-year');
    const academicYearValue =
        student.academic_year ||
        student.academicYear ||
        student.school_year ||
        student.tahun_ajaran ||
        student.academic_year_label ||
        null;

    if (academicYearEl) {
        academicYearEl.textContent = academicYearValue || EMPTY_VALUE;
        const academicYearCol = academicYearEl.closest('.col-12, .col-sm-6, .col-md-4');
        if (academicYearCol) {
            academicYearCol.style.display = academicYearValue ? '' : 'none';
        }
    }
    document.getElementById('detail-major').textContent = student.major || EMPTY_VALUE;
    
    let statusText = EMPTY_VALUE;
    const detailStatus = student.student_status || 'active';
    if (detailStatus === 'active') statusText = 'Aktif';
    else if (detailStatus === 'graduated') statusText = 'Lulus';
    else if (detailStatus === 'inactive') statusText = 'Tidak Aktif';
    document.getElementById('detail-status').textContent = statusText;
    
    // Data Keluarga
    document.getElementById('detail-father').textContent = student.father_name || EMPTY_VALUE;
    document.getElementById('detail-mother').textContent = student.mother_name || EMPTY_VALUE;
    
    // Alamat
    document.getElementById('detail-address').textContent = student.address || EMPTY_VALUE;

    // Render semua field tabel students
    renderAllStudentFields(student);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('studentDetailModal'));
    modal.show();
}

// Real-time validation for NIS and NISN
let nisTimeout, nisnTimeout;

function showDuplicateAlert(field, value) {
    console.log('showDuplicateAlert called:', field, value); // Debug
    const alertDiv = document.getElementById('duplicate-alert');
    const messageSpan = document.getElementById('duplicate-message');
    const fieldName = field === 'nis' ? 'NIS' : 'NISN';
    
    if (!alertDiv || !messageSpan) {
        console.error('Alert elements not found!');
        return;
    }
    
    messageSpan.innerHTML = `<strong>${fieldName} "${value}"</strong> sudah terdaftar di database. Silakan gunakan nomor yang berbeda.`;
    
    // Show with animation
    alertDiv.style.display = 'block';
    alertDiv.style.animation = 'none';
    setTimeout(() => {
        alertDiv.style.animation = 'shake 0.5s';
    }, 10);
    
    // Scroll to alert smoothly
    setTimeout(() => {
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
    
    console.log('Alert shown successfully!'); // Debug
}

function hideDuplicateAlert() {
    const nisInput = document.getElementById('nis');
    const nisnInput = document.getElementById('nisn');
    const alertDiv = document.getElementById('duplicate-alert');
    
    // Only hide if both fields are valid or empty
    if ((!nisInput?.classList.contains('is-invalid') || !nisInput?.value) && 
        (!nisnInput?.classList.contains('is-invalid') || !nisnInput?.value)) {
        alertDiv.style.display = 'none';
        console.log('Alert hidden'); // Debug
    }
}

document.getElementById('nis')?.addEventListener('input', function(e) {
    console.log('NIS input event triggered'); // Debug
    clearTimeout(nisTimeout);
    const nis = e.target.value.trim();
    const feedback = document.getElementById('nis-feedback');
    
    console.log('NIS value:', nis, 'Length:', nis.length); // Debug
    
    if (nis.length < 3) {
        e.target.classList.remove('is-valid', 'is-invalid');
        feedback.textContent = '';
        hideDuplicateAlert();
        return;
    }
    
    feedback.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
    feedback.className = 'text-muted';
    
    nisTimeout = setTimeout(() => {
        const checkUrl = '{{ route("dashboard.siswa.check") }}?field=nis&value=' + encodeURIComponent(nis);
        console.log('Fetching:', checkUrl); // Debug
        
        fetch(checkUrl)
            .then(res => {
                console.log('Response status:', res.status); // Debug
                return res.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug
                if (data.exists) {
                    console.log('NIS exists! Showing alert...'); // Debug
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.innerHTML = '<i class="fas fa-times-circle"></i> NIS sudah terdaftar!';
                    feedback.className = 'text-danger';
                    showDuplicateAlert('nis', nis);
                } else {
                    console.log('NIS available'); // Debug
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.innerHTML = '<i class="fas fa-check-circle"></i> NIS tersedia';
                    feedback.className = 'text-success';
                    hideDuplicateAlert();
                }
            })
            .catch(err => {
                console.error('Fetch error:', err); // Debug
                e.target.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
            });
    }, 500);
});

document.getElementById('nisn')?.addEventListener('input', function(e) {
    console.log('NISN input event triggered'); // Debug
    clearTimeout(nisnTimeout);
    const nisn = e.target.value.trim();
    const feedback = document.getElementById('nisn-feedback');
    
    console.log('NISN value:', nisn, 'Length:', nisn.length); // Debug
    
    if (nisn.length < 3) {
        e.target.classList.remove('is-valid', 'is-invalid');
        feedback.textContent = '';
        hideDuplicateAlert();
        return;
    }
    
    feedback.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
    feedback.className = 'text-muted';
    
    nisnTimeout = setTimeout(() => {
        const checkUrl = '{{ route("dashboard.siswa.check") }}?field=nisn&value=' + encodeURIComponent(nisn);
        console.log('Fetching:', checkUrl); // Debug
        
        fetch(checkUrl)
            .then(res => {
                console.log('Response status:', res.status); // Debug
                return res.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug
                if (data.exists) {
                    console.log('NISN exists! Showing alert...'); // Debug
                    e.target.classList.remove('is-valid');
                    e.target.classList.add('is-invalid');
                    feedback.innerHTML = '<i class="fas fa-times-circle"></i> NISN sudah terdaftar!';
                    feedback.className = 'text-danger';
                    showDuplicateAlert('nisn', nisn);
                } else {
                    console.log('NISN available'); // Debug
                    e.target.classList.remove('is-invalid');
                    e.target.classList.add('is-valid');
                    feedback.innerHTML = '<i class="fas fa-check-circle"></i> NISN tersedia';
                    feedback.className = 'text-success';
                    hideDuplicateAlert();
                }
            })
            .catch(err => {
                console.error('Fetch error:', err); // Debug
                e.target.classList.remove('is-valid', 'is-invalid');
                feedback.textContent = '';
            });
    }, 500);
});

// Prevent form submission if NIS or NISN is invalid
document.querySelector('form[action="{{ route("dashboard.siswa.store") }}"]')?.addEventListener('submit', function(e) {
    const nisInput = document.getElementById('nis');
    const nisnInput = document.getElementById('nisn');
    
    if (nisInput?.classList.contains('is-invalid') || nisnInput?.classList.contains('is-invalid')) {
        e.preventDefault();
        alert('NIS atau NISN sudah terdaftar! Silakan gunakan nomor yang berbeda.');
        return false;
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchStudentInput');
    const form = searchInput?.closest('form');
    
    if (searchInput && form) {
        // Position cursor at the end of text
        const valLength = searchInput.value.length;
        if (valLength > 0) {
            searchInput.focus();
            searchInput.setSelectionRange(valLength, valLength);
        }

        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            
            // Add loading indicator to search icon
            const searchIcon = searchInput.nextElementSibling;
            if (searchIcon && searchIcon.classList.contains('fa-search')) {
                searchIcon.className = 'fas fa-spinner fa-spin position-absolute';
            }
            
            debounceTimer = setTimeout(() => {
                const url = new URL(form.action);
                url.searchParams.set('search', this.value);
                
                const sortSelect = form.querySelector('select[name="sort"]');
                if (sortSelect) {
                    url.searchParams.set('sort', sortSelect.value);
                }
                
                fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newContent = doc.getElementById('siswaTableAndPaginationWrapper');
                    const currentContent = document.getElementById('siswaTableAndPaginationWrapper');
                    
                    if (newContent && currentContent) {
                        currentContent.innerHTML = newContent.innerHTML;
                        window.history.pushState({}, '', url.toString());
                    }
                    
                    // Restore search icon
                    if (searchIcon) {
                        searchIcon.className = 'fas fa-search position-absolute';
                    }
                })
                .catch(err => {
                    console.error('AJAX Search Error:', err);
                    if (searchIcon) searchIcon.className = 'fas fa-search position-absolute';
                });
            }, 300); // 300ms debounce
        });
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            searchInput.dispatchEvent(new Event('input'));
        });
    }
});
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        alert('{{ session("success") }}');
    });
</script>
@endif

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show error alert
        let errorMessages = [
            @foreach($errors->all() as $error)
                '{{ $error }}',
            @endforeach
        ];
        
        // Check if the error is email related
        let hasEmailError = errorMessages.some(msg => msg.toLowerCase().includes('email'));
        
        if (hasEmailError) {
            alert('Terdapat kesalahan pada form:\n\n' + errorMessages.join('\n') + '\n\nEmail yang Anda masukkan sudah digunakan oleh siswa lain. Silakan gunakan email yang berbeda atau kosongkan field email.');
        } else {
            alert('Terdapat kesalahan pada form:\n\n' + errorMessages.join('\n'));
        }
        
        // Re-open the add modal if there are errors (assuming it was from add form)
        // You can enhance this to detect if it's from edit or add form
        @if(old('nis'))
            var addModal = new bootstrap.Modal(document.getElementById('addSiswaModal'));
            addModal.show();
        @endif
    });
</script>
@endif

@endsection
