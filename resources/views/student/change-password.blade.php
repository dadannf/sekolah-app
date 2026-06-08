@extends('layouts.student')

@section('title', 'Ganti Password - Dashboard Siswa')
@section('page-title', 'Ganti Password')
@section('page-subtitle', 'Perbarui password akun Anda')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Berhasil!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
            <h5 class="fw-bold text-white mb-0" style="font-size: clamp(0.95rem, 3vw, 1.125rem);">
                <i class="fas fa-key me-2"></i>Form Ganti Password
            </h5>
        </div>

        <div class="p-3 p-md-4">
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                Gunakan password yang kuat (minimal 8 karakter) dan jangan bagikan kepada siapa pun.
            </div>

            <form action="{{ route('student.password.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12">
                        <label for="current_password" class="form-label fw-semibold">Password Saat Ini</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-toggle-password="current_password" aria-label="Lihat/sembunyikan password saat ini">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label fw-semibold">Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password" aria-label="Lihat/sembunyikan password baru">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted" style="font-size: 0.8rem;">Minimal 8 karakter.</small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password_confirmation" aria-label="Lihat/sembunyikan konfirmasi password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('student.profile') }}" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                    <button type="submit" class="btn px-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none;">
                        <i class="fas fa-save me-2"></i>Simpan Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('[data-toggle-password]');

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-toggle-password');
                const input = document.getElementById(targetId);
                if (!input) return;

                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');

                const icon = btn.querySelector('i');
                if (icon) {
                    if (isPassword) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
        });
    });
</script>
@endsection
