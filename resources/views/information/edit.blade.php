@extends('layouts.app')

@section('title', 'Edit Informasi')
@section('page-title', 'Edit Informasi')
@section('page-subtitle', 'Perbarui pengumuman atau informasi')

@section('content')
<div class="mb-4">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('information.index') }}" 
            class="btn d-inline-flex align-items-center fw-semibold shadow-sm" 
            style="background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,1) 100%); 
                   color: #4a5568; 
                   border: 2px solid #cbd5e0; 
                   border-radius: 12px; 
                   padding: 10px 20px; 
                   font-size: 0.9rem; 
                   transition: all 0.3s ease; 
                   text-decoration: none;"
            onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.2)'"
            onmouseout="this.style.borderColor='#cbd5e0'; this.style.color='#4a5568'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)'">
            <i class="fas fa-arrow-left me-2" style="font-size: 1rem;"></i>
            Kembali
        </a>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-4 overflow-hidden" style="box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid rgba(59, 130, 246, 0.1);">
        <!-- Card Header -->
        <div class="px-3 px-md-4 py-4" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); position: relative; overflow: hidden;">
            <div class="position-absolute" style="top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
            <h2 class="fs-5 fs-md-4 fw-bold text-white mb-0" style="letter-spacing: 0.5px; text-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <i class="fas fa-edit me-2" style="font-size: 1.3rem;"></i>
                Edit Informasi
            </h2>
        </div>

        <!-- Form Content -->
        <div class="p-3 p-md-4">
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-2 ms-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('information.update', $information->id) }}" enctype="multipart/form-data">>
                @csrf
                @method('PUT')

                <!-- Title -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.9rem;">
                        Judul <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $information->title) }}" 
                        class="form-control border-2" 
                        style="border-color: #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; transition: all 0.3s ease;"
                        placeholder="Contoh: Pengumuman Libur Semester"
                        required maxlength="150"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    <small class="text-muted mt-1 d-block" style="font-size: 0.8rem;">Maksimal 150 karakter</small>
                </div>

                <!-- Type Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-3" style="font-size: 0.9rem;">
                        Tipe Informasi <span class="text-danger">*</span>
                    </label>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <input type="radio" name="info_type" value="general" id="type-general" class="d-none" {{ old('info_type', $information->info_type) === 'general' ? 'checked' : '' }}>
                            <label for="type-general" class="d-flex flex-column align-items-center p-3 border-2 rounded-3 cursor-pointer transition-all" style="border-color: #e2e8f0; transition: all 0.3s ease; cursor: pointer;" onclick="selectType('general', this)">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                    <i class="fas fa-bullhorn text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">UMUM</span>
                            </label>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="radio" name="info_type" value="spp" id="type-spp" class="d-none" {{ old('info_type', $information->info_type) === 'spp' ? 'checked' : '' }}>
                            <label for="type-spp" class="d-flex flex-column align-items-center p-3 border-2 rounded-3 cursor-pointer transition-all" style="border-color: #e2e8f0; transition: all 0.3s ease; cursor: pointer;" onclick="selectType('spp', this)">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                                    <i class="fas fa-money-bill-wave text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">SPP</span>
                            </label>
                        </div>
                        <div class="col-12 col-md-4">
                            <input type="radio" name="info_type" value="academic" id="type-academic" class="d-none" {{ old('info_type', $information->info_type) === 'academic' ? 'checked' : '' }}>
                            <label for="type-academic" class="d-flex flex-column align-items-center p-3 border-2 rounded-3 cursor-pointer transition-all" style="border-color: #e2e8f0; transition: all 0.3s ease; cursor: pointer;" onclick="selectType('academic', this)">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                                    <i class="fas fa-graduation-cap text-white" style="font-size: 1.2rem;"></i>
                                </div>
                                <span class="fw-bold text-dark" style="font-size: 0.85rem;">AKADEMIK</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.9rem;">
                        Isi Informasi <span class="text-danger">*</span>
                    </label>
                    <textarea name="body" rows="6" 
                        class="form-control border-2" 
                        style="border-color: #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; transition: all 0.3s ease; resize: vertical;"
                        placeholder="Tulis isi informasi/pengumuman di sini..."
                        required
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">{{ old('body', $information->body) }}</textarea>
                </div>

                <!-- Image Upload -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.9rem;">
                        Gambar (Opsional)
                    </label>
                    <div class="p-4 rounded-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, rgba(29, 78, 216, 0.03) 100%); border: 2px dashed rgba(59, 130, 246, 0.2);">
                        
                        @if($information->image_path)
                        <!-- Current Image -->
                        <div class="mb-3">
                            <p class="fw-semibold text-dark mb-2" style="font-size: 0.85rem;">Gambar saat ini:</p>
                            <div class="position-relative d-inline-block">
                                <img src="{{ $information->image_url }}" alt="Current image" class="rounded-3 shadow-sm" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                                <div class="mt-2">
                                    <label class="form-check-label d-flex align-items-center" style="font-size: 0.85rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_image" value="1" class="form-check-input me-2" onchange="toggleImageRemoval(this)">
                                        <i class="fas fa-trash text-danger me-1"></i>
                                        Hapus gambar ini
                                    </label>
                                </div>
                            </div>
                        </div>
                        <hr style="border-color: rgba(59, 130, 246, 0.2);">
                        @endif

                        <div class="text-center mb-3">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3b82f6; opacity: 0.7;"></i>
                            <p class="text-muted mb-2" style="font-size: 0.9rem;">
                                @if($information->image_path)
                                    Pilih gambar baru untuk mengganti yang lama
                                @else
                                    Pilih gambar untuk melengkapi informasi
                                @endif
                            </p>
                        </div>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                            class="form-control border-2" 
                            style="border-color: #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem;"
                            onchange="previewImage(this)"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#e2e8f0'">
                        <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">
                            <i class="fas fa-info-circle me-1"></i>
                            Format: JPG, JPEG, PNG, GIF, WEBP | Maksimal: 5MB
                        </small>
                        
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <p class="fw-semibold text-dark mb-2" style="font-size: 0.85rem;">Preview gambar baru:</p>
                            <div class="position-relative d-inline-block">
                                <img id="previewImg" src="" alt="Preview" class="rounded-3 shadow-sm" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                                <button type="button" class="btn btn-sm position-absolute top-0 end-0 m-1" 
                                    style="background: rgba(220, 53, 69, 0.9); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; padding: 0; font-size: 0.7rem;"
                                    onclick="removeImagePreview()" title="Hapus preview">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Section -->
                <div class="mb-4 p-4 rounded-3" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(29, 78, 216, 0.05) 100%); border: 2px dashed rgba(59, 130, 246, 0.2);">
                    <h3 class="fs-6 fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="fas fa-bullseye me-2" style="color: #3b82f6;"></i>
                        Pengaturan Target
                    </h3>
                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold text-dark mb-2" style="font-size: 0.85rem;">Target Kelas (Opsional)</label>
                            <input type="text" name="target_class" value="{{ old('target_class', $information->target_class) }}" 
                                class="form-control border-2" 
                                style="border-color: #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem;"
                                placeholder="Contoh: 10, XI RPL 1"
                                onfocus="this.style.borderColor='#3b82f6'"
                                onblur="this.style.borderColor='#e2e8f0'">
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Kosongkan untuk semua kelas</small>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold text-dark mb-2" style="font-size: 0.85rem;">Target Siswa Spesifik (Opsional)</label>
                            <select name="target_student_id" 
                                class="form-select border-2" 
                                style="border-color: #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 0.85rem;"
                                onfocus="this.style.borderColor='#3b82f6'"
                                onblur="this.style.borderColor='#e2e8f0'">
                                <option value="">-- Semua Siswa --</option>
                                @foreach($students as $student)
                                <option value="{{ $student->id }}" 
                                    {{ old('target_student_id', $information->target_student_id) == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} ({{ $student->nis }})
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Pilih untuk info personal</small>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.9rem;">Tanggal Mulai (Opsional)</label>
                        <input type="datetime-local" name="start_at" 
                            value="{{ old('start_at', $information->start_at ? $information->start_at->format('Y-m-d\TH:i') : '') }}" 
                            class="form-control border-2" 
                            style="border-color: #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 0.9rem;"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#e2e8f0'">
                        <small class="text-muted mt-1 d-block" style="font-size: 0.8rem;">Kosongkan untuk langsung aktif</small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.9rem;">Tanggal Berakhir (Opsional)</label>
                        <input type="datetime-local" name="end_at" 
                            value="{{ old('end_at', $information->end_at ? $information->end_at->format('Y-m-d\TH:i') : '') }}" 
                            class="form-control border-2" 
                            style="border-color: #e2e8f0; border-radius: 10px; padding: 12px 16px; font-size: 0.9rem;"
                            onfocus="this.style.borderColor='#3b82f6'"
                            onblur="this.style.borderColor='#e2e8f0'">
                        <small class="text-muted mt-1 d-block" style="font-size: 0.8rem;">Kosongkan untuk tidak kadaluwarsa</small>
                    </div>
                </div>

                <!-- Active Status -->
                <div class="mb-4 p-3 rounded-3" style="background: rgba(59, 130, 246, 0.05);">
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" 
                                {{ old('is_active', $information->is_active) == '1' ? 'checked' : '' }}
                                style="width: 48px; height: 24px; cursor: pointer;">
                        </div>
                        <label class="form-check-label fw-semibold text-dark" for="is_active" style="font-size: 0.9rem; cursor: pointer;">
                            Aktifkan Informasi
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-3 pt-4 border-top" style="border-color: rgba(59, 130, 246, 0.1) !important;">
                    <button type="submit" class="btn flex-fill fw-semibold text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-size: 0.9rem; border-radius: 12px; padding: 12px 24px; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(251, 191, 36, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'">
                        <i class="fas fa-save me-2"></i>
                        Update Informasi
                    </button>
                    <a href="{{ route('information.index') }}" class="btn flex-fill fw-semibold border-0 shadow-sm" style="background: #e2e8f0; color: #4a5568; font-size: 0.9rem; border-radius: 12px; padding: 12px 24px; transition: all 0.3s ease; text-decoration: none;" onmouseover="this.style.background='#cbd5e0'" onmouseout="this.style.background='#e2e8f0'">
                        <i class="fas fa-times me-2"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectType(type, element) {
    // Remove active class from all labels
    document.querySelectorAll('label[for^="type-"]').forEach(label => {
        label.style.borderColor = '#e2e8f0';
        label.style.background = 'transparent';
    });
    
    // Add active class to selected label
    element.style.borderColor = '#3b82f6';
    element.style.background = 'rgba(59, 130, 246, 0.05)';
    
    // Check the radio button
    document.getElementById('type-' + type).checked = true;
}

function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak didukung. Gunakan: JPG, JPEG, PNG, GIF, atau WEBP');
            input.value = '';
            return;
        }
        
        // Validate file size (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Ukuran file terlalu besar. Maksimal 5MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.onerror = function() {
            alert('Gagal membaca file. Pastikan file tidak corrupt.');
            input.value = '';
        }
        
        reader.readAsDataURL(file);
        
        // Uncheck remove image if new image is selected
        const removeCheckbox = document.querySelector('input[name="remove_image"]');
        if (removeCheckbox) {
            removeCheckbox.checked = false;
        }
    }
}

function removeImagePreview() {
    const input = document.getElementById('image');
    const preview = document.getElementById('imagePreview');
    
    input.value = '';
    preview.style.display = 'none';
}

function toggleImageRemoval(checkbox) {
    if (checkbox.checked) {
        // Clear any new image selection
        const input = document.getElementById('image');
        const preview = document.getElementById('imagePreview');
        
        input.value = '';
        preview.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedRadio = document.querySelector('input[name="info_type"]:checked');
    if (checkedRadio) {
        const label = document.querySelector('label[for="' + checkedRadio.id + '"]');
        if (label) {
            selectType(checkedRadio.value, label);
        }
    }
});
</script>
@endsection
