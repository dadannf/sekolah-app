<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\StudentExcelImporter;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    public function index()
    {
        // ===== STATISTIK SISWA PER JURUSAN (Real-time dari DB) =====
        $siswaPerJurusan = DB::table('students')
            ->selectRaw('major, COUNT(*) as count')
            ->whereNotNull('major')
            ->groupBy('major')
            ->pluck('count', 'major')
            ->toArray();
        
        // 3 Jurusan utama
        $jurusanPemasaran = $siswaPerJurusan['Pemasaran'] ?? 0;
        $jurusanTKJ = $siswaPerJurusan['Teknik Komputer dan Jaringan'] ?? 0;
        $jurusanTBSM = $siswaPerJurusan['Teknik Bisnis Sepeda Motor'] ?? 0;
        
        // Total siswa (untuk referensi)
        $totalSiswa = DB::table('students')->count();

        // ===== STATISTIK KEUANGAN REAL-TIME =====
        
        // Total tagihan keseluruhan dari invoices
        $totalTagihan = DB::table('spp_invoices')
            ->sum('amount_due');
            
        // Total yang sudah terbayar (status verified)
        $totalTerbayar = DB::table('payments')
            ->where('status', 'verified')
            ->sum('amount');
            
        // Total tunggakan (total tagihan - total terbayar)
        $totalTunggakan = $totalTagihan - $totalTerbayar;
        
        // Persentase pembayaran
        $persentasePembayaran = $totalTagihan > 0 ? round(($totalTerbayar / $totalTagihan) * 100, 2) : 0;
        
        // Detail keuangan tambahan
        $invoiceLunas = DB::table('spp_invoices')
            ->where('status', 'paid')
            ->count();
            
        $invoiceBelumLunas = DB::table('spp_invoices')
            ->where('status', 'unpaid')
            ->count();

        // Hitung pembayaran yang benar-benar masih menunggu verifikasi:
        // hanya ambil pembayaran TERBARU per invoice, lalu filter status pending/submitted.
        $latestPaymentPerInvoice = DB::table('payments')
            ->selectRaw('MAX(id) as id')
            ->groupBy('invoice_id');

        $pembayaranPending = DB::table('payments as p')
            ->joinSub($latestPaymentPerInvoice, 'lp', function ($join) {
                $join->on('lp.id', '=', 'p.id');
            })
            // Konsisten dengan tampilan Keuangan yang memakai status 'pending'.
            // Tetap dukung status lama 'submitted' bila masih ada di DB.
            ->whereIn('p.status', ['pending', 'submitted'])
            ->count();
        
        // Pembayaran per bulan untuk chart (tahun ini)
        $currentYear = date('Y');
        $pembayaranPerBulan = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->where('si.invoice_year', $currentYear)
            ->where('p.status', 'verified')
            ->selectRaw('si.invoice_month, SUM(p.amount) as total')
            ->groupBy('si.invoice_month')
            ->orderBy('si.invoice_month')
            ->pluck('total', 'invoice_month')
            ->toArray();
        
        // Siswa per kelas untuk chart
        $siswaPerKelas = DB::table('students')
            ->selectRaw('current_grade_level, COUNT(*) as count')
            ->whereNotNull('current_grade_level')
            ->groupBy('current_grade_level')
            ->orderBy('current_grade_level')
            ->pluck('count', 'current_grade_level')
            ->toArray();

        // ===== INFORMASI SEKOLAH (Real-time dari tabel information) =====
        $informasiTerbaru = DB::table('information')
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function($info) {
                if ($info->image_path) {
                    $info->image_url = asset('storage/' . $info->image_path);
                }
                return $info;
            });

        // Data sekolah statis
        $informasiSekolah = [
            'nama' => config('app.school_name', 'SMA Negeri 1 Kota'),
            'npsn' => config('app.school_npsn', '12345678'),
            'alamat' => config('app.school_address', 'Jl. Pendidikan No. 123, Kota'),
            'telepon' => config('app.school_phone', '(021) 1234567'),
            'email' => config('app.school_email', 'info@sman1kota.sch.id'),
            'kepala_sekolah' => config('app.school_principal', 'Dr. H. Abdul Rahman, M.Pd'),
            'akreditasi' => config('app.school_accreditation', 'A'),
            'tahun_berdiri' => config('app.school_founded', '1985'),
            'status' => config('app.school_status', 'Negeri')
        ];

        // ===== AKTIVITAS TERAKHIR =====
        $recentActivities = [];
        
        // Siswa baru (7 hari terakhir)
        $siswaBaru = DB::table('students')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        if ($siswaBaru > 0) {
            $recentActivities[] = [
                'icon' => 'fas fa-user-plus',
                'color' => 'primary',
                'title' => 'Siswa Baru Terdaftar',
                'description' => $siswaBaru . ' siswa baru dalam 7 hari terakhir',
                'time' => 'Minggu ini'
            ];
        }
        
        // Pembayaran verified hari ini
        $pembayaranHariIni = DB::table('payments')
            ->whereDate('verified_at', Carbon::today())
            ->where('status', 'verified')
            ->count();
        if ($pembayaranHariIni > 0) {
            $recentActivities[] = [
                'icon' => 'fas fa-money-check',
                'color' => 'success',
                'title' => 'Pembayaran Terverifikasi',
                'description' => $pembayaranHariIni . ' pembayaran telah diverifikasi hari ini',
                'time' => 'Hari ini'
            ];
        }
        
        // Info/pengumuman baru (3 hari terakhir)
        $infoBaru = DB::table('information')
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->count();
        if ($infoBaru > 0) {
            $recentActivities[] = [
                'icon' => 'fas fa-bullhorn',
                'color' => 'info',
                'title' => 'Informasi Baru',
                'description' => $infoBaru . ' informasi/pengumuman baru ditambahkan',
                'time' => '3 hari terakhir'
            ];
        }

        return view('dashboard.index', compact(
            'jurusanPemasaran',
            'jurusanTKJ',
            'jurusanTBSM',
            'totalSiswa',
            'totalTagihan',
            'totalTerbayar',
            'totalTunggakan',
            'persentasePembayaran',
            'invoiceLunas',
            'invoiceBelumLunas',
            'pembayaranPending',
            'pembayaranPerBulan',
            'siswaPerKelas',
            'informasiSekolah',
            'informasiTerbaru',
            'recentActivities'
        ));
    }

    public function pendingVerifications()
    {
        $latestPaymentPerInvoice = DB::table('payments')
            ->selectRaw('MAX(id) as id')
            ->groupBy('invoice_id');

        $pendingPayments = DB::table('payments as p')
            ->joinSub($latestPaymentPerInvoice, 'lp', function ($join) {
                $join->on('lp.id', '=', 'p.id');
            })
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->whereIn('p.status', ['pending', 'submitted'])
            ->orderByDesc('p.id')
            ->select([
                'p.id as payment_id',
                'p.amount',
                'p.method',
                'p.status',
                'p.paid_at',
                'p.created_at as payment_created_at',
                'si.invoice_year',
                'si.invoice_month',
                'si.invoice_type',
                'si.invoice_subtype',
                's.id as student_id',
                's.nis',
                's.name as student_name',
                's.current_grade_level',
                's.major',
            ])
            ->paginate(20);

        return view('dashboard.keuangan-pending', [
            'pendingPayments' => $pendingPayments,
        ]);
    }

    public function siswa(Request $request)
    {
        $sort = $request->input('sort', 'az');
        $sortDirection = $sort === 'za' ? 'desc' : 'asc';

        // Ambil data siswa dari database dengan pagination
        $siswa = Student::with('user')
            ->orderBy('name', $sortDirection)
            ->orderBy('nis', 'asc')
            ->paginate(15);

        // Ambil semua kolom tabel students untuk ditampilkan di modal detail
        $studentColumns = Schema::getColumnListing('students');

        // Hitung statistik siswa
        $totalSiswa = Student::count();
        $totalAktif = Student::where('student_status', 'active')->count();
        $totalLulus = Student::where('student_status', 'graduated')->count();

        // Hitung distribusi siswa per jurusan
        $majorDistribution = Student::selectRaw('major, COUNT(*) as count')
            ->whereNotNull('major')
            ->groupBy('major')
            ->get()
            ->pluck('count', 'major')
            ->toArray();

        // Definisi 3 jurusan utama
        $majors = [
            'Pemasaran' => $majorDistribution['Pemasaran'] ?? 0,
            'Teknik Komputer dan Jaringan' => $majorDistribution['Teknik Komputer dan Jaringan'] ?? 0,
            'Teknik Bisnis Sepeda Motor' => $majorDistribution['Teknik Bisnis Sepeda Motor'] ?? 0,
        ];

        // Hitung distribusi kelas per jurusan
        $gradeDistribution = Student::selectRaw('major, current_grade_level, COUNT(*) as count')
            ->whereNotNull('major')
            ->whereNotNull('current_grade_level')
            ->groupBy('major', 'current_grade_level')
            ->get();

        // Reorganize data untuk setiap jurusan
        $gradesByMajor = [
            'Pemasaran' => ['10' => 0, '11' => 0, '12' => 0],
            'Teknik Komputer dan Jaringan' => ['10' => 0, '11' => 0, '12' => 0],
            'Teknik Bisnis Sepeda Motor' => ['10' => 0, '11' => 0, '12' => 0],
        ];

        foreach ($gradeDistribution as $item) {
            if (isset($gradesByMajor[$item->major][$item->current_grade_level])) {
                $gradesByMajor[$item->major][$item->current_grade_level] = $item->count;
            }
        }

        return view('dashboard.siswa', compact('siswa', 'majors', 'gradesByMajor', 'totalSiswa', 'totalAktif', 'totalLulus', 'studentColumns', 'sort'));
    }

    public function importSiswaExcel(Request $request, StudentExcelImporter $importer)
    {
        $validated = $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        /** @var \Illuminate\Http\UploadedFile|null $file */
        $file = $request->file('excel');

        if (!$file || !$file->isValid()) {
            return redirect()->route('dashboard.siswa')->with('error', 'File upload tidak valid. Coba upload ulang.');
        }

        try {
            Storage::disk('local')->makeDirectory('import');

            $ext = trim((string) $file->getClientOriginalExtension());
            if ($ext === '') {
                $ext = trim((string) $file->extension());
            }
            if ($ext === '') {
                $ext = 'xlsx';
            }

            $fileName = 'students_import_' . now()->format('Ymd_His') . '_' . uniqid() . '.' . $ext;

            $storedPath = null;
            try {
                $storedPath = $file->storeAs('import', $fileName, 'local');
            } catch (\ValueError $e) {
                // Some Windows/PHP setups can yield empty realpath during upload; fallback to manual put.
                $tmpPath = (string) $file->getPathname();
                if (trim($tmpPath) === '' || !is_file($tmpPath) || !is_readable($tmpPath)) {
                    throw new \RuntimeException('Upload file temporary path tidak tersedia. Cek konfigurasi PHP upload_tmp_dir dan permission folder temp.');
                }

                $contents = @file_get_contents($tmpPath);
                if ($contents === false) {
                    throw new \RuntimeException('Gagal membaca file upload sementara. Coba upload ulang.');
                }

                $manualPath = 'import/' . $fileName;
                Storage::disk('local')->put($manualPath, $contents);
                $storedPath = $manualPath;
            }

            if (!$storedPath || !is_string($storedPath)) {
                return redirect()->route('dashboard.siswa')->with('error', 'Gagal menyimpan file upload.');
            }

            $fullPath = Storage::disk('local')->path($storedPath);
            if (!is_string($fullPath) || trim($fullPath) === '' || !is_file($fullPath) || !is_readable($fullPath)) {
                return redirect()->route('dashboard.siswa')->with('error', 'File upload tidak ditemukan setelah disimpan.');
            }

            $result = $importer->import($fullPath, [
                'dry_run' => false,
                'default_grade_level' => 10,
                'default_student_status' => 'active',
            ]);

            $message = 'Import selesai. Created: ' . $result['created']
                . ', Updated: ' . $result['updated']
                . ', Skipped: ' . $result['skipped']
                . ', Errors: ' . $result['errors']
                . '.';

            if ($result['errors'] > 0 && !empty($result['error_messages'])) {
                $message .= ' Contoh error: ' . implode(' | ', array_slice($result['error_messages'], 0, 3));
                return redirect()->route('dashboard.siswa')->with('error', $message);
            }

            return redirect()->route('dashboard.siswa')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard.siswa')->with('error', 'Gagal import Excel: ' . $e->getMessage());
        }
    }

    public function templateSiswaExcel()
    {
        $headers = [
            'id',
            'user_id',
            'nis',
            'nisn',
            'nik',
            'name',
            'gender',
            'place_of_birth',
            'date_of_birth',
            'religion',
            'email',
            'phone_number',
            'address',
            'address_rt',
            'address_rw',
            'address_kelurahan',
            'address_kecamatan',
            'address_postal_code',
            'father_name',
            'father_birth_year',
            'mother_name',
            'mother_birth_year',
            'current_grade_level',
            'major',
            'previous_school',
            'uniform_size',
            'student_status',
            'photo_path',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        foreach ($headers as $i => $header) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);

        // Data validation for common fields (rows 2..5000)
        $maxRow = 5000;

        // gender column
        $dvJk = new DataValidation();
        $dvJk->setType(DataValidation::TYPE_LIST);
        $dvJk->setErrorStyle(DataValidation::STYLE_STOP);
        $dvJk->setAllowBlank(true);
        $dvJk->setShowInputMessage(true);
        $dvJk->setShowErrorMessage(true);
        $dvJk->setShowDropDown(true);
        $dvJk->setFormula1('\"L,P,M,F\"');
        $dvJk->setPromptTitle('gender');
        $dvJk->setPrompt('Isi L/P (atau M/F). Import akan mapping L->M, P->F');
        $dvJk->setErrorTitle('gender tidak valid');
        $dvJk->setError('Gunakan: L, P, M, atau F');

        // current_grade_level column
        $dvKelas = new DataValidation();
        $dvKelas->setType(DataValidation::TYPE_LIST);
        $dvKelas->setErrorStyle(DataValidation::STYLE_STOP);
        $dvKelas->setAllowBlank(true);
        $dvKelas->setShowInputMessage(true);
        $dvKelas->setShowErrorMessage(true);
        $dvKelas->setShowDropDown(true);
        $dvKelas->setFormula1('"10,11,12"');
        $dvKelas->setPromptTitle('current_grade_level');
        $dvKelas->setPrompt('Isi 10/11/12. Jika kosong, default 10');
        $dvKelas->setErrorTitle('current_grade_level tidak valid');
        $dvKelas->setError('Gunakan: 10, 11, atau 12');

        // student_status column
        $dvStatus = new DataValidation();
        $dvStatus->setType(DataValidation::TYPE_LIST);
        $dvStatus->setErrorStyle(DataValidation::STYLE_STOP);
        $dvStatus->setAllowBlank(true);
        $dvStatus->setShowInputMessage(true);
        $dvStatus->setShowErrorMessage(true);
        $dvStatus->setShowDropDown(true);
        $dvStatus->setFormula1('"active,inactive,graduated"');
        $dvStatus->setPromptTitle('student_status');
        $dvStatus->setPrompt('Isi active/inactive/graduated. Jika kosong, default active');
        $dvStatus->setErrorTitle('student_status tidak valid');
        $dvStatus->setError('Gunakan: active, inactive, atau graduated');

        // apply validation by header position
        $genderCol = array_search('gender', $headers, true);
        $gradeCol = array_search('current_grade_level', $headers, true);
        $statusCol = array_search('student_status', $headers, true);

        for ($r = 2; $r <= $maxRow; $r++) {
            if ($genderCol !== false) {
                $sheet->getCell(Coordinate::stringFromColumnIndex($genderCol + 1) . $r)->setDataValidation(clone $dvJk);
            }
            if ($gradeCol !== false) {
                $sheet->getCell(Coordinate::stringFromColumnIndex($gradeCol + 1) . $r)->setDataValidation(clone $dvKelas);
            }
            if ($statusCol !== false) {
                $sheet->getCell(Coordinate::stringFromColumnIndex($statusCol + 1) . $r)->setDataValidation(clone $dvStatus);
            }
        }

        $fileName = 'Template_Import_Siswa_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function storeSiswa(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:20|unique:students,nis',
            'nisn' => 'required|string|max:32|unique:students,nisn',
            'name' => 'required|string|max:100',
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'current_grade_level' => 'required|integer|in:10,11,12',
            'major' => 'nullable|string|max:100',
            'national_id_number' => 'nullable|string|max:16',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100|unique:students,email',
            'phone_number' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'student_status' => 'required|in:active,inactive,graduated',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . $validated['nis'] . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('storage/students'), $photoName);
            $validated['photo_path'] = 'students/' . $photoName;
        }

        // Create user account for student
        // Email = NIS, Password = bba# + 4 digit terakhir NIS
        $last4Digits = substr($validated['nis'], -4);
        $defaultPassword = 'bba#' . $last4Digits;
        
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['nis'], // NIS sebagai username/email
            'password' => md5($defaultPassword), // Password: bba#XXXX (MD5)
            'role' => 'siswa',
        ]);

        $validated['user_id'] = $user->id;

        $student = Student::create($validated);

        // Otomatis buat SPP invoices untuk siswa baru
        $this->createSppInvoicesForStudent($student);

        return redirect()->route('dashboard.siswa')->with('success', 'Data siswa berhasil ditambahkan!');
    }

    public function checkSiswa(Request $request)
    {
        $field = $request->input('field'); // 'nis' or 'nisn'
        $value = $request->input('value');
        
        if (!in_array($field, ['nis', 'nisn'])) {
            return response()->json(['exists' => false]);
        }
        
        $exists = Student::where($field, $value)->exists();
        
        return response()->json(['exists' => $exists]);
    }

    public function updateSiswa(Request $request, $id)
    {
        try {
            $student = Student::findOrFail($id);
            
            $validated = $request->validate([
                'nis' => 'required|string|max:32|unique:students,nis,' . $id,
                'nisn' => 'required|string|max:32|unique:students,nisn,' . $id,
                'name' => 'required|string|max:100',
                'place_of_birth' => 'nullable|string|max:100',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:M,F',
                'current_grade_level' => 'required|integer|in:10,11,12',
                'major' => 'nullable|string|max:100',
                'national_id_number' => 'nullable|string|max:16',
                'father_name' => 'nullable|string|max:100',
                'mother_name' => 'nullable|string|max:100',
                'email' => 'nullable|email|max:100|unique:students,email,' . $id,
                'phone_number' => 'nullable|string|max:30',
                'address' => 'nullable|string|max:255',
                'student_status' => 'required|in:active,inactive,graduated',
                'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($student->photo_path && file_exists(public_path('storage/' . $student->photo_path))) {
                    unlink(public_path('storage/' . $student->photo_path));
                }
                
                $photo = $request->file('photo');
                $photoName = time() . '_' . $validated['nis'] . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('storage/students'), $photoName);
                $validated['photo_path'] = 'students/' . $photoName;
            }

            // Update student
            $student->update($validated);

            // Update user email and password if NIS changed
            if ($student->user) {
                $updateData = [
                    'name' => $validated['name'],
                    'email' => $validated['nis'], // NIS sebagai username
                ];
                
                // If NIS changed, update password too
                if ($student->nis !== $validated['nis']) {
                    $last4Digits = substr($validated['nis'], -4);
                    $defaultPassword = 'bba#' . $last4Digits;
                    $updateData['password'] = md5($defaultPassword);
                }
                
                $student->user->update($updateData);
            }

            return redirect()->route('dashboard.siswa')->with('success', 'Data siswa berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard.siswa')->with('error', 'Gagal memperbarui data siswa: ' . $e->getMessage());
        }
    }

    public function deleteSiswa($id)
    {
        try {
            $student = Student::findOrFail($id);
            $studentName = $student->name;

            DB::transaction(function () use ($student) {
                // Delete student - StudentObserver akan menangani cascade deletion
                // dari payments, spp_invoices, enrollments, dan related records lainnya
                $student->delete();
            });

            return redirect()->route('dashboard.siswa')->with('success', "Data siswa {$studentName} berhasil dihapus beserta data keuangan dan data terkait lainnya.");
        } catch (\Throwable $e) {
            \Log::error('[DashboardController.deleteSiswa] Error deleting student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard.siswa')->with('error', 'Gagal menghapus data siswa: ' . $e->getMessage());
        }
    }

    public function bulkDeleteSiswa(Request $request)
    {
        $selectAllStudents = $request->input('select_all_students') === 'true';

        try {
            if ($selectAllStudents) {
                // Delete ALL students when select_all flag is set
                $students = Student::all();
                $totalCount = Student::count();
                
                if ($totalCount === 0) {
                    return redirect()->route('dashboard.siswa')->with('warning', 'Tidak ada siswa yang dapat dihapus.');
                }
            } else {
                // Delete specific selected students
                $validated = $request->validate([
                    'student_ids' => ['required', 'array', 'min:1'],
                    'student_ids.*' => ['integer', 'exists:students,id'],
                ]);
                $students = Student::whereIn('id', $validated['student_ids'])->get();
            }

            $deletedCount = 0;

            DB::transaction(function () use ($students, &$deletedCount) {
                foreach ($students as $student) {
                    $student->delete();
                    $deletedCount++;
                }
            });

            return redirect()->route('dashboard.siswa')->with(
                'success',
                $deletedCount > 0
                    ? "{$deletedCount} data siswa berhasil dihapus beserta data keuangan dan data terkait lainnya."
                    : 'Tidak ada data siswa yang berhasil dihapus.'
            );
        } catch (\Throwable $e) {
            \Log::error('[DashboardController.bulkDeleteSiswa] Error deleting students', [
                'select_all' => $selectAllStudents,
                'student_ids' => $request->input('student_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('dashboard.siswa')->with('error', 'Gagal menghapus data siswa: ' . $e->getMessage());
        }
    }

    public function keuangan(Request $request)
    {
        // Auto-generate invoices untuk siswa yang belum punya
        $this->autoGenerateMissingInvoices();

        // Get filter parameters
        $yearFilter = $request->input('year', date('Y'));
        $gradeFilter = $request->input('grade_level');
        $searchFilter = $request->input('search');
        $invoiceTypeFilter = $request->input('invoice_type', 'spp'); // New filter
        $sortFilter = $request->input('sort', 'az');
        $sortDirection = $sortFilter === 'za' ? 'desc' : 'asc';

        // Query students dengan pembayaran
        $query = \App\Models\Student::query();

        // Apply grade filter
        if ($gradeFilter) {
            $query->where('current_grade_level', $gradeFilter);
        }

        // Apply search filter
        if ($searchFilter) {
            $query->where(function($q) use ($searchFilter) {
                $q->where('nis', 'like', "%{$searchFilter}%")
                  ->orWhere('name', 'like', "%{$searchFilter}%");
            });
        }

        $query->orderBy('name', $sortDirection)
            ->orderBy('nis', 'asc');

        $students = $query->get();

        // Define payment type configurations
        $invoiceTypeConfig = [
            'spp' => ['label' => 'SPP Bulanan', 'month_count' => 12, 'color' => 'primary'],
            'uniform' => ['label' => 'Seragam', 'month_count' => 1, 'month_no' => 1, 'color' => 'info'],
            'pts' => ['label' => 'PTS', 'month_count' => 1, 'month_no' => 5, 'color' => 'warning'],
            'pas' => ['label' => 'PAS', 'month_count' => 1, 'month_no' => 12, 'color' => 'danger'],
        ];

        // Calculate payment data for each student
        $payments = [];
        $totalBill = 0;
        $totalPaid = 0;

        foreach ($students as $student) {
            if (!$student->current_grade_level) continue;
            
            // Get tariff
            $tariff = DB::table('spp_tariffs')
                ->where('grade_level', $student->current_grade_level)
                ->where('is_active', 1)
                ->first();
            
            if (!$tariff) continue;
            
            // Calculate bill based on invoice type
            $studentTotalBill = 0;
            
            if ($invoiceTypeFilter === 'spp') {
                $studentTotalBill = $tariff->amount * 12;
            } elseif ($invoiceTypeFilter === 'uniform') {
                // For uniform, sum all 5 uniform types
                $studentTotalBill = ($tariff->uniform_batik_cost ?? 0) +
                                    ($tariff->uniform_olahraga_cost ?? 0) +
                                    ($tariff->uniform_muslim_cost ?? 0) +
                                    ($tariff->uniform_pramuka_cost ?? 0) +
                                    ($tariff->uniform_almamater_cost ?? 0);
            } elseif ($invoiceTypeFilter === 'pts' && $tariff->pts_cost) {
                $studentTotalBill = $tariff->pts_cost;
            } elseif ($invoiceTypeFilter === 'pas' && $tariff->pas_cost) {
                $studentTotalBill = $tariff->pas_cost;
            } else {
                continue; // Skip jika invoice type tidak ada atau tidak applicable
            }
            
            // Get total paid for this student in this year for this invoice type (only verified payments)
            $studentTotalPaid = DB::table('payments as p')
                ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                ->where('si.student_id', $student->id)
                ->where('si.invoice_year', $yearFilter)
                ->where('si.invoice_type', $invoiceTypeFilter)
                ->where('p.status', 'verified') // Only count verified payments
                ->sum('p.amount');
            
            $remaining = $studentTotalBill - $studentTotalPaid;
            $percentage = $studentTotalBill > 0 
                ? round(($studentTotalPaid / $studentTotalBill) * 100, 2) 
                : 0;
            
            $payments[] = (object) [
                'id' => $student->id,
                'student_id' => $student->id,
                'student' => $student,
                'year' => $yearFilter,
                'invoice_type' => $invoiceTypeFilter,
                'total_bill' => $studentTotalBill,
                'total_paid' => $studentTotalPaid,
                'remaining' => $remaining,
                'payment_percentage' => $percentage,
                'status' => $percentage >= 100 ? 'completed' : 'active',
            ];
            
            $totalBill += $studentTotalBill;
            $totalPaid += $studentTotalPaid;
        }

        $totalRemaining = $totalBill - $totalPaid;
        $averageProgress = $totalBill > 0 ? round(($totalPaid / $totalBill) * 100, 2) : 0;

        // Paginate the payments array (15 items per page)
        $page = Paginator::resolveCurrentPage();
        $perPage = 15;
        $payments = collect($payments)->values();
        $totalCount = $payments->count();
        $paginatedPayments = $payments->slice(($page - 1) * $perPage, $perPage)->values();
        
        $payments = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedPayments,
            $totalCount,
            $perPage,
            $page,
            [
                'path' => route('dashboard.keuangan'),
                'query' => request()->query(),
            ]
        );

        // Get available years and grades for filters
        $availableYears = range(date('Y') - 5, date('Y') + 1);
        $grades = DB::table('spp_tariffs')
            ->where('is_active', 1)
            ->pluck('grade_level')
            ->unique()
            ->sort()
            ->values();

        // Available invoice types
        $invoiceTypes = collect($invoiceTypeConfig)->map(function($config, $type) {
            return [
                'value' => $type,
                'label' => $config['label'],
            ];
        })->values();

        return view('dashboard.keuangan', compact(
            'payments',
            'totalBill',
            'totalPaid',
            'totalRemaining',
            'averageProgress',
            'availableYears',
            'grades',
            'yearFilter',
            'sortFilter',
            'invoiceTypeFilter',
            'invoiceTypes'
        ));
    }

    public function keuanganPrint(Request $request)
    {
        $scope = $request->input('scope', 'monthly');
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', 1);
        $semester = $request->input('semester', 'ganjil');

        $monthNames = [
            1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober',
            5 => 'November', 6 => 'Desember', 7 => 'Januari', 8 => 'Februari',
            9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
        ];

        // Tentukan bulan yang dicakup
        if ($scope === 'semester') {
            $selectedMonths = $semester === 'genap' ? range(7, 12) : range(1, 6);
            $subtitle = 'Semester ' . ($semester === 'genap' ? 'Genap' : 'Ganjil');
        } elseif ($scope === 'yearly') {
            $selectedMonths = range(1, 12);
            $subtitle = 'Tahunan';
        } else {
            $selectedMonths = [($month >= 1 && $month <= 12) ? $month : 1];
            $subtitle = 'Bulan ' . ($monthNames[$selectedMonths[0]] ?? '');
        }

        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->where('si.invoice_year', $year)
            ->whereIn('si.invoice_month', $selectedMonths)
            ->where('p.status', 'verified')
            ->select(
                'p.id',
                's.nis',
                's.name',
                's.current_grade_level',
                's.major',
                'p.amount',
                'p.paid_at',
                'p.method',
                'p.status',
                'p.bank_name',
                'si.invoice_month'
            )
            ->orderBy('s.name')
            ->orderByDesc('p.paid_at')
            ->get();

        return view('dashboard.keuangan-print', compact(
            'payments',
            'year',
            'scope',
            'month',
            'semester',
            'monthNames',
            'subtitle'
        ));
    }

    public function keuanganDetail($studentId, $year = null)
    {
        // Get student data
        try {
            $student = \App\Models\Student::findOrFail($studentId);
        } catch (\Exception $e) {
            abort(404, 'Siswa tidak ditemukan');
        }
        
        // Validate student has required fields
        if (!$student->id) {
            abort(400, 'Data siswa tidak valid');
        }
        
        // Determine current year (tahun ajaran)
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Tahun ajaran dimulai Juli (bulan 7)
        // Jika bulan sekarang < Juli, maka tahun ajaran adalah tahun lalu
        $defaultYear = $currentMonth < 7 ? $currentYear - 1 : $currentYear;
        
        // Use provided year or default to current academic year
        $invoiceYear = $year ?? $defaultYear;
        
        // Get tariff based on student's current grade level
        if (!$student->current_grade_level) {
            abort(400, 'Tingkat kelas siswa belum diset');
        }
        
        $tariff = DB::table('spp_tariffs')
            ->where('grade_level', $student->current_grade_level)
            ->where('is_active', 1)
            ->first();
        
        if (!$tariff) {
            abort(400, 'Tarif SPP untuk kelas ' . $student->current_grade_level . ' tidak ditemukan');
        }
        
        $sppPerBulan = $tariff->amount;
        
        // Get all invoices for this student in current year
        $invoices = DB::table('spp_invoices')
            ->where('student_id', $studentId)
            ->where('invoice_year', $invoiceYear)
            ->get()
            ->keyBy('invoice_month');
        
        // Get all payments for these invoices (include pending/rejected for display)
        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->where('si.student_id', $studentId)
            ->where('si.invoice_year', $invoiceYear)
            ->select('p.*', 'si.invoice_month', 'si.invoice_type', 'si.invoice_subtype')
            ->orderByDesc('p.id')
            ->get()
            ->groupBy('invoice_type');
        
        // ===== MONTHLY PAYMENTS (SPP) BREAKDOWN =====
        $totalPaidSpp = $payments->get('spp', collect())->where('status', 'verified')->sum('amount');
        $totalBillSpp = $sppPerBulan * 12;
        $remainingSpp = $totalBillSpp - $totalPaidSpp;
        $percentageSpp = $totalBillSpp > 0 ? round(($totalPaidSpp / $totalBillSpp) * 100, 2) : 0;
        
        // Generate monthly payments (Juli - Juni)
        $months = [
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'
        ];
        
        $monthlyPayments = [];
        foreach ($months as $index => $monthName) {
            $monthNo = $index + 1; // 1-12 untuk Juli-Juni
            $paymentList = $payments->get('spp', collect())->where('invoice_month', $monthNo)->first();

            $status = 'unpaid';
            if ($paymentList) {
                $status = $paymentList->status ?: 'pending';
            }
            
            $monthlyPayments[] = [
                'no' => $monthNo,
                'month' => $monthName,
                'amount' => $sppPerBulan,
                'method' => $paymentList ? strtoupper($paymentList->method) : null,
                'location' => $paymentList ? ($paymentList->bank_name ?? $paymentList->place_paid ?? null) : null,
                'status' => $status,
                'payment_id' => $paymentList ? $paymentList->id : null,
                'proof_path' => $paymentList ? $paymentList->proof_path : null,
                'proof' => $paymentList && $paymentList->proof_path ? 'Pratinjau' : ($paymentList ? ucfirst($status) : null),
                'proof_url' => $paymentList && $paymentList->proof_path ? route('payment.proof.show', [$paymentList->id, basename($paymentList->proof_path)]) : null,
                'paid_at' => $paymentList ? $paymentList->paid_at : null,
            ];
        }
        
        // ===== ADDITIONAL PAYMENT TYPES BREAKDOWN =====
        $additionalPayments = [];
        
        // Uniform - Group all 5 types into single card
        $uniformTypes = ['batik', 'olahraga', 'muslim', 'pramuka', 'almamater'];
        $uniformTypeDetails = [];
        $totalUniformBill = 0;
        $totalUniformPaid = 0;
        
        foreach ($uniformTypes as $type) {
            $costField = 'uniform_' . $type . '_cost';
            $uniformCost = $tariff->$costField ?? 0;
            
            if ($uniformCost > 0) {
                $uniformPayments = $payments->get('uniform', collect());
                $uniformPaymentsForType = $uniformPayments
                    ->filter(function ($p) use ($type) {
                        return strtolower(trim((string) ($p->invoice_subtype ?? ''))) === $type;
                    });

                $payment = $uniformPaymentsForType->first();
                $typePaid = $uniformPaymentsForType
                    ->where('status', 'verified')
                    ->sum('amount');
                
                $totalUniformBill += $uniformCost;
                $totalUniformPaid += $typePaid;
                
                $uniformTypeDetails[$type] = [
                    'name' => ucfirst($type),
                    'cost' => $uniformCost,
                    'paid' => $typePaid,
                    'payment' => $payment,
                ];
            }
        }
        
        // Create single Seragam card if there are uniform costs
        if ($totalUniformBill > 0) {
            $additionalPayments['seragam'] = [
                'type' => 'uniform',
                'label' => 'Seragam (' . count($uniformTypeDetails) . ' Jenis)',
                'icon' => 'fa-shirt',
                'color' => 'info',
                'total_bill' => $totalUniformBill,
                'total_paid' => $totalUniformPaid,
                'remaining' => $totalUniformBill - $totalUniformPaid,
                'percentage' => $totalUniformBill > 0 ? round(($totalUniformPaid / $totalUniformBill) * 100, 2) : 0,
                'details' => $uniformTypeDetails,
                'payment' => null, // No single payment for uniform (multiple types)
            ];
        }
        
        if ($tariff->pts_cost) {
            $ptsPaid = $payments->get('pts', collect())->where('status', 'verified')->sum('amount');
            $additionalPayments['pts'] = [
                'type' => 'pts',
                'label' => 'PTS',
                'icon' => 'fa-book-open',
                'color' => 'warning',
                'total_bill' => $tariff->pts_cost,
                'total_paid' => $ptsPaid,
                'remaining' => $tariff->pts_cost - $ptsPaid,
                'percentage' => $tariff->pts_cost > 0 ? round(($ptsPaid / $tariff->pts_cost) * 100, 2) : 0,
                'payment' => $payments->get('pts', collect())->first(),
            ];
        }
        
        if ($tariff->pas_cost) {
            $pasPaid = $payments->get('pas', collect())->where('status', 'verified')->sum('amount');
            $additionalPayments['pas'] = [
                'type' => 'pas',
                'label' => 'PAS',
                'icon' => 'fa-scroll',
                'color' => 'danger',
                'total_bill' => $tariff->pas_cost,
                'total_paid' => $pasPaid,
                'remaining' => $tariff->pas_cost - $pasPaid,
                'percentage' => $tariff->pas_cost > 0 ? round(($pasPaid / $tariff->pas_cost) * 100, 2) : 0,
                'payment' => $payments->get('pas', collect())->first(),
            ];
        }
        
        // Create payment object for compatibility - MUST NOT BE NULL
        if (!$student || !$student->id) {
            abort(400, 'Data siswa tidak valid untuk keperluan keuangan');
        }
        
        $payment = (object) [
            'id' => null, // Payment master ID
            'student_id' => $student->id, // CRITICAL: Must always be set
            'student' => $student,
            'invoice_year' => $invoiceYear,
            'total_bill' => $totalBillSpp,
            'total_paid' => $totalPaidSpp,
            'remaining' => $remainingSpp,
            'payment_percentage' => $percentageSpp,
        ];
        
        // Ensure payment object is valid before rendering
        if (!isset($payment->student_id) || !$payment->student_id) {
            abort(500, 'Gagal inisialisasi data pembayaran siswa');
        }

        return view('dashboard.keuangan-detail', compact(
            'payment', 
            'monthlyPayments', 
            'sppPerBulan',
            'additionalPayments',
            'student'
        ));
    }

    public function keuanganDetailPrint($studentId, $year = null)
    {
        // Get student data
        $student = \App\Models\Student::findOrFail($studentId);
        
        // Determine current year (tahun ajaran)
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Tahun ajaran dimulai Juli (bulan 7)
        // Jika bulan sekarang < Juli, maka tahun ajaran adalah tahun lalu
        $defaultYear = $currentMonth < 7 ? $currentYear - 1 : $currentYear;
        
        // Use provided year or default to current academic year
        $invoiceYear = $year ?? $defaultYear;
        
        // Get tariff based on student's current grade level
        if (!$student->current_grade_level) {
            abort(400, 'Tingkat kelas siswa belum diset');
        }
        
        $tariff = DB::table('spp_tariffs')
            ->where('grade_level', $student->current_grade_level)
            ->where('is_active', 1)
            ->first();
        
        if (!$tariff) {
            abort(400, 'Tarif SPP untuk kelas ' . $student->current_grade_level . ' tidak ditemukan');
        }
        
        $sppPerBulan = $tariff->amount;
        
        // Get all invoices for this student in current year
        $invoices = DB::table('spp_invoices')
            ->where('student_id', $studentId)
            ->where('invoice_year', $invoiceYear)
            ->get()
            ->keyBy('invoice_month');
        
        // Get all payments for these invoices (print: hanya yang sudah diverifikasi)
        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->where('si.student_id', $studentId)
            ->where('si.invoice_year', $invoiceYear)
            ->where('p.status', 'verified')
            ->select('p.*', 'si.invoice_month')
            ->orderByDesc('p.id')
            ->get()
            ->unique('invoice_month')
            ->keyBy('invoice_month');
        
        // Calculate totals (only verified counted)
        $totalPaid = $payments->where('status', 'verified')->sum('amount');
        $totalBill = $sppPerBulan * 12;
        $remaining = $totalBill - $totalPaid;
        $percentage = $totalBill > 0 ? round(($totalPaid / $totalBill) * 100, 2) : 0;
        
        // Generate monthly payments (Juli - Juni)
        $months = [
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'
        ];
        
        $monthlyPayments = [];
        foreach ($months as $index => $monthName) {
            $monthNo = $index + 1; // 1-12 untuk Juli-Juni
            $payment = $payments->get($monthNo);
            $invoice = $invoices->get($monthNo);

            $status = 'unpaid';
            if ($payment) {
                $status = $payment->status ?: 'pending';
            }
            
            $monthlyPayments[] = [
                'no' => $monthNo,
                'month' => $monthName,
                'amount' => $sppPerBulan,
                'method' => $payment ? strtoupper($payment->method) : null,
                'location' => $payment ? ($payment->bank_name ?? $payment->place_paid ?? null) : null,
                'status' => $status,
                'payment_id' => $payment ? $payment->id : null,
                'proof_path' => $payment ? $payment->proof_path : null,
                'proof' => $payment && $payment->proof_path ? 'Pratinjau' : ($payment ? ucfirst($status) : null),
                'proof_url' => $payment && $payment->proof_path ? route('payment.proof.show', [$payment->id, basename($payment->proof_path)]) : null,
                'paid_at' => $payment ? $payment->paid_at : null,
            ];
        }

        // Untuk print: tampilkan hanya pembayaran yang sudah verified
        $monthlyPayments = array_values(array_filter($monthlyPayments, function ($row) {
            return ($row['status'] ?? null) === 'verified';
        }));
        
        // Create payment object for compatibility
        $payment = (object) [
            'student_id' => $student->id,
            'student' => $student,
            'invoice_year' => $invoiceYear,
            'total_bill' => $totalBill,
            'total_paid' => $totalPaid,
            'remaining' => $remaining,
            'payment_percentage' => $percentage,
        ];

        return view('dashboard.keuangan-detail-print', compact('payment', 'monthlyPayments', 'sppPerBulan'));
    }

    /**
     * Print selected uniform (seragam) report for a student (admin)
     * URL example: /dashboard/keuangan/{id}/print/seragam-selected?types=batik,seragam_pramuka
     */
    public function printSelectedUniformReport($studentId, Request $request)
    {
        $typesParam = $request->query('types', '');
        $types = array_filter(array_map('trim', explode(',', $typesParam)));

        $student = Student::find($studentId);
        if (!$student) {
            abort(404, 'Siswa tidak ditemukan');
        }

        if (empty($types)) {
            // If no types provided, show all uniform payments (reuse existing print route behavior)
            // Build payments: only verified payments for uniform
            $invoiceYear = date('Y');
            $payments = DB::table('payments as p')
                ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                ->join('students as s', 'si.student_id', '=', 's.id')
                ->where('si.student_id', $student->id)
                ->where('si.invoice_type', 'uniform')
                ->where('p.status', 'verified')
                ->select('p.*', 's.name', 's.current_grade_level', 's.major', 'si.invoice_month')
                ->orderByDesc('p.paid_at')
                ->get();

            $scope = 'annual';
            $year = $invoiceYear;
            $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];

            return view('dashboard.keuangan-print', compact('payments','scope','year','monthNames'));
        }

        // Query invoices with left join payments so unpaid items still appear
        $invoiceYear = date('Y');
        $rows = DB::table('spp_invoices as si')
            ->leftJoin('payments as p', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->where('si.student_id', $student->id)
            ->where('si.invoice_type', 'uniform')
            ->where('si.invoice_year', $invoiceYear)
            ->whereIn('si.invoice_subtype', $types)
            ->select('p.*', 's.name', 's.current_grade_level', 's.major', 'si.invoice_month')
            ->orderBy('si.invoice_month', 'asc')
            ->get();

        $payments = $rows;
        $scope = 'annual';
        $year = $invoiceYear;
        $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];

        return view('dashboard.keuangan-print', compact('payments','scope','year','monthNames'));
    }

    /**
     * Print multiple selected payments as a single kwitansi (receipt) report.
     * Expects query param `ids` as CSV of payment IDs, e.g. ?ids=12,13,14
     */
    public function printSelectedPayments(Request $request)
    {
        $idsParam = $request->query('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));

        if (empty($ids)) {
            abort(400, 'Tidak ada pembayaran yang dipilih');
        }

        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->leftJoin('users as u', 'p.received_by', '=', 'u.id')
            ->whereIn('p.id', $ids)
            ->select(
                'p.*',
                'si.invoice_month',
                'si.invoice_year',
                'si.invoice_type',
                'si.invoice_subtype',
                's.id as student_id',
                's.nis',
                's.name as student_name',
                's.current_grade_level',
                's.major',
                'u.name as received_by_name'
            )
            ->orderBy('p.paid_at')
            ->get();

        if ($payments->isEmpty()) {
            abort(404, 'Pembayaran tidak ditemukan');
        }

        // Calculate totals
        $totalAmount = $payments->sum('amount');

        // Terbilang (use existing helper)
        $terbilang = $this->numberToWords($totalAmount);

        return view('dashboard.kwitansi-multi-print', compact('payments', 'totalAmount', 'terbilang'));
    }

    public function informasi()
    {
        // Data informasi sekolah
        $sekolah = [
            'nama' => 'SMA Negeri 1 Kota',
            'npsn' => '12345678',
            'alamat' => 'Jl. Pendidikan No. 123, Kota',
            'telepon' => '(021) 1234567',
            'email' => 'info@sman1kota.sch.id',
            'kepala_sekolah' => 'Dr. H. Abdul Rahman, M.Pd',
            'akreditasi' => 'A',
            'jumlah_siswa' => 850,
            'jumlah_guru' => 65,
            'jumlah_kelas' => 24,
        ];

        return view('dashboard.informasi', compact('sekolah'));
    }

    public function printReceipt($paymentId)
    {
        // Get payment data dengan relasi
        $payment = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->leftJoin('users as u', 'p.received_by', '=', 'u.id')
            ->where('p.id', $paymentId)
            ->select(
                'p.*',
                'si.invoice_month',
                'si.invoice_year',
                'si.invoice_type',
                'si.invoice_subtype',
                's.id as student_id',
                's.nis',
                's.name as student_name',
                's.current_grade_level',
                's.major',
                'u.name as received_by_name'
            )
            ->first();

        if (!$payment) {
            abort(404, 'Data pembayaran tidak ditemukan');
        }

        // Hanya bisa cetak kwitansi yang sudah verified
        if ($payment->status !== 'verified') {
            abort(403, 'Kwitansi hanya bisa dicetak untuk pembayaran yang sudah terverifikasi');
        }

        // Get month name
        $months = [
            1 => 'Juli', 2 => 'Agustus', 3 => 'September', 4 => 'Oktober',
            5 => 'November', 6 => 'Desember', 7 => 'Januari', 8 => 'Februari',
            9 => 'Maret', 10 => 'April', 11 => 'Mei', 12 => 'Juni'
        ];
        
        $monthName = $months[$payment->invoice_month] ?? '-';
        
        // Generate payment description based on invoice type
        $invoiceType = $payment->invoice_type ?? 'spp';
        $paymentDescription = match($invoiceType) {
            'spp' => 'Biaya Penyelenggaraan Pendidikan - SPP ' . $monthName . ' ' . $payment->invoice_year . '/' . ($payment->invoice_year + 1),
            'uniform' => 'Pembayaran Seragam Sekolah' . ($payment->invoice_subtype ? ' (' . $payment->invoice_subtype . ')' : ''),
            'pts' => 'Biaya Ujian Penilaian Tengah Semester (PTS) ' . $monthName . ' ' . $payment->invoice_year . '/' . ($payment->invoice_year + 1),
            'pas' => 'Biaya Ujian Penilaian Akhir Semester (PAS) ' . $monthName . ' ' . $payment->invoice_year . '/' . ($payment->invoice_year + 1),
            default => 'Pembayaran'
        };
        
        // Generate nomor transaksi: student_id + invoice_year + invoice_month + invoice_type
        $typeCode = match($invoiceType) {
            'spp' => '01',
            'uniform' => '02',
            'pts' => '03',
            'pas' => '04',
            default => '00'
        };
        $transactionNo = $payment->student_id . $payment->invoice_year . str_pad($payment->invoice_month, 2, '0', STR_PAD_LEFT) . $typeCode;
        
        // Konversi angka ke terbilang
        $terbilang = $this->numberToWords($payment->amount);

        return view('dashboard.kwitansi-print', compact('payment', 'monthName', 'transactionNo', 'terbilang', 'paymentDescription', 'invoiceType'));
    }

    /**
     * Convert number to Indonesian words
     */
    private function numberToWords($number)
    {
        $number = abs($number);
        $words = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        
        if ($number < 12) {
            return $words[$number];
        } elseif ($number < 20) {
            return $this->numberToWords($number - 10) . " Belas";
        } elseif ($number < 100) {
            return $this->numberToWords(floor($number / 10)) . " Puluh " . $this->numberToWords($number % 10);
        } elseif ($number < 200) {
            return "Seratus " . $this->numberToWords($number - 100);
        } elseif ($number < 1000) {
            return $this->numberToWords(floor($number / 100)) . " Ratus " . $this->numberToWords($number % 100);
        } elseif ($number < 2000) {
            return "Seribu " . $this->numberToWords($number - 1000);
        } elseif ($number < 1000000) {
            return $this->numberToWords(floor($number / 1000)) . " Ribu " . $this->numberToWords($number % 1000);
        } elseif ($number < 1000000000) {
            return $this->numberToWords(floor($number / 1000000)) . " Juta " . $this->numberToWords($number % 1000000);
        }
        
        return $number;
    }

    /**
     * Create SPP invoices for a new student
     */
    private function createSppInvoicesForStudent($student)
    {
        // Pastikan siswa punya kelas
        if (!$student->current_grade_level) {
            return;
        }

        // Ambil tarif SPP berdasarkan kelas siswa
        $tariff = DB::table('spp_tariffs')
            ->where('grade_level', $student->current_grade_level)
            ->where('is_active', 1)
            ->first();

        if (!$tariff) {
            return; // Tidak ada tarif untuk kelas ini
        }

        // Tentukan tahun ajaran saat ini
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Tahun ajaran dimulai Juli (bulan 7)
        // Jika bulan sekarang < Juli, maka tahun ajaran adalah tahun lalu
        $invoiceYear = $currentMonth < 7 ? $currentYear - 1 : $currentYear;

        // Buat 12 invoice untuk bulan Juli (1) sampai Juni (12)
        $months = [
            1 => 'Juli',
            2 => 'Agustus',
            3 => 'September',
            4 => 'Oktober',
            5 => 'November',
            6 => 'Desember',
            7 => 'Januari',
            8 => 'Februari',
            9 => 'Maret',
            10 => 'April',
            11 => 'Mei',
            12 => 'Juni'
        ];

        foreach ($months as $monthNo => $monthName) {
            // Hitung due date (tanggal 10 setiap bulan)
            // Untuk bulan 1-6 (Juli-Desember), tahunnya adalah invoiceYear
            // Untuk bulan 7-12 (Januari-Juni), tahunnya adalah invoiceYear + 1
            $dueYear = $monthNo <= 6 ? $invoiceYear : $invoiceYear + 1;
            $dueMonth = $monthNo <= 6 ? $monthNo + 6 : $monthNo - 6; // Convert to calendar month (1-12)
            
            $dueDate = sprintf('%04d-%02d-10', $dueYear, $dueMonth);

            // Insert invoice
            DB::table('spp_invoices')->insert([
                'student_id' => $student->id,
                'invoice_year' => $invoiceYear,
                'invoice_month' => $monthNo,
                'grade_level_at_invoice' => $student->current_grade_level,
                'tariff_id' => $tariff->id,
                'amount_due' => $tariff->amount,
                'due_date' => $dueDate,
                'status' => 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Auto-generate invoices untuk siswa yang belum punya invoice
     */
    private function autoGenerateMissingInvoices()
    {
        // Tentukan tahun ajaran saat ini
        $currentYear = date('Y');
        $currentMonth = date('n');
        $invoiceYear = $currentMonth < 7 ? $currentYear - 1 : $currentYear;

        // Ambil semua siswa yang punya kelas
        $students = Student::whereNotNull('current_grade_level')
            ->where('student_status', 'active')
            ->get();

        foreach ($students as $student) {
            // Cek apakah siswa sudah punya invoice untuk tahun ajaran ini
            $hasInvoice = DB::table('spp_invoices')
                ->where('student_id', $student->id)
                ->where('invoice_year', $invoiceYear)
                ->exists();

            // Jika belum punya, buat invoice
            if (!$hasInvoice) {
                $this->createSppInvoicesForStudent($student);
            }
        }
    }

    /**
     * Halaman Ganti Password untuk Admin/Kepala Sekolah
     */
    public function editPassword()
    {
        $user = Auth::user();
        return view('dashboard.change-password', compact('user'));
    }

    /**
     * Update Password Admin/Kepala Sekolah
     */
    public function updatePassword(Request $request)
    {
        $user = User::find(Auth::id());
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Pengguna tidak ditemukan');
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $currentPassword = (string) $request->input('current_password');
        $storedHash = trim((string) $user->password);

        $isValid = false;
        if (preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1) {
            $isValid = hash_equals(strtolower($storedHash), md5($currentPassword));
        } elseif (preg_match('/^[a-f0-9]{40}$/i', $storedHash) === 1) {
            $isValid = hash_equals(strtolower($storedHash), sha1($currentPassword));
        } else {
            try {
                $isValid = Hash::check($currentPassword, $storedHash);
            } catch (\RuntimeException $e) {
                $isValid = false;
            }
            if (!$isValid && $storedHash !== '' && hash_equals($storedHash, $currentPassword)) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            return back()
                ->withErrors(['current_password' => 'Password saat ini tidak sesuai.'])
                ->withInput();
        }

        $user->password = Hash::make($request->input('password'));
        $user->must_change_password = false;
        $user->save();

        return redirect()
            ->route('password.edit')
            ->with('success', 'Password berhasil diperbarui.');
    }
}
