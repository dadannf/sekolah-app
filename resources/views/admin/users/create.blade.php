@extends('layouts.app')

@section('title', 'Daftar Admin - Dashboard Sekolah')
@section('page-title', 'Daftar Admin/Kepala Sekolah')
@section('page-subtitle', 'Buat akun khusus untuk pengelola sistem')

@section('content')
<style>
    .password-wrapper {
        position: relative;
    }
    .password-toggle {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        user-select: none;
    }
</style>

<div class="mb-4">
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
            <strong>Periksa input:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h4 class="fw-bold mb-1">Form Pendaftaran</h4>
                    <div class="text-muted small">Akun ini bisa login pakai email + password (bcrypt).</div>
                </div>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nama lengkap" required>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@sekolah.sch.id" required>
                        <div class="form-text">Untuk siswa tetap login memakai NIS, bukan email.</div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih role</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="kepala_sekolah" {{ old('role') === 'kepala_sekolah' ? 'selected' : '' }}>Kepala Sekolah</option>
                        </select>
                        <div class="form-text">Kepala Sekolah memiliki akses setara Admin.</div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="password-wrapper">
                            <input id="password-input" type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                            <i class="fas fa-eye password-toggle" data-toggle-for="password-input" title="Show password"></i>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                        <div class="password-wrapper">
                            <input id="password-confirmation-input" type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                            <i class="fas fa-eye password-toggle" data-toggle-for="password-confirmation-input" title="Show password"></i>
                        </div>
                    </div>

                    <div class="col-12 mt-2 d-grid gap-2 d-sm-flex">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-user-plus me-2"></i>
                            Buat Akun
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.password-toggle').forEach((toggle) => {
        toggle.addEventListener('click', function () {
            const inputId = this.getAttribute('data-toggle-for');
            const input = document.getElementById(inputId);
            if (!input) return;

            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            this.setAttribute('title', type === 'password' ? 'Show password' : 'Hide password');
        });
    });
</script>
@endpush
