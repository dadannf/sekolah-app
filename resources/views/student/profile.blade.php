@extends('layouts.student')

@section('title', 'Data Pribadi - Dashboard Siswa')
@section('page-title', 'Data Pribadi')
@section('page-subtitle', 'Kelola informasi pribadi Anda')

@section('content')
<div class="container-fluid">
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
        <strong>Terdapat kesalahan:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Profile Form Card -->
    <form action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-3 mb-md-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
            <!-- Card Header -->
            <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                <h5 class="fw-bold text-white mb-0" style="font-size: clamp(0.95rem, 3vw, 1.125rem);">
                    <i class="fas fa-user-edit me-2"></i>Informasi Pribadi
                </h5>
            </div>
            
            <!-- Card Body -->
            <div class="p-3 p-md-4">
                <!-- Photo Upload -->
                <div class="row mb-3 mb-md-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem;">Foto Profil</label>
                        <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                            @if($student->photo_path)
                                <img src="{{ asset('storage/' . $student->photo_path) }}" alt="Foto Siswa" id="preview-image" class="rounded-circle" style="width: clamp(80px, 20vw, 100px); height: clamp(80px, 20vw, 100px); object-fit: cover; border: 3px solid #3b82f6;">
                            @else
                                <div id="preview-image" class="rounded-circle d-flex align-items-center justify-content-center" style="width: clamp(80px, 20vw, 100px); height: clamp(80px, 20vw, 100px); background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                    <i class="fas fa-user text-white" style="font-size: clamp(2rem, 5vw, 2.5rem);"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1 w-100">
                                <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/jpg,image/png">
                                <small class="text-muted" style="font-size: 0.75rem;">Format: JPG, JPEG, PNG. Maksimal 2MB</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NIS and NISN (Read Only) -->
                <div class="row mb-3">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem;">NIS <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" value="{{ $student->nis }}" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">NIS tidak dapat diubah</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem;">NISN</label>
                        <input type="text" class="form-control bg-light" value="{{ $student->nisn ?? '-' }}" disabled>
                        <small class="text-muted" style="font-size: 0.75rem;">NISN tidak dapat diubah</small>
                    </div>
                </div>

                <!-- Name -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="name" class="form-label fw-semibold" style="font-size: 0.9rem;">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $student->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Place and Date of Birth -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="place_of_birth" class="form-label fw-semibold">Tempat Lahir</label>
                        <input type="text" name="place_of_birth" id="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror" value="{{ old('place_of_birth', $student->place_of_birth) }}">
                        @error('place_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label fw-semibold">Tanggal Lahir</label>
                        @php
                            $dobValue = old('date_of_birth', $student->date_of_birth);
                            if ($dobValue) {
                                try {
                                    $dobValue = \Carbon\Carbon::parse($dobValue)->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    // ignore
                                }
                            }
                        @endphp
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ $dobValue }}">
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Gender -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="gender" class="form-label fw-semibold">Jenis Kelamin</label>
                        <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="M" {{ old('gender', $student->gender) == 'M' || old('gender', $student->gender) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="F" {{ old('gender', $student->gender) == 'F' || old('gender', $student->gender) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="national_id_number" class="form-label fw-semibold">NIK</label>
                        <input type="text" name="national_id_number" id="national_id_number" class="form-control @error('national_id_number') is-invalid @enderror" value="{{ old('national_id_number', $student->national_id_number) }}">
                        @error('national_id_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Religion / Hobby / Aspiration -->
                <div class="row mb-3">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="religion" class="form-label fw-semibold">Religion</label>
                        <input type="text" name="religion" id="religion" class="form-control @error('religion') is-invalid @enderror" value="{{ old('religion', $student->religion) }}">
                        @error('religion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="hobby" class="form-label fw-semibold">Hobby</label>
                        <input type="text" name="hobby" id="hobby" class="form-control @error('hobby') is-invalid @enderror" value="{{ old('hobby', $student->hobby) }}">
                        @error('hobby')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="aspiration" class="form-label fw-semibold">Aspiration</label>
                        <input type="text" name="aspiration" id="aspiration" class="form-control @error('aspiration') is-invalid @enderror" value="{{ old('aspiration', $student->aspiration) }}">
                        @error('aspiration')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Parents Name -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="father_name" class="form-label fw-semibold">Nama Ayah</label>
                        <input type="text" name="father_name" id="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name', $student->father_name) }}">
                        @error('father_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="mother_name" class="form-label fw-semibold">Nama Ibu</label>
                        <input type="text" name="mother_name" id="mother_name" class="form-control @error('mother_name') is-invalid @enderror" value="{{ old('mother_name', $student->mother_name) }}">
                        @error('mother_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Address -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="address" class="form-label fw-semibold">Alamat</label>
                        <textarea name="address" id="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $student->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Contact -->
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label fw-semibold">No. Telepon</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number', $student->phone_number) }}">
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        @php
            $nonEditable = [
                'id', 'user_id',
                'nis', 'nisn',
                'current_grade_level', 'major', 'student_status',
                'photo_path',
                'created_at', 'updated_at',
            ];

            $manualFields = [
                'photo',
                'nis', 'nisn',
                'name',
                'place_of_birth', 'date_of_birth',
                'gender',
                'national_id_number',
                'religion', 'hobby', 'aspiration',
                'father_name', 'mother_name',
                'address',
                'email', 'phone_number',
            ];

            $autoColumns = collect($studentColumns ?? [])
                ->filter(fn($c) => $c && !in_array($c, $nonEditable, true) && !in_array($c, $manualFields, true))
                ->values();

            $labelMap = [
                'religion' => 'Agama',
                'hobby' => 'Hobi',
                'aspiration' => 'Cita-cita',
                'family_card_number' => 'Nomor Kartu Keluarga',
                'family_card_nomor' => 'Nomor Kartu Keluarga',
                'child_order' => 'Anak Ke',
                'residence_type' => 'Jenis Tinggal',
                'transportation' => 'Transportasi',
                'participant_number' => 'Nomor Peserta',
                'is_kip_recipient' => 'Penerima KIP',
                'kip_number' => 'Nomor KIP',
                'distance_to_school' => 'Jarak ke Sekolah',
                'travel_time' => 'Waktu Tempuh',
                'siblings_count' => 'Jumlah Saudara',
                'previous_school' => 'Sekolah Sebelumnya',
                'previous_school_npsn' => 'NPSN Sekolah Sebelumnya',
                'birth_certificate_registration_no' => 'No. Registrasi Akta Lahir',
                'diploma_serial_no' => 'No. Seri Ijazah',
                'notes' => 'Catatan',
                'telephone' => 'Telepon',
                'mobile_telephone' => 'Telepon Mobile',
                'mobile_telepon' => 'Telepon Mobile',
            ];

            $tokenMap = [
                'id' => 'ID',
                'nis' => 'NIS',
                'nisn' => 'NISN',
                'npsn' => 'NPSN',
                'nik' => 'NIK',
                'rt' => 'RT',
                'rw' => 'RW',
                'kip' => 'KIP',
            ];

            $humanize = function ($column) use ($labelMap, $tokenMap) {
                if (isset($labelMap[$column])) return $labelMap[$column];

                $parts = preg_split('/_+/', (string) $column);
                $parts = array_map(function ($p) use ($tokenMap) {
                    $lower = strtolower($p);
                    if (isset($tokenMap[$lower])) return $tokenMap[$lower];
                    return ucfirst($lower);
                }, $parts);
                return trim(implode(' ', $parts));
            };

            $fieldValue = function ($column) use ($student) {
                $value = old($column, data_get($student, $column));
                if ($value === null) return null;
                return is_scalar($value) ? (string) $value : $value;
            };
        @endphp

        @if($autoColumns->count() > 0)
            <div class="bg-white rounded-4 shadow-sm overflow-hidden border mb-3 mb-md-4" style="border-color: rgba(59, 130, 246, 0.15) !important;">
                <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
                    <h5 class="fw-bold text-white mb-0" style="font-size: clamp(0.95rem, 3vw, 1.125rem);">
                        <i class="fas fa-ellipsis-h me-2"></i>Data Lain-lain (Dapat Diedit)
                    </h5>
                </div>

                <div class="p-3 p-md-4">
                    <div class="row g-3">
                        @foreach($autoColumns as $col)
                            @php
                                $label = $humanize($col);
                                $value = $fieldValue($col);

                                $isDate = str_contains($col, 'date') || str_ends_with($col, '_date');
                                $isYear = str_ends_with($col, '_year');
                                $isNotes = str_contains($col, 'notes') || str_contains($col, 'note');
                                $isBooleanLike = str_starts_with($col, 'is_') || str_ends_with($col, '_flag');
                            @endphp

                            <div class="col-12 col-md-6 col-lg-4">
                                <label for="{{ $col }}" class="form-label fw-semibold" style="font-size: 0.9rem;">{{ $label }}</label>

                                @if($isNotes)
                                    <textarea name="{{ $col }}" id="{{ $col }}" rows="2" class="form-control @error($col) is-invalid @enderror">{{ $value }}</textarea>
                                @elseif($isBooleanLike)
                                    <select name="{{ $col }}" id="{{ $col }}" class="form-select @error($col) is-invalid @enderror">
                                        <option value="">-</option>
                                        <option value="0" {{ (string) $value === '0' ? 'selected' : '' }}>Tidak</option>
                                        <option value="1" {{ (string) $value === '1' ? 'selected' : '' }}>Ya</option>
                                    </select>
                                @elseif($isDate)
                                    @php
                                        $dateValue = null;
                                        if ($value) {
                                            try {
                                                $dateValue = \Carbon\Carbon::parse($value)->format('Y-m-d');
                                            } catch (\Throwable $e) {
                                                $dateValue = null;
                                            }
                                        }
                                    @endphp
                                    <input type="date" name="{{ $col }}" id="{{ $col }}" class="form-control @error($col) is-invalid @enderror" value="{{ $dateValue }}">
                                @elseif($isYear)
                                    <input type="number" name="{{ $col }}" id="{{ $col }}" class="form-control @error($col) is-invalid @enderror" value="{{ $value }}" min="1900" max="{{ date('Y') + 1 }}">
                                @else
                                    <input type="text" name="{{ $col }}" id="{{ $col }}" class="form-control @error($col) is-invalid @enderror" value="{{ $value }}">
                                @endif

                                @error($col)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    <!-- Academic Info (Read Only) -->
    <div class="bg-white rounded-4 shadow-sm overflow-hidden border" style="border-color: rgba(59, 130, 246, 0.15) !important;">
        <div class="px-3 px-md-4 py-3" style="background: linear-gradient(135deg, #198754 0%, #157347 100%);">
            <h5 class="fw-bold text-white mb-0">
                <i class="fas fa-graduation-cap me-2"></i>Informasi Akademik
            </h5>
        </div>
        <div class="p-3 p-md-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Kelas</label>
                    <input type="text" class="form-control bg-light" value="{{ $student->current_grade_level ? 'Kelas ' . $student->current_grade_level : '-' }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Jurusan</label>
                    <input type="text" class="form-control bg-light" value="{{ $student->major ?? '-' }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status Siswa</label>
                    <input type="text" class="form-control bg-light" value="{{ $student->student_status ?? 'Aktif' }}" disabled>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong> Data akademik hanya dapat diubah oleh admin sekolah.
            </div>
        </div>
    </div>

    <!-- Submit Button (paling bawah) -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <button type="reset" class="btn btn-outline-secondary px-4">
            <i class="fas fa-undo me-2"></i>Reset
        </button>
        <button type="submit" class="btn px-4" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none;">
            <i class="fas fa-save me-2"></i>Simpan Perubahan
        </button>
    </div>

    </form>
</div>
@endsection

@section('scripts')
<script>
    // Preview image before upload
    document.getElementById('photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('preview-image');
                preview.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'rounded-circle';
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.style.border = '3px solid #3b82f6';
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection
