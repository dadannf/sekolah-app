@extends('layouts.student')

@section('title', 'Home - Dashboard Siswa')
@section('page-title', 'Dashboard Siswa')

@section('content')
<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="position-relative overflow-hidden rounded-4 shadow-sm p-4 p-md-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="position-absolute top-0 end-0 opacity-10" style="font-size: 15rem; margin-top: -3rem; margin-right: -3rem;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="row align-items-center position-relative">
                    <div class="col-md-8">
                        <h2 class="text-white fw-bold mb-2" style="font-size: clamp(1.25rem, 4vw, 1.75rem);">
                            Halo, {{ explode(' ', $student->name)[0] }}! 🎓
                        </h2>
                        <p class="text-white mb-3 mb-md-4" style="opacity: 0.95; font-size: clamp(0.875rem, 2vw, 1rem);">
                            "Pendidikan adalah kunci untuk membuka pintu kesempatan emas." - George Washington Carver
                        </p>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                        @if($student->photo_path)
                            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="Foto" class="rounded-circle shadow-lg" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid rgba(255,255,255,0.3);">
                        @else
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-lg" style="width: 120px; height: 120px; background: rgba(255,255,255,0.2); border: 4px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-user text-white" style="font-size: 3rem;"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi & Pengumuman Terbaru -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
                <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
                    <h5 class="fw-bold text-white mb-0 d-flex align-items-center" style="font-size: 1rem;">
                        <i class="fas fa-bullhorn me-2"></i>
                        Informasi & Pengumuman Terbaru
                    </h5>
                </div>
                <div class="p-3 p-md-4">
                    @forelse($information as $info)
                        <div class="row mb-3 {{ $loop->last ? '' : 'pb-3 border-bottom' }}">
                            <div class="col-md-4 mb-3 mb-md-0">
                                @if($info->image_path)
                                    <img src="{{ asset('storage/' . $info->image_path) }}" alt="{{ $info->title }}" class="img-fluid rounded-3 w-100" style="object-fit: cover; height: 200px;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded-3 w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 200px;">
                                        <i class="fas fa-image text-white" style="font-size: 3rem; opacity: 0.5;"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-8">
                                <h4 class="fw-bold text-dark mb-2" style="font-size: 1.125rem;">{{ $info->title }}</h4>
                                <p class="text-secondary mb-3" style="font-size: 0.875rem;">{{ Str::limit($info->body, 120) }}</p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ \Carbon\Carbon::parse($info->created_at)->format('d M Y') }}
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showInfoDetail({{ $info->id }})" style="font-size: 0.8rem;">
                                        <i class="fas fa-eye me-1"></i>Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(29, 78, 216, 0.1) 100%);">
                                <i class="fas fa-bullhorn" style="font-size: 2rem; color: #3b82f6;"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-2">Belum Ada Informasi</h6>
                            <p class="text-muted small mb-0">Informasi dan pengumuman akan tampil di sini</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Informasi -->
<div class="modal fade" id="informationDetailModal" tabindex="-1" aria-labelledby="informationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
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
                            <img id="detailImage" src="" alt="" class="w-100 h-100" style="object-fit: cover;">
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
                            <div class="col-12">
                                <div class="d-flex align-items-center text-muted" style="font-size: 0.9rem;">
                                    <i class="fas fa-calendar-alt me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailDate"></span>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-user me-2" style="color: #6b7280; width: 16px;"></i>
                                    <span id="detailCreatedBy"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Title -->
                        <h4 class="fw-bold text-dark mb-3" id="detailTitle" style="line-height: 1.4; font-size: 1.3rem;"></h4>
                        
                        <!-- Body Content -->
                        <div class="mb-4">
                            <div id="detailBody" class="text-dark" style="font-size: 0.95rem; line-height: 1.8; white-space: pre-wrap;"></div>
                        </div>
                        
                        <!-- Attachment Section (if exists) -->
                        <div id="detailAttachment" style="display: none;">
                            <h6 class="fw-bold text-dark mb-3" style="font-size: 1rem;">
                                <i class="fas fa-paperclip me-2"></i>
                                Lampiran File
                            </h6>
                            <div class="p-3 rounded-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(29, 78, 216, 0.05) 100%); border: 1px solid rgba(59, 130, 246, 0.2);">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                        <i class="fas fa-image text-white" style="font-size: 1.2rem;"></i>
                                    </div>
                                    <div>
                                        <p class="fw-semibold text-dark mb-0" style="font-size: 0.9rem;" id="attachmentFileName">Gambar</p>
                                        <p class="text-muted small mb-0">Gambar</p>
                                    </div>
                                </div>
                                <a id="attachmentLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>Lihat
                                </a>
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
            </div>
        </div>
    </div>
</div>

<script>
function showInfoDetail(infoId) {
    const modal = new bootstrap.Modal(document.getElementById('informationDetailModal'));
    const loadingDiv = document.getElementById('modalLoading');
    const contentDiv = document.getElementById('modalContent');
    
    // Show loading state
    loadingDiv.style.display = 'block';
    contentDiv.style.display = 'none';
    
    // Show modal
    modal.show();
    
    // Fetch information details
    fetch(`/student/information/${infoId}/detail`)
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
    const attachmentSection = document.getElementById('detailAttachment');
    const attachmentLink = document.getElementById('attachmentLink');
    
    if (info.image_url) {
        detailImage.src = info.image_url;
        detailImage.alt = info.title;
        imageSection.style.display = 'block';
        
        // Also show in attachment section
        attachmentLink.href = info.image_url;
        attachmentSection.style.display = 'block';
    } else {
        imageSection.style.display = 'none';
        attachmentSection.style.display = 'none';
    }
    
    // Set type badge
    const typeBadge = document.getElementById('detailTypeBadge');
    let badgeStyle = '';
    let badgeText = '';
    let badgeIcon = '';
    
    switch (info.info_type) {
        case 'general':
            badgeStyle = 'background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;';
            badgeText = 'Informasi Sekolah';
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
    
    // Set meta information - format date in Indonesian
    const dateStr = info.created_at_formatted;
    document.getElementById('detailDate').textContent = dateStr;
    document.getElementById('detailCreatedBy').textContent = info.created_by_name || 'admin';
    
    // Set title and body
    document.getElementById('detailTitle').textContent = info.title;
    document.getElementById('detailBody').textContent = info.body;
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
