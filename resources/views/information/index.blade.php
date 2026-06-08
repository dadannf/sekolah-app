@extends('layouts.app')

@section('title', 'Informasi & Pengumuman')
@section('page-title', 'Informasi & Pengumuman')
@section('page-subtitle', 'Kelola dan publikasikan informasi penting untuk seluruh warga sekolah')

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
        <!-- Total Informasi -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(59, 130, 246, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(59, 130, 246, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-bullhorn text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TOTAL INFORMASI</p>
                        <h3 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $informations->total() }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-bullhorn" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <!-- Informasi Aktif -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); box-shadow: 0 8px 16px rgba(25, 135, 84, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(25, 135, 84, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(25, 135, 84, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-check-circle text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">INFORMASI AKTIF</p>
                        <h3 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $informations->where('is_active', 1)->count() }}</h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-check-circle" style="font-size: 6rem;"></i>
                </div>
            </div>
        </div>

        <!-- Terakhir Dipublikasi -->
        <div class="col-12 col-md-4">
            <div class="position-relative overflow-hidden rounded-4 p-3 p-md-4" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); box-shadow: 0 8px 16px rgba(139, 92, 246, 0.25); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(139, 92, 246, 0.35)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 16px rgba(139, 92, 246, 0.25)'">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-25 d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 56px; height: 56px; backdrop-filter: blur(10px);">
                        <i class="fas fa-calendar-check text-white" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <p class="text-white mb-1 fw-medium" style="font-size: 0.8rem; opacity: 0.9; letter-spacing: 0.5px;">TERAKHIR DIPUBLIKASI</p>
                        <h3 class="fs-6 fs-md-5 fw-bold text-white mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            @if($informations->count() > 0)
                                {{ optional($informations->first()->created_at)->format('d M Y') }}
                            @else
                                -
                            @endif
                        </h3>
                    </div>
                </div>
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.1;">
                    <i class="fas fa-calendar-check" style="font-size: 6rem;"></i>
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
                        <i class="fas fa-bullhorn me-2" style="font-size: 1.3rem;"></i>
                        Informasi & Pengumuman
                    </h2>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="row g-2">
                        <div class="col-12 col-sm-7 col-md-8">
                            <form method="GET" action="{{ route('information.index') }}" class="position-relative">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul atau isi informasi..." class="form-control ps-5 border-0" style="background: rgba(255,255,255,0.95); font-size: 0.9rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 10px 16px 10px 45px;">
                                <i class="fas fa-search position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); font-size: 0.95rem; color: #3b82f6;"></i>
                                <button type="submit" class="d-none"></button>
                            </form>
                        </div>
                        <div class="col-12 col-sm-5 col-md-4">
                            <a href="{{ route('information.create') }}" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(251, 191, 36, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                                <i class="fas fa-plus me-2"></i>
                                <span class="d-none d-sm-inline">Tambah Informasi</span>
                                <span class="d-inline d-sm-none">Tambah</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-3 p-md-4">
            @if($informations->count() > 0)
                <!-- Grid Cards Layout -->
                <div class="row g-3 g-md-4">
                    @foreach($informations as $index => $info)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden" style="border-radius: 16px; transition: all 0.3s ease; background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,1) 100%);" 
                            onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 12px 32px rgba(0,0,0,0.15)'" 
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'">
                            
                            <!-- Card Header with Image or Icon -->
                            <div class="position-relative" style="height: 200px; background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); overflow: hidden;">
                                @if($info->image_path)
                                    <img src="{{ $info->image_url }}" alt="{{ $info->title }}" 
                                        class="w-100 h-100" 
                                        style="object-fit: cover; cursor: pointer;"
                                        onclick="openImageModal('{{ $info->image_url }}', '{{ $info->title }}')"
                                        title="Klik untuk memperbesar">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <div class="text-center">
                                            <div class="rounded-circle bg-white bg-opacity-25 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; backdrop-filter: blur(10px);">
                                                @if($info->info_type === 'general')
                                                    <i class="fas fa-bullhorn text-white" style="font-size: 2rem;"></i>
                                                @elseif($info->info_type === 'spp')
                                                    <i class="fas fa-money-bill-wave text-white" style="font-size: 2rem;"></i>
                                                @else
                                                    <i class="fas fa-graduation-cap text-white" style="font-size: 2rem;"></i>
                                                @endif
                                            </div>
                                            <h6 class="text-white fw-bold mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                                @if($info->info_type === 'general')
                                                    Informasi Umum
                                                @elseif($info->info_type === 'spp')
                                                    Informasi SPP
                                                @else
                                                    Informasi Akademik
                                                @endif
                                            </h6>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Priority Badge -->
                                <div class="position-absolute top-0 start-0 m-3">
                                    <span class="badge px-3 py-2 rounded-pill fw-bold" style="background: rgba(255,255,255,0.9); color: #3b82f6; font-size: 0.8rem; backdrop-filter: blur(10px);">
                                        #{{ $informations->firstItem() + $index }}
                                    </span>
                                </div>
                                
                                <!-- Status Badge -->
                                <div class="position-absolute top-0 end-0 m-3">
                                    @if($info->isCurrentlyActive())
                                        <span class="badge px-3 py-2 rounded-pill" style="background: rgba(34, 197, 94, 0.9); color: white; font-weight: 600; backdrop-filter: blur(10px);">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge px-3 py-2 rounded-pill" style="background: rgba(239, 68, 68, 0.9); color: white; font-weight: 600; backdrop-filter: blur(10px);">
                                            <i class="fas fa-times-circle me-1"></i>
                                            {{ $info->getStatusLabel() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Card Body -->
                            <div class="card-body p-4 d-flex flex-column">
                                <!-- Title -->
                                <h5 class="card-title fw-bold text-dark mb-2" style="font-size: 1.1rem; line-height: 1.4;">
                                    {{ Str::limit($info->title, 50) }}
                                </h5>
                                
                                <!-- Description -->
                                <p class="card-text text-muted mb-3 flex-grow-1" style="font-size: 0.9rem; line-height: 1.5;">
                                    {{ Str::limit($info->body, 80) }}
                                </p>
                                
                                <!-- Meta Information -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center text-muted mb-2" style="font-size: 0.85rem;">
                                        <i class="fas fa-clock me-2" style="color: #6b7280;"></i>
                                        <span>{{ $info->created_at ? $info->created_at->format('d M Y, H:i') : '-' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center text-muted" style="font-size: 0.85rem;">
                                        <i class="fas fa-bullseye me-2" style="color: #6b7280;"></i>
                                        <span>{{ $info->getTargetDescription() }}</span>
                                    </div>
                                </div>
                                
                                <!-- Type Badge -->
                                <div class="mb-3">
                                    @if($info->info_type === 'general')
                                        <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600;">
                                            <i class="fas fa-bullhorn me-1" style="font-size: 0.75rem;"></i>
                                            Umum
                                        </span>
                                    @elseif($info->info_type === 'spp')
                                        <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; font-weight: 600;">
                                            <i class="fas fa-money-bill-wave me-1" style="font-size: 0.75rem;"></i>
                                            SPP
                                        </span>
                                    @else
                                        <span class="badge px-3 py-2 rounded-pill" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; font-weight: 600;">
                                            <i class="fas fa-graduation-cap me-1" style="font-size: 0.75rem;"></i>
                                            Akademik
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="mt-auto">
                                    <!-- View Button -->
                                    <button type="button" 
                                        class="btn w-100 text-white border-0 shadow-sm fw-semibold mb-2" 
                                        style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); font-size: 0.9rem; border-radius: 12px; padding: 10px 16px; transition: all 0.3s ease;"
                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(59, 130, 246, 0.4)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'"
                                        onclick="showInformationDetail({{ $info->id }})">
                                        <i class="fas fa-eye me-2"></i>
                                        Lihat Informasi
                                    </button>
                                    
                                    <!-- Action Buttons Row -->
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <a href="{{ route('information.edit', $info->id) }}" 
                                                class="btn w-100 text-white border-0 shadow-sm fw-semibold" 
                                                style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.85rem; border-radius: 10px; padding: 8px 12px; transition: all 0.3s ease; text-decoration: none;"
                                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(251, 191, 36, 0.4)'"
                                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                                                <i class="fas fa-edit me-1"></i>
                                                Edit
                                            </a>
                                        </div>
                                        <div class="col-6">
                                            <form method="POST" action="{{ route('information.destroy', $info->id) }}" 
                                                onsubmit="return confirm('Yakin ingin menghapus informasi ini?')" 
                                                style="display:inline;" class="w-100">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="btn w-100 text-white border-0 shadow-sm fw-semibold" 
                                                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); font-size: 0.85rem; border-radius: 10px; padding: 8px 12px; transition: all 0.3s ease;"
                                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)'"
                                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
                                                    <i class="fas fa-trash me-1"></i>
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                    <p class="small text-secondary mb-0">Menampilkan {{ $informations->count() }} dari {{ $informations->total() }} informasi</p>
                    <div>
                        {{ $informations->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="py-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                            <i class="fas fa-bullhorn" style="font-size: 2.5rem; color: #3b82f6;"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Belum Ada Informasi</h6>
                        <p class="text-muted small mb-0">Mulai buat pengumuman pertama dengan klik tombol "Tambah Informasi" di atas</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <h5 class="modal-title text-white fw-bold" id="imageModalLabel">
                    <i class="fas fa-image me-2"></i>
                    Gambar Informasi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <img id="modalImage" src="" alt="" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<!-- Information Detail Modal -->
<div class="modal fade" id="informationDetailModal" tabindex="-1" aria-labelledby="informationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header border-0 position-relative" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 24px;">
                <div class="position-absolute" style="top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
                <h5 class="modal-title text-white fw-bold mb-0" id="informationDetailModalLabel" style="font-size: 1.2rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <i class="fas fa-info-circle me-2"></i>
                    Detail Informasi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
                <!-- Loading State -->
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 mb-0">Memuat detail informasi...</p>
                </div>
                
                <!-- Content Container -->
                <div id="modalContent" style="display: none;">
                    <!-- Image Section -->
                    <div id="modalImageSection" style="display: none;">
                        <div class="position-relative" style="height: 250px; background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); overflow: hidden;">
                            <img id="detailImage" src="" alt="" class="w-100 h-100" style="object-fit: cover; cursor: pointer;" onclick="openImageModal(this.src, this.alt)">
                            <div class="position-absolute bottom-0 start-0 end-0" style="background: linear-gradient(transparent, rgba(0,0,0,0.3)); padding: 20px 24px 16px;">
                                <p class="text-white mb-0 small" style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">Klik gambar untuk memperbesar</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Section -->
                    <div class="p-4">
                        <!-- Type Badge -->
                        <div class="mb-3">
                            <span id="detailTypeBadge" class="badge px-3 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;"></span>
                        </div>
                        
                        <!-- Meta Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center text-muted" style="font-size: 0.9rem;">
                                    <i class="fas fa-calendar-alt me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailDate"></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center text-muted" style="font-size: 0.9rem;">
                                    <i class="fas fa-user me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailCreatedBy"></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center text-muted" style="font-size: 0.9rem;">
                                    <i class="fas fa-bullseye me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailTarget"></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-toggle-on me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailStatus" class="badge px-2 py-1 rounded-pill" style="font-size: 0.8rem;"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Title -->
                        <h4 class="fw-bold text-dark mb-3" id="detailTitle" style="line-height: 1.4; font-size: 1.3rem;"></h4>
                        
                        <!-- Body Content -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 1rem;">Isi Informasi:</h6>
                            <div id="detailBody" class="text-dark" style="font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;"></div>
                        </div>
                        
                        <!-- Schedule Info (if exists) -->
                        <div id="detailSchedule" style="display: none;">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 1rem;">Jadwal Publikasi:</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <div class="p-3 rounded-3" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(21, 128, 61, 0.1) 100%); border: 1px solid rgba(34, 197, 94, 0.2);">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-play-circle me-2" style="color: #22c55e;"></i>
                                            <div>
                                                <small class="text-muted d-block" style="font-size: 0.8rem;">Mulai</small>
                                                <span id="detailStartAt" class="fw-medium text-dark" style="font-size: 0.9rem;"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="p-3 rounded-3" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(185, 28, 28, 0.1) 100%); border: 1px solid rgba(239, 68, 68, 0.2);">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-stop-circle me-2" style="color: #ef4444;"></i>
                                            <div>
                                                <small class="text-muted d-block" style="font-size: 0.8rem;">Berakhir</small>
                                                <span id="detailEndAt" class="fw-medium text-dark" style="font-size: 0.9rem;"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer border-0 p-4" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(29, 78, 216, 0.05) 100%);">
                <button type="button" class="btn text-white fw-semibold px-4" 
                    style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); border-radius: 10px; transition: all 0.3s ease;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(107, 114, 128, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'"
                    data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Tutup
                </button>
                <a id="detailEditButton" href="#" class="btn text-white fw-semibold px-4" 
                    style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 10px; transition: all 0.3s ease; text-decoration: none;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <i class="fas fa-edit me-2"></i>
                    Edit Informasi
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(imageSrc, imageTitle) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('imageModalLabel');
    
    modalImage.src = imageSrc;
    modalImage.alt = imageTitle;
    modalTitle.innerHTML = '<i class="fas fa-image me-2"></i>' + imageTitle;
    
    modal.show();
}

function showInformationDetail(informationId) {
    const modal = new bootstrap.Modal(document.getElementById('informationDetailModal'));
    const loadingDiv = document.getElementById('modalLoading');
    const contentDiv = document.getElementById('modalContent');
    
    // Show loading state
    loadingDiv.style.display = 'block';
    contentDiv.style.display = 'none';
    
    // Show modal
    modal.show();
    
    // Fetch information details
    fetch(`/information/${informationId}/detail`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateModalContent(data.information);
                loadingDiv.style.display = 'none';
                contentDiv.style.display = 'block';
            } else {
                showError('Gagal memuat detail informasi');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Terjadi kesalahan saat memuat data');
        });
}

