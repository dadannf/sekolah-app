<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use App\Models\Student;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    /**
     * Halaman Home Siswa - Menampilkan informasi sekolah
     */
    public function index()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan');
        }

        // Get payment summary
        $currentYear = date('Y');
        $currentMonth = date('n');
        $invoiceYear = $currentMonth < 7 ? $currentYear - 1 : $currentYear;
        
        $paymentSummary = null;
        if ($student->current_grade_level) {
            $tariff = DB::table('spp_tariffs')
                ->where('grade_level', $student->current_grade_level)
                ->where('is_active', 1)
                ->first();
            
            if ($tariff) {
                $totalBill = $tariff->amount * 12;
                
                $totalPaid = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $student->id)
                    ->where('si.invoice_year', $invoiceYear)
                    ->where('p.status', 'verified')
                    ->sum('p.amount');
                
                $paidMonths = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $student->id)
                    ->where('si.invoice_year', $invoiceYear)
                    ->where('p.status', 'verified')
                    ->count();
                
                $paymentSummary = [
                    'total_bill' => $totalBill,
                    'total_paid' => $totalPaid,
                    'remaining' => $totalBill - $totalPaid,
                    'paid_months' => $paidMonths,
                    'remaining_months' => 12 - $paidMonths,
                    'percentage' => $totalBill > 0 ? round(($totalPaid / $totalBill) * 100, 1) : 0,
                ];
            }
        }

        // Get active information/announcements
        $information = DB::table('information')
            ->where('is_active', 1)
            ->where(function($query) use ($student) {
                $query->whereNull('target_student_id')
                      ->orWhere('target_student_id', $student->id);
            })
            ->where(function($query) use ($student) {
                $query->whereNull('target_class')
                      ->orWhere('target_class', $student->current_grade_level);
            })
            ->where(function($query) {
                $query->where(function($q) {
                    $q->whereNull('start_at')
                      ->whereNull('end_at');
                })
                ->orWhere(function($q) {
                    $q->where('start_at', '<=', now())
                      ->where('end_at', '>=', now());
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // School identity
        $schoolInfo = [
            'name' => 'SMK BIT BINA AULIA',
            'address' => 'Jl. Pendidikan No. 123, Kota',
            'phone' => '(021) 12345678',
            'email' => 'info@smkbitbinaaulia.sch.id',
        ];

        // Academic calendar / important dates
        $importantDates = [
            ['date' => '15 Jan 2026', 'event' => 'Ujian Tengah Semester', 'icon' => 'fa-file-alt', 'color' => '#ef4444'],
            ['date' => '20 Feb 2026', 'event' => 'Libur Semester', 'icon' => 'fa-calendar-check', 'color' => '#10b981'],
            ['date' => '05 Mar 2026', 'event' => 'Ujian Akhir Semester', 'icon' => 'fa-graduation-cap', 'color' => '#f59e0b'],
        ];

        return view('student.index', compact('student', 'information', 'schoolInfo', 'paymentSummary', 'importantDates', 'invoiceYear'));
    }

    /**
     * Halaman Data Pribadi Siswa
     */
    public function profile()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan');
        }

        $studentColumns = Schema::getColumnListing('students');

        return view('student.profile', compact('student', 'studentColumns'));
    }

    /**
     * Update Data Pribadi Siswa (tidak bisa edit NIS dan NISN)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan');
        }

        $columns = Schema::getColumnListing('students');

        $nonEditable = [
            'id',
            'user_id',
            'nis',
            'nisn',
            'current_grade_level',
            'major',
            'student_status',
            'photo_path',
            'created_at',
            'updated_at',
        ];

        $editableColumns = array_values(array_diff($columns, $nonEditable));

        $rules = [
            'name' => 'required|string|max:100',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];

        $columnTypes = [];

        foreach ($editableColumns as $column) {
            if ($column === 'name') continue;

            $columnType = null;
            try {
                $columnType = Schema::getColumnType('students', $column);
            } catch (\Throwable $e) {
                $columnType = null;
            }

            $columnTypes[$column] = $columnType;

            switch ($columnType) {
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'mediumint':
                case 'tinyint':
                    $rules[$column] = 'nullable|integer';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $rules[$column] = 'nullable|numeric';
                    break;
                case 'boolean':
                    $rules[$column] = 'nullable|boolean';
                    break;
                case 'date':
                case 'datetime':
                case 'datetimetz':
                case 'timestamp':
                    $rules[$column] = 'nullable|date';
                    break;
                case 'text':
                case 'longtext':
                case 'mediumtext':
                    $rules[$column] = 'nullable|string';
                    break;
                default:
                    $rules[$column] = 'nullable';
                    break;
            }
        }

        // Friendly overrides for common fields
        if (array_key_exists('email', $rules)) $rules['email'] = 'nullable|email|max:191';
        if (array_key_exists('phone_number', $rules)) $rules['phone_number'] = 'nullable|string|max:30';
        if (array_key_exists('gender', $rules)) $rules['gender'] = 'nullable|in:M,F,Laki-laki,Perempuan';

        $validated = $request->validate($rules);

        // Handle photo upload - SAMA SEPERTI DI ADMIN
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo_path && file_exists(public_path('storage/' . $student->photo_path))) {
                unlink(public_path('storage/' . $student->photo_path));
            }
            
            $photo = $request->file('photo');
            $photoName = time() . '_' . $student->nis . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('storage/students'), $photoName);
            $validated['photo_path'] = 'students/' . $photoName;
        }

        // Update student (exclude photo which is handled separately)
        $data = Arr::except($validated, ['photo']);

        // Convert empty strings to null only for non-string-ish columns
        foreach ($data as $key => $value) {
            if ($value !== '') continue;

            $type = $columnTypes[$key] ?? null;
            $stringTypes = ['string', 'text', 'mediumtext', 'longtext'];
            if (in_array($type, $stringTypes, true)) continue;

            $data[$key] = null;
        }

        $student->forceFill($data);
        $student->save();

        return redirect()->route('student.profile')
            ->with('success', 'Data pribadi berhasil diperbarui');
    }

    /**
     * Halaman Keuangan Siswa
     */
    public function keuangan()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        
        if (!$student) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan');
        }

        // Use current calendar year (same as admin)
        $invoiceYear = date('Y');
        
        // Get tariff based on student's current grade level
        if (!$student->current_grade_level) {
            return view('student.keuangan', [
                'student' => $student,
                'payment' => null,
                'monthlyPayments' => [],
                'sppPerBulan' => 0,
                'invoiceYear' => $invoiceYear,
                'additionalPayments' => [],
                'error' => 'Tingkat kelas Anda belum diset, silakan hubungi admin'
            ]);
        }
        
        $tariff = DB::table('spp_tariffs')
            ->where('grade_level', $student->current_grade_level)
            ->where('is_active', 1)
            ->first();
        
        if (!$tariff) {
            return view('student.keuangan', [
                'student' => $student,
                'payment' => null,
                'monthlyPayments' => [],
                'sppPerBulan' => 0,
                'invoiceYear' => $invoiceYear,
                'additionalPayments' => [],
                'error' => 'Tarif SPP untuk kelas Anda belum tersedia'
            ]);
        }
        
        $sppPerBulan = $tariff->amount;
        
        // Get all invoices for this student in current year
        $invoices = DB::table('spp_invoices')
            ->where('student_id', $student->id)
            ->where('invoice_year', $invoiceYear)
            ->get()
            ->keyBy('invoice_month');
        
        // Get all payments for these invoices (include pending/rejected for display)
        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->where('si.student_id', $student->id)
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
        $isSequenceClearedUpToPreviousMonth = true;
        // Allow override for student to pay any month regardless of sequence
        $allowStudentSkipSequence = env('STUDENT_ALLOW_SKIP_SEQUENCE', false);
        foreach ($months as $index => $monthName) {
            $monthNo = $index + 1;
            $paymentList = $payments->get('spp', collect())->where('invoice_month', $monthNo)->first();
            $invoice = $invoices->get($monthNo);

            $status = 'unpaid';
            if ($paymentList) {
                $status = $paymentList->status ?: 'pending';
            }

            // Normalize legacy status
            if ($status === 'submitted') {
                $status = 'pending';
            }

            // Sequential rule (student SPP): this month is payable only if all previous months are cleared
            // Consider 'pending' and 'verified' as cleared; 'rejected' and 'unpaid' are NOT cleared.
            $isUnlockedToPay = $isSequenceClearedUpToPreviousMonth;
            $isCleared = in_array($status, ['pending', 'verified'], true);
            $isSequenceClearedUpToPreviousMonth = $isSequenceClearedUpToPreviousMonth && $isCleared;
            
            $monthlyPayments[] = [
                'no' => $monthNo,
                'month' => $monthName,
                'amount' => $sppPerBulan,
                'method' => $paymentList ? strtoupper($paymentList->method) : null,
                'location' => $paymentList ? ($paymentList->bank_name ?? null) : null,
                'status' => $status,
                'payment_id' => $paymentList ? $paymentList->id : null,
                'proof_path' => $paymentList ? $paymentList->proof_path : null,
                'proof' => $paymentList && $paymentList->proof_path ? 'Pratinjau' : ($paymentList ? ucfirst($status) : null),
                'proof_url' => $paymentList && $paymentList->proof_path ? route('payment.proof.show', [$paymentList->id, basename($paymentList->proof_path)]) : null,
                'paid_at' => $paymentList ? $paymentList->paid_at : null,
                'is_unlocked_to_pay' => $allowStudentSkipSequence ? true : $isUnlockedToPay,
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

                // Keep newest payment per uniform type (query is ordered newest-first)
                $latestPaymentForType = $uniformPaymentsForType->first();

                $typePaid = $uniformPaymentsForType
                    ->where('status', 'verified')
                    ->sum('amount');
                
                $totalUniformBill += $uniformCost;
                $totalUniformPaid += $typePaid;
                
                $uniformTypeDetails[$type] = [
                    'name' => ucfirst($type),
                    'cost' => $uniformCost,
                    'paid' => $typePaid,
                    'payment' => $latestPaymentForType,
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
        
        // Create payment object
        $payment = (object) [
            'student_id' => $student->id,
            'student' => $student,
            'invoice_year' => $invoiceYear,
            'total_bill' => $totalBillSpp,
            'total_paid' => $totalPaidSpp,
            'remaining' => $remainingSpp,
            'payment_percentage' => $percentageSpp,
        ];

        return view('student.keuangan', compact(
            'student', 
            'payment', 
            'monthlyPayments', 
            'sppPerBulan', 
            'invoiceYear',
            'additionalPayments'
        ));
    }

    /**
     * Print SPP report for the authenticated student (annual)
     */
    public function printSppReport()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) abort(404);

        $invoiceYear = date('Y');

        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->where('si.student_id', $student->id)
            ->where('si.invoice_type', 'spp')
            ->where('si.invoice_year', $invoiceYear)
            ->select('p.*', 's.name', 's.current_grade_level', 's.major', 'si.invoice_month')
            ->orderBy('p.paid_at', 'asc')
            ->get();

        $scope = 'annual';
        $year = $invoiceYear;
        $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];

        $reportType = 'ujian'; // indicates combined PTS+PAS report
        return view('dashboard.keuangan-print', compact('payments','scope','year','monthNames','reportType'));
    }

    /**
     * Print Uniform (Seragam) report for the authenticated student
     */
    public function printUniformReport()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) abort(404);

        $invoiceYear = date('Y');

        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->where('si.student_id', $student->id)
            ->where('si.invoice_type', 'uniform')
            ->where('si.invoice_year', $invoiceYear)
            ->select('p.*', 's.name', 's.current_grade_level', 's.major', 'si.invoice_month')
            ->orderBy('p.created_at', 'asc')
            ->get();

        $scope = 'annual';
        $year = $invoiceYear;
        $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];

        return view('dashboard.keuangan-print', compact('payments','scope','year','monthNames'));
    }

    /**
     * Print combined Exam report (PTS + PAS) for the authenticated student
     */
    public function printExamReport()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) abort(404);

        $invoiceYear = date('Y');

        $payments = DB::table('payments as p')
            ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->join('students as s', 'si.student_id', '=', 's.id')
            ->where('si.student_id', $student->id)
            ->whereIn('si.invoice_type', ['pts','pas'])
            ->where('si.invoice_year', $invoiceYear)
            ->select('p.*', 's.name', 's.current_grade_level', 's.major', 'si.invoice_month', 'si.invoice_type')
            ->orderBy('si.invoice_type')
            ->orderBy('p.created_at', 'asc')
            ->get();

        $scope = 'annual';
        $year = $invoiceYear;
        $monthNames = [1=>'Juli',2=>'Agustus',3=>'September',4=>'Oktober',5=>'November',6=>'Desember',7=>'Januari',8=>'Februari',9=>'Maret',10=>'April',11=>'Mei',12=>'Juni'];

        return view('dashboard.keuangan-print', compact('payments','scope','year','monthNames'));
    }

    /**
     * Halaman Ganti Password Siswa
     */
    public function editPassword()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return redirect()->route('login')->with('error', 'Data siswa tidak ditemukan');
        }

        return view('student.change-password', compact('student'));
    }

    /**
     * Update Password Siswa
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
        // Support legacy MD5 stored passwords (same logic as login)
        if (preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1) {
            $isValid = hash_equals(strtolower($storedHash), md5($currentPassword));
        } elseif (preg_match('/^[a-f0-9]{40}$/i', $storedHash) === 1) {
            // Legacy SHA1
            $isValid = hash_equals(strtolower($storedHash), sha1($currentPassword));
        } else {
            try {
                $isValid = Hash::check($currentPassword, $storedHash);
            } catch (\RuntimeException $e) {
                $isValid = false;
            }

            // Very old systems may store plaintext passwords (not recommended).
            // If detected, allow match and migrate to bcrypt.
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

        // Notify the student themselves (do NOT include password)
        Notification::createIfMissing(
            [
                'user_id' => $user->id,
                'type' => 'security',
                'action' => 'password_changed',
                'title' => 'Password Berhasil Diperbarui',
            ],
            [
                'user_id' => $user->id,
                'performed_by_id' => $user->id,
                'performed_by_name' => $user->name,
                'type' => 'security',
                'action' => 'password_changed',
                'title' => 'Password Berhasil Diperbarui',
                'message' => 'Password akun Anda berhasil diperbarui.',
                'data' => [
                    'changed_at' => now()->toISOString(),
                ],
                'changes' => [
                    'password' => ['updated' => true],
                ],
            ],
            5
        );

        // Notify admin & kepala sekolah (do NOT include password)
        $student = Student::where('user_id', $user->id)->first();
        if ($student) {
            $adminIds = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();
            foreach ($adminIds as $adminId) {
                Notification::createIfMissing(
                    [
                        'user_id' => $adminId,
                        'type' => 'security',
                        'action' => 'password_changed',
                        'title' => 'Password Siswa Diperbarui',
                    ],
                    [
                        'user_id' => $adminId,
                        'performed_by_id' => $user->id,
                        'performed_by_name' => $user->name,
                        'type' => 'security',
                        'action' => 'password_changed',
                        'title' => 'Password Siswa Diperbarui',
                        'message' => "Siswa '{$student->name}' (NIS: {$student->nis}) telah mengganti password akun mereka.",
                        'data' => [
                            'student_id' => $student->id,
                            'student_user_id' => $user->id,
                            'nis' => $student->nis,
                            'nisn' => $student->nisn,
                            'name' => $student->name,
                            'changed_at' => now()->toISOString(),
                        ],
                        'changes' => [
                            'password' => ['updated' => true],
                        ],
                    ],
                    5
                );
            }
        }

        return redirect()
            ->route('student.password.edit')
            ->with('success', 'Password berhasil diperbarui.');
    }
}
