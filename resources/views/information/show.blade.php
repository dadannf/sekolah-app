@extends('layouts.app')

@section('title', 'Detail Informasi')
@section('page-title', 'Detail Informasi')
@section('page-subtitle', 'Lihat detail pengumuman atau informasi')

@section('content')
<div class="mb-4">
    <!-- Back Link -->
    <a href="{{ route('information.index') }}" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color: #3b82f6; font-weight: 600; font-size: 0.9rem; transition: all 0.2s ease;" onmouseover="this.style.color='#1d4ed8'" onmouseout="this.style.color='#3b82f6'">
        <i class="fas fa-arrow-left me-2"></i>
        Kembali ke Daftar Informasi
    </a>

    <!-- Main Content Card -->
    <div class="bg-white rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(59, 130, 246, 0.1);">
        <!-- Card Header -->
        <div class="px-3 px-md-4 py-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); position: relative; overflow: hidden;">
            <div class="position-absolute" style="top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
            <div class="row align-items-center">
                <div class="col-12 col-lg-8">
                    <h2 class="fs-5 fs-md-4 fw-bold text-white mb-2" style="letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <i class="fas fa-eye me-2" style="font-size: 1.3rem;"></i>
                        {{ $information->title }}
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        @if($information->info_type === 'general')
                            <span class="badge px-3 py-2 rounded-pill" style="background: rgba(255,255,255,0.2); color: white; font-weight: 600; border: 1px solid rgba(255,255,255,0.3);">
                                <i class="fas fa-bullhorn me-1"></i>Umum
                            </span>
                        @elseif($information->info_type === 'spp')
                            <span class="badge px-3 py-2 rounded-pill" style="background: rgba(251, 191, 36, 0.3); color: white; font-weight: 600; border: 1px solid rgba(251, 191, 36, 0.5);">
                                <i class="fas fa-money-bill-wave me-1"></i>SPP
                            </span>
                        @else
                            <span class="badge px-3 py-2 rounded-pill" style="background: rgba(139, 92, 246, 0.3); color: white; font-weight: 600; border: 1px solid rgba(139, 92, 246, 0.5);">
                                <i class="fas fa-graduation-cap me-1"></i>Akademik
                            </span>
                        @endif

                        @if($information->isCurrentlyActive())
                            <span class="badge px-3 py-2 rounded-pill" style="background: rgba(25, 135, 84, 0.3); color: white; font-weight: 600; border: 1px solid rgba(25, 135, 84, 0.5);">
                                <i class="fas fa-check-circle me-1"></i>{{ $information->getStatusLabel() }}
                            </span>
                        @else
                            <span class="badge px-3 py-2 rounded-pill" style="background: rgba(220, 53, 69, 0.3); color: white; font-weight: 600; border: 1px solid rgba(220, 53, 69, 0.5);">
                                <i class="fas fa-times-circle me-1"></i>{{ $information->getStatusLabel() }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-flex flex-column flex-lg-row gap-2 justify-content-lg-end">
                        <a href="{{ route('information.edit', $information->id) }}" class="btn fw-semibold border-0 shadow-sm" style="background: rgba(251, 191, 36, 0.2); color: white; font-size: 0.9rem; border-radius: 10px; padding: 8px 16px; transition: all 0.3s ease; text-decoration: none; border: 1px solid rgba(251, 191, 36, 0.3) !important;" onmouseover="this.style.background='rgba(251, 191, 36, 0.3)'" onmouseout="this.style.background='rgba(251, 191, 36, 0.2)'">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a>
                        <form method="POST" action="{{ route('information.toggle', $information->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn fw-semibold border-0 shadow-sm" style="background: rgba({{ $information->is_active ? '107, 114, 128' : '16, 185, 129' }}, 0.2); color: white; font-size: 0.9rem; border-radius: 10px; padding: 8px 16px; transition: all 0.3s ease; border: 1px solid rgba({{ $information->is_active ? '107, 114, 128' : '16, 185, 129' }}, 0.3) !important;" onmouseover="this.style.background='rgba({{ $information->is_active ? '107, 114, 128' : '16, 185, 129' }}, 0.3)'" onmouseout="this.style.background='rgba({{ $information->is_active ? '107, 114, 128' : '16, 185, 129' }}, 0.2)'">
                                <i class="fas fa-{{ $information->is_active ? 'pause' : 'play' }} me-2"></i>
                                {{ $information->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="p-3 p-md-4">
            <!-- Information Content -->
            <div class="mb-4">
                <h3 class="fs-6 fw-bold text-dark mb-3">Isi Informasi</h3>
                <div class="p-4 rounded-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, rgba(29, 78, 216, 0.03) 100%); border: 1px solid rgba(59, 130, 246, 0.1);">
                    @if($information->image_path)
                    <!-- Image Display -->
                    <div class="mb-3 text-center">
                        <img src="{{ $information->image_url }}" alt="{{ $information->title }}" 
                            class="img-fluid rounded-3 shadow-sm" 
                            style="max-width: 100%; max-height: 400px; object-fit: cover; cursor: pointer;"
                            onclick="openImageModal(this.src, '{{ $information->title }}')"
                            title="Klik untuk memperbesar">
                    </div>
                    @endif
                    <p class="text-dark mb-0" style="font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;">{{ $information->body }}</p>
                </div>
            </div>

            <!-- Information Details -->
            <div class="row g-4">
                <!-- Target Information -->
                <div class="col-12 col-md-6">
                    <div class="p-4 rounded-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(29, 78, 216, 0.05) 100%); border: 2px dashed rgba(59, 130, 246, 0.2);">
                        <h4 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center">
                            <i class="fas fa-bullseye me-2" style="color: #3b82f6;"></i>
                            Target Informasi
                        </h4>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 80px;">Target:</span>
                                <span class="text-muted" style="font-size: 0.85rem;">{{ $information->getTargetDescription() }}</span>
                            </div>
                            @if($information->target_class)
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 80px;">Kelas:</span>
                                <span class="badge px-2 py-1 rounded-pill" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; font-weight: 600; font-size: 0.75rem;">
                                    {{ $information->target_class }}
                                </span>
                            </div>
                            @endif
                            @if($information->targetStudent)
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 80px;">Siswa:</span>
                                <span class="text-dark" style="font-size: 0.85rem;">{{ $information->targetStudent->name }} ({{ $information->targetStudent->nis }})</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="col-12 col-md-6">
                    <div class="p-4 rounded-3" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%); border: 2px dashed rgba(139, 92, 246, 0.2);">
                        <h4 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center">
                            <i class="fas fa-calendar-alt me-2" style="color: #8b5cf6;"></i>
                            Jadwal Publikasi
                        </h4>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 100px;">Mulai:</span>
                                <span class="text-muted" style="font-size: 0.85rem;">
                                    @if($information->start_at)
                                        {{ $information->start_at->format('d M Y, H:i') }}
                                    @else
                                        Langsung aktif
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 100px;">Berakhir:</span>
                                <span class="text-muted" style="font-size: 0.85rem;">
                                    @if($information->end_at)
                                        {{ $information->end_at->format('d M Y, H:i') }}
                                    @else
                                        Tidak kadaluwarsa
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold text-dark me-2" style="font-size: 0.85rem; min-width: 100px;">Status:</span>
                                @if($information->isCurrentlyActive())
                                    <span class="badge px-2 py-1 rounded-pill" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; font-weight: 600; font-size: 0.75rem;">
                                        <i class="fas fa-check-circle me-1"></i>{{ $information->getStatusLabel() }}
                                    </span>
                                @else
                                    <span class="badge px-2 py-1 rounded-pill" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; font-weight: 600; font-size: 0.75rem;">
                                        <i class="fas fa-times-circle me-1"></i>{{ $information->getStatusLabel() }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Meta Information -->
            <div class="mt-4 pt-4 border-top" style="border-color: rgba(59, 130, 246, 0.1) !important;">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle me-2" style="color: #3b82f6; font-size: 1.1rem;"></i>
                            <div>
                                <span class="fw-semibold text-dark d-block" style="font-size: 0.85rem;">Dibuat oleh</span>
                                <span class="text-muted" style="font-size: 0.8rem;">{{ $information->createdBy->name ?? 'Unknown' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2" style="color: #3b82f6; font-size: 1.1rem;"></i>
                            <div>
                                <span class="fw-semibold text-dark d-block" style="font-size: 0.85rem;">Tanggal dibuat</span>
                                <span class="text-muted" style="font-size: 0.8rem;">{{ $information->created_at ? $information->created_at->format('d M Y, H:i') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-3 pt-4 border-top mt-4" style="border-color: rgba(59, 130, 246, 0.1) !important;">
                <a href="{{ route('information.edit', $information->id) }}" class="btn flex-fill fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.9rem; border-radius: 12px; padding: 12px 24px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(251, 191, 36, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                    <i class="fas fa-edit me-2"></i>
                    Edit Informasi
                </a>
                <form method="POST" action="{{ route('information.destroy', $information->id) }}" onsubmit="return confirm('Yakin ingin menghapus informasi ini?')" class="flex-fill">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn w-100 fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); font-size: 0.9rem; border-radius: 12px; padding: 12px 24px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(255, 107, 107, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                        <i class="fas fa-trash me-2"></i>
                        Hapus Informasi
                    </button>
                </form>
            </div>
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
</script>
@endsection