function populateModalContent(info) {
    // Set image
    const imageSection = document.getElementById('modalImageSection');
    const detailImage = document.getElementById('detailImage');
    
    if (info.image_url) {
        detailImage.src = info.image_url;
        detailImage.alt = info.title;
        imageSection.style.display = 'block';
    } else {
        imageSection.style.display = 'none';
    }
    
    // Set type badge
    const typeBadge = document.getElementById('detailTypeBadge');
    let badgeStyle = '';
    let badgeText = '';
    let badgeIcon = '';
    
    switch (info.info_type) {
        case 'general':
            badgeStyle = 'background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;';
            badgeText = 'Umum';
            badgeIcon = 'fas fa-bullhorn';
            break;
        case 'spp':
            badgeStyle = 'background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white;';
            badgeText = 'SPP';
            badgeIcon = 'fas fa-money-bill-wave';
            break;
        case 'academic':
            badgeStyle = 'background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white;';
            badgeText = 'Akademik';
            badgeIcon = 'fas fa-graduation-cap';
            break;
    }
    
    typeBadge.style.cssText = badgeStyle;
    typeBadge.innerHTML = `<i class="${badgeIcon} me-1"></i>${badgeText}`;
    
    // Set meta information
    document.getElementById('detailDate').textContent = info.created_at_formatted;
    document.getElementById('detailCreatedBy').textContent = info.created_by_name || 'Admin';
    document.getElementById('detailTarget').textContent = info.target_description;
    
    // Set status
    const statusBadge = document.getElementById('detailStatus');
    if (info.is_currently_active) {
        statusBadge.style.cssText = 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;';
        statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Aktif';
    } else {
        statusBadge.style.cssText = 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;';
        statusBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>' + info.status_label;
    }
    
    // Set title and body
    document.getElementById('detailTitle').textContent = info.title;
    document.getElementById('detailBody').textContent = info.body;
    
    // Set schedule info
    const scheduleDiv = document.getElementById('detailSchedule');
    if (info.start_at || info.end_at) {
        document.getElementById('detailStartAt').textContent = info.start_at_formatted || 'Tidak ditentukan';
        document.getElementById('detailEndAt').textContent = info.end_at_formatted || 'Tidak ditentukan';
        scheduleDiv.style.display = 'block';
    } else {
        scheduleDiv.style.display = 'none';
    }
    
    // Set edit button
    document.getElementById('detailEditButton').href = `/information/${info.id}/edit`;
}

function showError(message) {
    const loadingDiv = document.getElementById('modalLoading');
    loadingDiv.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
            <h6 class="text-dark mt-3 mb-2">Oops! Terjadi Kesalahan</h6>
            <p class="text-muted mb-0">${message}</p>
        </div>
    `;
}
</script>
@endsection
