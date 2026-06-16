<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use App\Models\Student;

class PaymentController extends Controller
{
    /**
     * Serve a payment proof file through Laravel after access checks.
     */
    public function showProof(Payment $payment)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $payment->loadMissing('invoice.student.user');

        $isAdmin = in_array($user->role, ['admin', 'kepala_sekolah'], true);
        $isOwner = (bool) ($payment->invoice?->student?->user_id && $payment->invoice->student->user_id === $user->id);

        if (!$isAdmin && !$isOwner) {
            abort(403);
        }

        if (!$payment->proof_path || !Storage::disk('public')->exists($payment->proof_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($payment->proof_path);
    }

    /**
     * Proxy OCR request through Laravel (same-origin) to avoid hosting path issues.
     */
    public function processOcrProxy(Request $request)
    {
        // OCR can take >120s on CPU with multi-variant strategy; prevent PHP execution timeout from killing the request.
        @set_time_limit(300);

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'student_id' => 'nullable|integer',
            'uploaded_by' => 'nullable|integer',
            'expected_amount' => 'nullable|numeric',
            'expected_date' => 'nullable|date',
            'expected_bank' => 'nullable|string|max:20',
        ]);

        $file = $request->file('file');
        $sourcePath = $file ? ($file->getRealPath() ?: $file->getPathname()) : null;

        if (empty($sourcePath)) {
            return response()->json([
                'detail' => 'File upload path is unavailable',
            ], 422);
        }

        $ocrUrl = config('services.ocr.process_url');

        // Gunakan parameter file + user sebagai key cache agar lebih reliable antar request
        $fileHash = md5(\Illuminate\Support\Facades\Auth::id() . '_' . $file->getClientOriginalName() . '_' . $file->getSize());
        $cacheKey = 'ocr_raw_' . $fileHash;

        try {
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                $responseJson = \Illuminate\Support\Facades\Cache::get($cacheKey);
                $statusCode = 200;
            } else {
                $response = Http::timeout(300)
                    ->attach('file', fopen($sourcePath, 'r'), $file->getClientOriginalName() ?: 'proof.jpg')
                    ->post($ocrUrl, array_filter([
                        'student_id' => $request->input('student_id'),
                        'uploaded_by' => Auth::id() ?: $request->input('uploaded_by'),
                        'expected_amount' => $request->input('expected_amount'),
                        'expected_date' => $request->input('expected_date'),
                        'expected_bank' => $request->input('expected_bank'),
                    ], static fn($v) => $v !== null && $v !== ''));
                
                $statusCode = $response->status();
                $responseJson = $response->json();
                
                if ($response->ok() && is_array($responseJson)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $responseJson, now()->addMinutes(60));
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('OCR proxy request failed', [
                'error' => $e->getMessage(),
                'ocr_url' => $ocrUrl,
            ]);

            return response()->json([
                'detail' => 'OCR service unavailable',
            ], 503);
        }

        $json = $responseJson;
        if (is_array($json)) {
            // Apply Ninja Field Mapping Layer (Extra layer without breaking existing structure)
            if (isset($json['full_text'])) {
                try {
                    $mapper = new \App\Services\OcrFieldMapperService();
                    $mapped = $mapper->mapFields($json['full_text']);
                    $json['mapped_fields'] = $mapped;
                    
                    $tanggalStr = $mapped['tanggal_transaksi'] ?? null;
                    $paidAt = $tanggalStr;
                    if ($tanggalStr) {
                        try {
                            // Coba parsing date agar valid untuk JS new Date()
                            $tanggalBersih = trim(preg_replace('/[^0-9a-zA-Z\/\-\s\.,:]/', '', $tanggalStr)); // Tambah titik dua untuk jam
                            $parsedDateStr = str_replace(['\\', '/'], '-', $tanggalBersih); // Jangan replace spasi dengan dash!
                            if (preg_match('/^\d{1,2}-\d{1,2}-\d{2}$/', $parsedDateStr)) {
                                $paidAt = \Carbon\Carbon::createFromFormat('d-m-y', $parsedDateStr)->format('Y-m-d H:i:s');
                            } elseif (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $parsedDateStr)) {
                                $paidAt = \Carbon\Carbon::createFromFormat('d-m-Y', $parsedDateStr)->format('Y-m-d H:i:s');
                            } else {
                                $paidAt = \Carbon\Carbon::parse($parsedDateStr)->format('Y-m-d H:i:s');
                            }
                        } catch (\Exception $e) {}
                    }
                    
                    // Prefer Python's structural extraction if available, fallback to Ninja Layer
                    $pyFields = $json['extracted_fields'] ?? [];
                    
                    $json['extracted_fields'] = [
                        'amount' => $pyFields['amount'] ?? $mapped['nominal'] ?? null,
                        'paid_at' => !empty($pyFields['paid_at']) ? $pyFields['paid_at'] : $paidAt,
                        'bank_name' => $pyFields['bank_name'] ?? $mapped['bank_tujuan'] ?? null,
                        'recipient_name' => $pyFields['recipient_name'] ?? $mapped['nama_penerima'] ?? null,
                        'recipient_account' => $pyFields['recipient_account'] ?? $mapped['rekening_tujuan'] ?? null,
                        'sender_name' => $pyFields['sender_name'] ?? $mapped['nama_pengirim'] ?? null,
                        'sender_bank' => $pyFields['sender_bank'] ?? $mapped['bank_pengirim'] ?? null,
                        'sender_account' => $pyFields['sender_account'] ?? $mapped['rekening_pengirim'] ?? null,
                        'reference_no' => $pyFields['reference_no'] ?? $mapped['nomor_referensi'] ?? null,
                    ];
                    
                    // Update mapped fields for ninja validation fallback
                    $mapped['nominal'] = $json['extracted_fields']['amount'];
                    $mapped['tanggal_transaksi'] = $json['extracted_fields']['paid_at'];
                    
                    // Override Python validation with Ninja validation
                    $expectedDate = $request->input('expected_date');
                    $expectedAmount = $request->input('expected_amount');
                    
                    // Auto-correct nominal OCR typo (e.g., .00 read as .000) based on expected amount
                    if ($expectedAmount > 0 && !empty($mapped['nominal'])) {
                        $nom = (int)$mapped['nominal'];
                        $exp = (int)preg_replace('/\D/', '', $expectedAmount);
                        
                        if ($exp > 0 && $nom > $exp * 5) {
                            if ($nom >= 1000) {
                                $div1000 = $nom / 1000;
                                if ($div1000 <= $exp + 10000 && $div1000 >= 10000) {
                                    $mapped['nominal'] = $div1000;
                                    $json['extracted_fields']['amount'] = $div1000;
                                } else {
                                    $div100 = $nom / 100;
                                    if ($div100 <= $exp + 10000 && $div100 >= 10000) {
                                        $mapped['nominal'] = $div100;
                                        $json['extracted_fields']['amount'] = $div100;
                                    }
                                }
                            }
                        }
                    }
                    
                    $strictValidation = $mapper->validateStrict($mapped, $expectedDate, $expectedAmount);
                    
                    $json['validation'] = [
                        'is_valid' => $strictValidation['is_valid'],
                        'errors' => $strictValidation['errors'],
                    ];
                } catch (\Exception $e) {
                    \Log::error("OcrFieldMapperService Error: " . $e->getMessage());
                }
            }

            return response()->json($json, $statusCode);
        }

        return response()->json([
            'detail' => 'Invalid OCR response format',
            'status' => $statusCode,
            'raw' => isset($response) ? mb_substr((string) $response->body(), 0, 400) : 'Cached response error',
        ], 502);
    }

    /**
     * Store a new payment - supports SPP, Uniform, PTS, and PAS
     */
    public function store(Request $request)
    {
        // Force JSON response
        $request->headers->set('Accept', 'application/json');
        
        try {
            // Get invoice type (default to 'spp')
            $invoiceType = $request->input('invoice_type', 'spp');
            
            // Validate invoice type
            $validInvoiceTypes = ['spp', 'uniform', 'pts', 'pas'];
            if (!in_array($invoiceType, $validInvoiceTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe invoice tidak valid',
                    'errors' => ['invoice_type' => ['Tipe invoice tidak valid']]
                ], 422);
            }
            
            // Map invoice type to fixed month
            $invoiceTypeMonths = [
                'spp' => null, // SPP can be any month 1-12
                'uniform' => 1,
                'pts' => 5,
                'pas' => 12,
            ];
            
            // Debug log - log semua request data
            \Log::info('Payment store request received', [
                'student_id' => $request->input('student_id'),
                'invoice_year' => $request->input('invoice_year'),
                'invoice_type' => $invoiceType,
                'payment_month' => $request->input('payment_month'),
                'method' => $request->input('method'),
                'has_file' => $request->hasFile('proof_path'),
                'file_valid' => $request->hasFile('proof_path') ? $request->file('proof_path')->isValid() : false,
                'all_files' => array_keys($request->allFiles()),
                'content_type' => $request->header('Content-Type')
            ]);
            
            // Validation
            $rules = [
                'student_id' => 'required|exists:students,id',
                'invoice_year' => 'required|integer|min:2020|max:2100',
                'paid_at' => 'required|date',
                'amount' => 'required|integer|min:1',
                'method' => 'required|in:cash,transfer',
            ];
            
            // For SPP, require payment_month. For others, it's optional (will use fixed month)
            if ($invoiceType === 'spp') {
                $rules['payment_month'] = 'required|integer|min:1|max:12';
            } else {
                $rules['payment_month'] = 'nullable|integer|min:1|max:12';
            }

            // Additional validation based on method
            $paymentMethod = $request->input('method');
            
            if ($paymentMethod === 'cash') {
                $rules['place_paid'] = 'nullable|string|max:100';
                $rules['received_by_user_id'] = 'required|exists:users,id';
            } else if ($paymentMethod === 'transfer') {
                // Only accept BRI for transfer payments
                $rules['bank_name'] = 'required|in:BRI';
                // File validation dilakukan manual setelah ini
            }

            $validated = $request->validate($rules, [
                'student_id.required' => 'Student ID wajib diisi',
                'student_id.exists' => 'Student tidak ditemukan',
                'invoice_year.required' => 'Tahun invoice wajib diisi',
                'invoice_year.integer' => 'Tahun invoice harus berupa angka',
                'invoice_year.min' => 'Tahun invoice tidak valid',
                'invoice_year.max' => 'Tahun invoice tidak valid',
                'payment_month.required' => 'Bulan pembayaran wajib diisi',
                'payment_month.integer' => 'Bulan pembayaran harus berupa angka',
                'payment_month.min' => 'Bulan pembayaran tidak valid',
                'payment_month.max' => 'Bulan pembayaran tidak valid',
                'paid_at.required' => 'Tanggal bayar wajib diisi',
                'paid_at.date' => 'Format tanggal tidak valid',
                'amount.required' => 'Jumlah bayar wajib diisi',
                'amount.integer' => 'Jumlah bayar harus berupa angka',
                'amount.min' => 'Jumlah bayar minimal Rp 1',
                'method.required' => 'Metode pembayaran wajib dipilih',
                'method.in' => 'Metode pembayaran tidak valid',
                'place_paid.required' => 'Tempat bayar wajib dipilih',
                'received_by_user_id.required' => 'Petugas penerima wajib dipilih',
                'bank_name.required' => 'Nama bank wajib dipilih',
                'bank_name.in' => 'Bank yang diterima hanya BRI',
            ]);

            // Get validated data (needed for subsequent checks)
            $studentId = $validated['student_id'];
            $invoiceYear = $validated['invoice_year'];
            
            // Determine the payment month
            $invoiceMonth = $invoiceType !== 'spp' 
                ? $invoiceTypeMonths[$invoiceType] 
                : ($validated['payment_month'] ?? 1);

            // Student SPP must be sequential: cannot pay month N if any previous month is not cleared.
            // Consider 'pending' and 'verified' as cleared; 'rejected'/'unpaid' are not cleared.
            if (Auth::check() && Auth::user()->role === 'siswa' && $invoiceType === 'spp' && $invoiceMonth > 1) {
                $previousMonths = range(1, $invoiceMonth - 1);

                $prevRows = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $studentId)
                    ->where('si.invoice_year', $invoiceYear)
                    ->where('si.invoice_type', 'spp')
                    ->whereIn('si.invoice_month', $previousMonths)
                    ->select('si.invoice_month', 'p.status')
                    ->orderByDesc('p.id')
                    ->get();

                $latestStatusByMonth = [];
                foreach ($prevRows as $row) {
                    // because ordered newest-first, first seen per month is the latest
                    if (!array_key_exists($row->invoice_month, $latestStatusByMonth)) {
                        $latestStatusByMonth[$row->invoice_month] = $row->status ?: 'pending';
                    }
                }

                foreach ($previousMonths as $m) {
                    $st = $latestStatusByMonth[$m] ?? null;
                    if ($st === 'submitted') {
                        $st = 'pending';
                    }

                    $isCleared = in_array($st, ['pending', 'verified'], true);
                    if (!$isCleared) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pembayaran SPP harus berurutan. Silakan selesaikan pembayaran bulan sebelumnya terlebih dahulu.',
                            'errors' => [
                                'payment_month' => ['Pembayaran SPP harus berurutan (mulai dari bulan pertama).'],
                            ],
                        ], 422);
                    }
                }
            }
            
            // For uniform, get the uniform type
            $uniformType = null;
            $uniformTypes = null;
            if ($invoiceType === 'uniform') {
                $validUniformTypes = ['batik', 'olahraga', 'muslim', 'pramuka', 'almamater'];

                // Support multi-select: uniform_types[] (array) OR legacy uniform_type (string)
                $uniformTypesInput = $request->input('uniform_types');
                if (is_array($uniformTypesInput)) {
                    $uniformTypes = array_values(array_unique(array_filter(array_map(function ($v) {
                        return strtolower(trim((string) $v));
                    }, $uniformTypesInput))));
                    foreach ($uniformTypes as $t) {
                        if (!in_array($t, $validUniformTypes, true)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Jenis seragam tidak valid',
                                'errors' => ['uniform_types' => ['Jenis seragam tidak valid']]
                            ], 422);
                        }
                    }
                    if (count($uniformTypes) === 1) {
                        // normalize to legacy single type too
                        $uniformType = $uniformTypes[0];
                    }
                }

                if (!$uniformTypes) {
                    $uniformType = $request->input('uniform_type');
                    if (!$uniformType || !in_array($uniformType, $validUniformTypes, true)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Jenis seragam tidak valid',
                            'errors' => ['uniform_type' => ['Jenis seragam tidak valid']]
                        ], 422);
                    }
                    $uniformTypes = [$uniformType];
                }
            }

            $invoiceSubtype = $invoiceType === 'uniform' ? (($uniformType ?? '') ?: '') : '';
            
            // Validasi file upload untuk transfer secara manual
            if ($paymentMethod === 'transfer') {
                \Log::info('Transfer payment - checking file upload', [
                    'hasFile' => $request->hasFile('proof_path'),
                    'files' => array_keys($request->allFiles()),
                    'all_inputs' => $request->except(['_token'])
                ]);
                
                if (!$request->hasFile('proof_path')) {
                    \Log::warning('No file uploaded for transfer payment');
                    return response()->json([
                        'success' => false,
                        'message' => 'Bukti transfer wajib diupload',
                        'errors' => ['proof_path' => ['Bukti transfer wajib diupload']]
                    ], 422);
                }
                
                $file = $request->file('proof_path');
                
                \Log::info('File received', [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError()
                ]);
                
                if (!$file->isValid()) {
                    \Log::error('File is not valid', [
                        'error_code' => $file->getError(),
                        'error_message' => $file->getErrorMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'File bukti transfer tidak valid: ' . $file->getErrorMessage(),
                        'errors' => ['proof_path' => ['File bukti transfer tidak valid']]
                    ], 422);
                }
                
                // Check file size (5MB)
                if ($file->getSize() > 5 * 1024 * 1024) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ukuran file maksimal 5MB',
                        'errors' => ['proof_path' => ['Ukuran file maksimal 5MB']]
                    ], 422);
                }
                
                // Check file mime type
                // Untuk verifikasi OCR rekening tujuan, bukti transfer wajib berupa gambar
                $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format file harus JPG atau PNG',
                        'errors' => ['proof_path' => ['Format file harus JPG atau PNG']]
                    ], 422);
                }
            }

            // OCR is best-effort for transfer payments.
            // If the OCR service is down or returns an invalid result, the payment still gets stored
            // as pending so admin/kepsek can validate it manually.
                $ocrStatus = null; // Track OCR service status: success, unavailable, failed
            
            if ($paymentMethod === 'transfer') {
                $ocrUrl = config('services.ocr.process_url');

                // OCR can take >120s on CPU with multi-variant strategy; prevent PHP execution timeout from killing the request.
                @set_time_limit(300);

                try {
                    $file = $request->file('proof_path');
                    $sourcePath = $file ? ($file->getRealPath() ?: $file->getPathname()) : null;

                    if (empty($sourcePath)) {
                        \Log::warning('OCR skipped: proof file path unavailable', [
                            'student_id' => $studentId,
                            'invoice_type' => $invoiceType,
                        ]);
                            $ocrStatus = 'unavailable';
                    } else {
                            // Gunakan parameter file + user sebagai key cache agar lebih reliable antar request
                            $fileHash = md5(\Illuminate\Support\Facades\Auth::id() . '_' . $file->getClientOriginalName() . '_' . $file->getSize());
                            $cacheKey = 'ocr_raw_' . $fileHash;
                            $ocrData = null;
                            $multipart = null;

                            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                                $ocrData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                            } else {
                                try {
                                    $multipart = Http::timeout(300)
                                ->attach('file', fopen($sourcePath, 'r'), $file->getClientOriginalName() ?: 'proof.jpg')
                                ->post($ocrUrl, array_filter([
                                    'student_id' => $validated['student_id'] ?? null,
                                    'uploaded_by' => Auth::id(),
                                    'expected_bank' => $validated['bank_name'] ?? 'BRI',
                                    'expected_date' => $validated['paid_at'] ?? null,
                                    'expected_amount' => ($invoiceType === 'uniform' && is_array($uniformTypes) && count($uniformTypes) > 1)
                                        ? null
                                        : ($validated['amount'] ?? null),
                                    ]));
                                    
                                    if ($multipart && $multipart->ok()) {
                                        $ocrData = $multipart->json() ?: [];
                                        if (is_array($ocrData)) {
                                            \Illuminate\Support\Facades\Cache::put($cacheKey, $ocrData, now()->addMinutes(60));
                                        }
                                    }
                                } catch (\Exception $httpException) {
                                    \Log::warning('OCR HTTP request failed (service unavailable)', [
                                        'error' => $httpException->getMessage(),
                                        'student_id' => $studentId,
                                    ]);
                                    $ocrStatus = 'unavailable';
                                    $multipart = null;
                                }
                            }

                            if (!$ocrData && $multipart && !$multipart->ok()) {
                            \Log::warning('OCR service returned non-OK, continuing with manual review', [
                                'status' => $multipart->status(),
                                'body' => $multipart->body(),
                            ]);
                                $ocrStatus = 'unavailable';
                            } elseif ($ocrData) {
                            $ocrReceiptData = null;
                            
                            // Apply Ninja Field Mapping Layer & Strict Validation
                            if (isset($ocrData['full_text'])) {
                                try {
                                    $mapper = new \App\Services\OcrFieldMapperService();
                                    $ocrData['mapped_fields'] = $mapper->mapFields($ocrData['full_text']);
                                    
                                    // STRICT VALIDATION
                                    $expectedDate = $validated['paid_at'] ?? null;
                                    $expectedAmount = ($invoiceType === 'uniform' && is_array($uniformTypes) && count($uniformTypes) > 1) ? null : ($validated['amount'] ?? null);
                                    
                                    // Auto-correct nominal OCR typo (e.g., .00 read as .000) based on expected amount
                                    if ($expectedAmount > 0 && !empty($ocrData['mapped_fields']['nominal'])) {
                                        $nom = (int)$ocrData['mapped_fields']['nominal'];
                                        $exp = (int)preg_replace('/\D/', '', $expectedAmount);
                                        
                                        // If nominal is unreasonably large compared to expected (e.g., > 5x larger)
                                        // It's likely an OCR error where .00 was read as .000 or similar
                                        if ($exp > 0 && $nom > $exp * 5) {
                                            if ($nom >= 1000) {
                                                // If dividing by 1000 brings it to a reasonable payment amount
                                                // (less than or equal to expected amount + tolerance, and at least 10k)
                                                $div1000 = $nom / 1000;
                                                if ($div1000 <= $exp + 10000 && $div1000 >= 10000) {
                                                    $ocrData['mapped_fields']['nominal'] = $div1000;
                                                }
                                                // Or dividing by 100
                                                else {
                                                    $div100 = $nom / 100;
                                                    if ($div100 <= $exp + 10000 && $div100 >= 10000) {
                                                        $ocrData['mapped_fields']['nominal'] = $div100;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    $strictValidation = $mapper->validateStrict($ocrData['mapped_fields'], $expectedDate, $expectedAmount);
                                    $ocrData['strict_validation'] = $strictValidation;
                                    
                                    $isOcrValid = $strictValidation['is_valid'];
                                    
                                    // We no longer throw an exception here because we WANT to save the OCR data
                                    // even if it's invalid, so the admin can review the failed OCR results.

                                    // Persiapkan data hasil OCR untuk disimpan
                                    $parsedDate = null;
                                    if (!empty($ocrData['mapped_fields']['tanggal_transaksi'])) {
                                        try {
                                            $tanggalBersih = trim(preg_replace('/[^0-9a-zA-Z\/\-\s\.,:]/', '', $ocrData['mapped_fields']['tanggal_transaksi']));
                                            $parsedDateStr = str_replace(['\\', '/'], '-', $tanggalBersih);
                                            $parsedDate = \Carbon\Carbon::parse($parsedDateStr);
                                        } catch (\Exception $e) {}
                                    }

                                    $ocrReceiptData = [
                                        'student_id' => $studentId,
                                        'uploaded_by' => \Illuminate\Support\Facades\Auth::id(),
                                        'amount' => $ocrData['mapped_fields']['nominal'] ?? null,
                                        'paid_at' => $parsedDate ? $parsedDate->format('Y-m-d H:i:s') : null,
                                        'bank_name' => $ocrData['mapped_fields']['bank_tujuan'] ?? null,
                                        'sender_name' => $ocrData['mapped_fields']['nama_pengirim'] ?? null,
                                        'reference_no' => $ocrData['mapped_fields']['nomor_referensi'] ?? null,
                                        'ocr_raw_text' => $ocrData['full_text'],
                                        'ocr_confidence' => $ocrData['confidence'] ?? 1.0,
                                        'status' => 'pending',
                                        'notes' => json_encode([
                                            'mapped_fields' => $ocrData['mapped_fields'],
                                            'validation_checks' => $strictValidation['checks'],
                                        ]),
                                    ];
                                } catch (\Illuminate\Validation\ValidationException $ve) {
                                    throw $ve; // Lempar ulang ke UI
                                } catch (\Exception $e) {
                                    \Log::error("OcrFieldMapperService Error: " . $e->getMessage());
                                }
                            }
                            
                            $isValid = $isOcrValid ?? false;
                            $ocrStatus = $isValid ? 'success' : 'failed';
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('OCR verification unavailable, continuing with manual review', [
                        'error' => $e->getMessage(),
                        'student_id' => $studentId,
                        'invoice_type' => $invoiceType,
                    ]);
                        $ocrStatus = 'unavailable';
                }
            }

            DB::beginTransaction();

            $upsertPaymentId = null;
            $oldProofPathToDelete = null;
            $isUpdate = false;

            // Get student data untuk mendapatkan current_grade_level
            $student = DB::table('students')
                ->where('id', $studentId)
                ->first();
            
            if (!$student) {
                throw new \Exception('Data siswa tidak ditemukan');
            }
            
            if (!$student->current_grade_level) {
                throw new \Exception('Tingkat kelas siswa belum diset. Harap hubungi admin.');
            }

            // ===== MULTI-UNIFORM PAYMENT (admin/kepsek/student) =====
            // If uniform_types[] provided, create/update one payment per selected uniform subtype.
            if ($invoiceType === 'uniform' && is_array($uniformTypes) && count($uniformTypes) > 0) {
                $createdPaymentIds = [];
                $oldProofPathsToDelete = [];
                $sharedProofPath = null;

                // Upload proof once for transfer payments
                if ($validated['method'] === 'transfer') {
                    if ($request->hasFile('proof_path') && $request->file('proof_path')->isValid()) {
                        $file = $request->file('proof_path');

                        $extension = $file->getClientOriginalExtension();
                        if (empty($extension)) {
                            $extension = 'jpg';
                        }

                        $timestamp = time();
                        $uniqueId = uniqid();
                        $fileName = "{$timestamp}_{$uniqueId}.{$extension}";

                        $year = date('Y');
                        $month = date('m');
                        $uploadDir = "payment_proofs/{$year}/{$month}";

                        $fullPath = storage_path("app/public/{$uploadDir}");
                        if (!is_dir($fullPath)) {
                            if (!mkdir($fullPath, 0755, true)) {
                                throw new \Exception('Gagal membuat direktori upload');
                            }
                        }

                        $sourcePath = $file->getRealPath() ?: $file->getPathname();
                        if (empty($sourcePath)) {
                            throw new \Exception('Path temp file kosong');
                        }

                        $stream = fopen($sourcePath, 'r');
                        if ($stream === false) {
                            throw new \Exception('Gagal membuka stream file upload');
                        }

                        $storedPath = $uploadDir . '/' . $fileName;
                        $putResult = Storage::disk('public')->put($storedPath, $stream);
                        fclose($stream);

                        if (!$putResult || !Storage::disk('public')->exists($storedPath)) {
                            throw new \Exception('File gagal disimpan');
                        }

                        $sharedProofPath = $storedPath;
                    } else {
                        throw new \Exception('Bukti transfer wajib diupload untuk pembayaran transfer');
                    }
                }

                // Preload tariff only if we need to create new invoices
                $tariff = null;

                foreach ($uniformTypes as $type) {
                    $invoiceMonthForUniform = $invoiceTypeMonths['uniform'];
                    $invoiceSubtypeForUniform = $type;

                    $invoiceQuery = DB::table('spp_invoices')
                        ->where('student_id', $studentId)
                        ->where('invoice_year', $invoiceYear)
                        ->where('invoice_month', $invoiceMonthForUniform)
                        ->where('invoice_type', 'uniform')
                        ->where('invoice_subtype', $invoiceSubtypeForUniform);

                    $invoice = $invoiceQuery->first();
                    $amountDue = null;

                    if (!$invoice) {
                        if (!$tariff) {
                            $tariff = DB::table('spp_tariffs')
                                ->where('grade_level', $student->current_grade_level)
                                ->where('is_active', 1)
                                ->first();

                            if (!$tariff) {
                                throw new \Exception('Tarif SPP untuk kelas ' . $student->current_grade_level . ' tidak ditemukan. Harap hubungi admin.');
                            }
                        }

                        $uniformCostField = 'uniform_' . $type . '_cost';
                        $amountDue = (int) ($tariff->$uniformCostField ?? 0);
                        if ($amountDue <= 0) {
                            throw new \Exception('Harga seragam ' . ucfirst($type) . ' tidak tersedia untuk kelas ' . $student->current_grade_level);
                        }

                        $invoiceId = DB::table('spp_invoices')->insertGetId([
                            'student_id' => $studentId,
                            'invoice_year' => $invoiceYear,
                            'invoice_month' => $invoiceMonthForUniform,
                            'invoice_type' => 'uniform',
                            'invoice_subtype' => $invoiceSubtypeForUniform,
                            'grade_level_at_invoice' => $student->current_grade_level,
                            'tariff_id' => $tariff->id,
                            'amount_due' => $amountDue,
                            'reference_no' => $type,
                            'due_date' => null,
                            'status' => 'unpaid',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $invoiceId = $invoice->id;
                        $amountDue = (int) ($invoice->amount_due ?? 0);

                        $hasVerifiedPayment = DB::table('payments')
                            ->where('invoice_id', $invoiceId)
                            ->whereRaw('LOWER(TRIM(status)) = ?', ['verified'])
                            ->exists();

                        if ($hasVerifiedPayment) {
                            DB::rollBack();
                            if (!empty($sharedProofPath)) {
                                Storage::disk('public')->delete($sharedProofPath);
                            }
                            return response()->json([
                                'success' => false,
                                'message' => 'Pembayaran seragam ' . ucfirst($type) . ' sudah diverifikasi (lunas).'
                            ], 409);
                        }
                    }

                    // Upsert behavior (update latest payment if exists)
                    $latestPayment = DB::table('payments')
                        ->where('invoice_id', $invoiceId)
                        ->orderByDesc('id')
                        ->select('id', 'status', 'proof_path', 'created_at')
                        ->first();

                    $upsertId = $latestPayment ? (int) $latestPayment->id : null;

                    $paymentData = [
                        'invoice_id' => $invoiceId,
                        'paid_at' => $validated['paid_at'],
                        'amount' => $amountDue > 0 ? $amountDue : (int) $validated['amount'],
                        'method' => $validated['method'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($validated['method'] === 'cash') {
                        $paymentData['status'] = 'verified';
                        $paymentData['received_by'] = $validated['received_by_user_id'];
                        $paymentData['verified_by'] = $validated['received_by_user_id'];
                        $paymentData['verified_at'] = now();
                        $paymentData['bank_name'] = $request->input('place_paid');
                        $paymentData['reference_no'] = $request->input('note');
                        $paymentData['proof_path'] = null;
                    }

                    if ($validated['method'] === 'transfer') {
                        $paymentData['status'] = 'pending';
                        $paymentData['bank_name'] = $validated['bank_name'];
                        $paymentData['received_by'] = null;
                        $paymentData['verified_by'] = null;
                        $paymentData['verified_at'] = null;
                        $paymentData['reference_no'] = $request->input('reference_no');
                        $paymentData['proof_path'] = $sharedProofPath;
                            $paymentData['ocr_status'] = $ocrStatus;
                    }

                    // Save payment via Eloquent to trigger observers
                    if ($upsertId) {
                        $payment = Payment::find($upsertId);
                        if ($payment) {
                            $oldProof = $payment->proof_path;
                            $updateData = $paymentData;
                            unset($updateData['created_at']);
                            $payment->fill($updateData);
                            $payment->save();
                            if (!empty($oldProof) && $oldProof !== ($paymentData['proof_path'] ?? null)) {
                                $oldProofPathsToDelete[] = $oldProof;
                            }
                        } else {
                            $payment = Payment::create($paymentData);
                        }
                    } else {
                        $payment = Payment::create($paymentData);
                    }

                    $createdPaymentIds[] = $payment->id;

                    // Update invoice status for cash
                    if ($validated['method'] === 'cash') {
                        DB::table('spp_invoices')
                            ->where('id', $invoiceId)
                            ->update([
                                'status' => 'paid',
                                'updated_at' => now()
                            ]);
                    }
                }

                DB::commit();

                if (isset($ocrReceiptData) && count($createdPaymentIds) > 0) {
                    $ocrReceiptData['payment_id'] = $createdPaymentIds[0];
                    $ocrReceiptData['file_path'] = $sharedProofPath;
                    \App\Models\OcrPaymentReceipt::updateOrCreate(
                        ['payment_id' => $createdPaymentIds[0]],
                        $ocrReceiptData
                    );
                } elseif ($isUpdate && count($createdPaymentIds) > 0 && $request->hasFile('proof_path')) {
                    \App\Models\OcrPaymentReceipt::where('payment_id', $createdPaymentIds[0])->delete();
                }

                // Delete old proof files after commit
                $oldProofPathsToDelete = array_values(array_unique(array_filter($oldProofPathsToDelete)));
                foreach ($oldProofPathsToDelete as $p) {
                    Storage::disk('public')->delete($p);
                }

                // Compute totals for uniform (all subtypes)
                $totalPaid = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $studentId)
                    ->where('si.invoice_year', $invoiceYear)
                    ->where('si.invoice_type', 'uniform')
                    ->where('p.status', 'verified')
                    ->sum('p.amount');

                $totalBill = DB::table('spp_invoices')
                    ->where('student_id', $studentId)
                    ->where('invoice_year', $invoiceYear)
                    ->where('invoice_type', 'uniform')
                    ->sum('amount_due');

                $remaining = $totalBill - $totalPaid;
                $percentage = $totalBill > 0 ? round(($totalPaid / $totalBill) * 100, 2) : 0;

                $successMessage = 'Pembayaran seragam berhasil disimpan!'
                    . (count($uniformTypes) > 1 ? ' (' . count($uniformTypes) . ' jenis)' : '');
                
                if (isset($ocrReceiptData)) {
                    $successMessage = 'Bukti transfer berhasil lolos verifikasi OCR dan saat ini sedang menunggu review admin.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'payment_id' => $createdPaymentIds[0] ?? null,
                        'payment_ids' => $createdPaymentIds,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining,
                        'percentage' => $percentage,
                        'status' => $validated['method'] === 'cash' ? 'verified' : 'pending'
                    ]
                ]);
            }

            // Find or create invoice untuk bulan dan tahun ini
            // Untuk seragam, bedakan per jenis seragam via invoice_subtype
            $invoiceQuery = DB::table('spp_invoices')
                ->where('student_id', $studentId)
                ->where('invoice_year', $invoiceYear)
                ->where('invoice_month', $invoiceMonth)
                ->where('invoice_type', $invoiceType)
                ->where('invoice_subtype', $invoiceSubtype);
            
            $invoice = $invoiceQuery->first();

            if (!$invoice) {
                // Auto-create invoice if not exists
                // Get tariff berdasarkan grade_level siswa saat ini
                $tariff = DB::table('spp_tariffs')
                    ->where('grade_level', $student->current_grade_level)
                    ->where('is_active', 1)
                    ->first();
                
                if (!$tariff) {
                    throw new \Exception('Tarif SPP untuk kelas ' . $student->current_grade_level . ' tidak ditemukan. Harap hubungi admin.');
                }
                
                // Determine amount based on invoice type
                $amountDue = $tariff->amount; // Default SPP
                $referenceNo = null;
                
                if ($invoiceType === 'uniform' && $uniformType) {
                    $uniformCostField = 'uniform_' . $uniformType . '_cost';
                    $amountDue = $tariff->$uniformCostField ?? 0;
                    if ($amountDue === 0) {
                        throw new \Exception('Harga seragam ' . ucfirst($uniformType) . ' tidak tersedia untuk kelas ' . $student->current_grade_level);
                    }
                    $referenceNo = $uniformType;
                } elseif ($invoiceType === 'pts' && $tariff->pts_cost) {
                    $amountDue = $tariff->pts_cost;
                } elseif ($invoiceType === 'pas' && $tariff->pas_cost) {
                    $amountDue = $tariff->pas_cost;
                }
                
                // Create invoice
                $invoiceId = DB::table('spp_invoices')->insertGetId([
                    'student_id' => $studentId,
                    'invoice_year' => $invoiceYear,
                    'invoice_month' => $invoiceMonth,
                    'invoice_type' => $invoiceType,
                    'invoice_subtype' => $invoiceSubtype,
                    'grade_level_at_invoice' => $student->current_grade_level,
                    'tariff_id' => $tariff->id,
                    'amount_due' => $amountDue,
                    'reference_no' => $referenceNo,
                    'due_date' => null,
                    'status' => 'unpaid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $invoiceId = $invoice->id;
                
                // Repayment rules:
                // - If any payment is verified, invoice is paid: block.
                // - Otherwise, update the latest payment (pending/rejected) instead of creating a new row.
                $hasVerifiedPayment = DB::table('payments')
                    ->where('invoice_id', $invoiceId)
                    ->whereRaw('LOWER(TRIM(status)) = ?', ['verified'])
                    ->exists();

                if ($hasVerifiedPayment) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice untuk ' . $invoiceType . ' ini sudah diverifikasi (lunas).'
                    ], 409);
                }

                $latestPayment = DB::table('payments')
                    ->where('invoice_id', $invoiceId)
                    ->orderByDesc('id')
                    ->select('id', 'status', 'proof_path', 'created_at')
                    ->first();

                if ($latestPayment) {
                    $upsertPaymentId = (int) $latestPayment->id;
                }
            }

            // Prepare payment data
            $paymentData = [
                'invoice_id' => $invoiceId,
                'paid_at' => $validated['paid_at'],
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Handle CASH payment
            if ($validated['method'] === 'cash') {
                $paymentData['status'] = 'verified'; // Cash langsung verified
                $paymentData['received_by'] = $validated['received_by_user_id'];
                $paymentData['verified_by'] = $validated['received_by_user_id']; // Same as receiver for cash
                $paymentData['verified_at'] = now(); // Cash langsung verified
                
                // Gunakan bank_name untuk menyimpan tempat bayar (untuk TUNAI)
                $paymentData['bank_name'] = $request->input('place_paid'); // Ruang Guru, Koperasi, dll
                
                // Gunakan reference_no untuk catatan tambahan jika ada
                $paymentData['reference_no'] = $request->input('note'); // Optional note dari user
                
                $paymentData['proof_path'] = null; // Cash tidak ada bukti transfer
            }

            // Handle TRANSFER payment
            if ($validated['method'] === 'transfer') {
                $paymentData['status'] = 'pending'; // Transfer perlu verifikasi
                $paymentData['bank_name'] = $validated['bank_name']; // Nama bank untuk TRANSFER
                $paymentData['received_by'] = null; // Transfer tidak ada petugas penerima langsung
                $paymentData['verified_by'] = null; // Belum diverifikasi
                $paymentData['verified_at'] = null; // Belum diverifikasi
                $paymentData['reference_no'] = $request->input('reference_no'); // Nomor referensi transfer atau note
                    $paymentData['ocr_status'] = $ocrStatus; // OCR service status
                
                // Upload proof file (sudah divalidasi di atas)
                if ($request->hasFile('proof_path') && $request->file('proof_path')->isValid()) {
                    $file = $request->file('proof_path');
                    
                    try {
                        // Get extension
                        $extension = $file->getClientOriginalExtension();
                        if (empty($extension)) {
                            $extension = 'jpg'; // default
                        }
                        
                        // Generate unique filename
                        $timestamp = time();
                        $uniqueId = uniqid();
                        $fileName = "{$timestamp}_{$uniqueId}.{$extension}";
                        
                        // Set directory path - FIXED: use absolute path
                        $year = date('Y');
                        $month = date('m');
                        $uploadDir = "payment_proofs/{$year}/{$month}";
                        
                        \Log::info('Preparing file upload', [
                            'directory' => $uploadDir,
                            'fileName' => $fileName,
                            'original_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType()
                        ]);
                        
                        // Validate directory is not empty
                        if (empty($uploadDir) || empty($fileName)) {
                            throw new \Exception('Directory atau filename kosong');
                        }
                        
                        // Create directory if not exists
                        $fullPath = storage_path("app/public/{$uploadDir}");
                        if (!is_dir($fullPath)) {
                            if (!mkdir($fullPath, 0755, true)) {
                                throw new \Exception('Gagal membuat direktori upload');
                            }
                        }
                        
                        // Upload manual to avoid empty getRealPath() edge cases on Windows
                        $sourcePath = $file->getRealPath() ?: $file->getPathname();
                        
                        if (empty($sourcePath)) {
                            throw new \Exception('Path temp file kosong');
                        }
                        
                        $stream = fopen($sourcePath, 'r');
                        if ($stream === false) {
                            throw new \Exception('Gagal membuka stream file upload');
                        }
                        
                        $storedPath = $uploadDir . '/' . $fileName;
                        $putResult = Storage::disk('public')->put($storedPath, $stream);
                        fclose($stream);
                        
                        if (!$putResult) {
                            throw new \Exception('File gagal disimpan');
                        }
                        
                        if (!Storage::disk('public')->exists($storedPath)) {
                            throw new \Exception('File tidak ditemukan setelah upload');
                        }
                        
                        $paymentData['proof_path'] = $storedPath;
                        
                        \Log::info('File uploaded successfully', [
                            'stored_path' => $storedPath,
                            'full_path' => storage_path("app/public/{$storedPath}"),
                            'size' => $file->getSize(),
                            'exists' => Storage::disk('public')->exists($storedPath)
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::error('File upload error', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file_name' => $file->getClientOriginalName() ?? 'unknown',
                            'file_size' => $file->getSize() ?? 0
                        ]);
                        throw new \Exception('Gagal mengupload bukti transfer: ' . $e->getMessage());
                    }
                } else {
                    // Tidak ada file yang diupload untuk transfer
                    throw new \Exception('Bukti transfer wajib diupload untuk pembayaran transfer');
                }
            }

            // Create/update payment using Eloquent model to trigger Observer
            if ($upsertPaymentId) {
                $payment = Payment::find($upsertPaymentId);
                if ($payment) {
                    $isUpdate = true;
                    $oldProofPathToDelete = $payment->proof_path;
                    $updateData = $paymentData;
                    unset($updateData['created_at']);
                    $payment->fill($updateData);
                    $payment->save();
                } else {
                    $payment = Payment::create($paymentData);
                }
            } else {
                $payment = Payment::create($paymentData);
            }

            $paymentId = $payment->id;

            // Update invoice status
            // Jika pembayaran cash (verified), maka invoice langsung paid
            // Jika transfer (pending), invoice tetap unpaid sampai diverifikasi
            if ($validated['method'] === 'cash') {
                DB::table('spp_invoices')
                    ->where('id', $invoiceId)
                    ->update([
                        'status' => 'paid',
                        'updated_at' => now()
                    ]);
            }
            // Transfer: invoice tetap unpaid, akan diupdate saat payment diverifikasi

            // Get invoice info for student
            $invoice = DB::table('spp_invoices')
                ->select('student_id', 'invoice_year', 'invoice_month', 'invoice_type', 'amount_due')
                ->where('id', $invoiceId)
                ->first();

            DB::commit();

            if (isset($ocrReceiptData) && $paymentId) {
                $ocrReceiptData['payment_id'] = $paymentId;
                $ocrReceiptData['file_path'] = $paymentData['proof_path'] ?? null;
                \App\Models\OcrPaymentReceipt::updateOrCreate(
                    ['payment_id' => $paymentId],
                    $ocrReceiptData
                );
            } elseif ($isUpdate && $paymentId && $request->hasFile('proof_path')) {
                // If this is an update with a new file, but OCR failed/skipped, delete the old OCR data
                // so we don't accidentally display old OCR data for the new image.
                \App\Models\OcrPaymentReceipt::where('payment_id', $paymentId)->delete();
            }

            // Delete old proof after commit (only when updated and file changed)
            if ($isUpdate
                && !empty($oldProofPathToDelete)
                && isset($paymentData['proof_path'])
                && !empty($paymentData['proof_path'])
                && $oldProofPathToDelete !== $paymentData['proof_path']
            ) {
                Storage::disk('public')->delete($oldProofPathToDelete);
            }

            // Calculate total paid and remaining based on invoice type
            if ($invoiceType === 'spp') {
                // For SPP, calculate all 12 months
                $totalPaid = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $invoice->student_id)
                    ->where('si.invoice_year', $invoice->invoice_year)
                    ->where('si.invoice_type', 'spp')
                    ->where('p.status', 'verified')
                    ->sum('p.amount');

                $tariff = DB::table('spp_tariffs')
                    ->where('grade_level', $student->current_grade_level)
                    ->where('is_active', 1)
                    ->select('amount')
                    ->first();

                $totalBill = $tariff ? $tariff->amount * 12 : 0;
            } else {
                // For uniform/pts/pas, calculate only that type
                $totalPaid = DB::table('payments as p')
                    ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                    ->where('si.student_id', $invoice->student_id)
                    ->where('si.invoice_year', $invoice->invoice_year)
                    ->where('si.invoice_type', $invoiceType)
                    ->where('p.status', 'verified')
                    ->sum('p.amount');

                $totalBill = $invoice->amount_due ?? 0;
            }
            
            $remaining = $totalBill - $totalPaid;
            $percentage = $totalBill > 0 
                ? round(($totalPaid / $totalBill) * 100, 2) 
                : 0;

            $successMessage = $isUpdate ? 'Pembayaran berhasil diperbarui!' : 'Pembayaran berhasil disimpan!';
            if (isset($ocrReceiptData)) {
                $successMessage = 'Bukti transfer berhasil lolos verifikasi OCR dan saat ini sedang menunggu review admin.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'payment_id' => $paymentId,
                    'total_paid' => $totalPaid,
                    'remaining' => $remaining,
                    'percentage' => $percentage,
                    'status' => $paymentData['status']
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete uploaded file if exists
            if (isset($paymentData['proof_path']) && !empty($paymentData['proof_path'])) {
                Storage::disk('public')->delete($paymentData['proof_path']);
            }

            // Log error for debugging
            \Log::error('Payment Error: ' . $e->getMessage());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Delete a payment (admin/kepala sekolah only).
     * This will delete all payment records for the same invoice (to fully reset status to 'Belum')
     * and remove any uploaded proof files from storage.
     */
    public function destroy(Request $request, $id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'kepala_sekolah'], true)) {
            abort(403);
        }

        $payment = Payment::findOrFail($id);
        $invoiceId = $payment->invoice_id;

        DB::beginTransaction();
        try {
            $payments = Payment::where('invoice_id', $invoiceId)->get();
            foreach ($payments as $row) {
                if (!empty($row->proof_path)) {
                    Storage::disk('public')->delete($row->proof_path);
                }
            }

            Payment::where('invoice_id', $invoiceId)->delete();

            DB::commit();

            $message = 'Pembayaran berhasil dihapus. Status kembali menjadi Belum.';
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Failed to delete payment', [
                'payment_id' => $id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            $message = 'Gagal menghapus pembayaran. Silakan coba lagi.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }
    }

    /**
     * Verify a payment (admin only)
     */
    public function verify($id)
    {
        try {
            // Use Eloquent to trigger Observer
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran tidak ditemukan'
                ], 404);
            }

            // Update using Eloquent to trigger Observer (PaymentStatusChanged event)
            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            if ($payment) {
                // Get updated payment data for real-time sync
                $paymentData = $this->getPaymentDetailForSync($id);
                
                // Broadcast event untuk real-time sync
                broadcast(new \App\Events\PaymentStatusUpdated($id, 'verified', $paymentData))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diverifikasi!',
                'data' => $this->getPaymentDetailForSync($id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a payment (admin only)
     */
    public function reject($id)
    {
        try {
            // Use Eloquent to trigger Observer
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran tidak ditemukan'
                ], 404);
            }

            // Update using Eloquent to trigger Observer (PaymentStatusChanged event)
            $payment->update([
                'status' => 'rejected',
                'note' => request()->input('note', 'Pembayaran ditolak'),
            ]);

            if ($payment) {
                // Get updated payment data for real-time sync
                $paymentData = $this->getPaymentDetailForSync($id);
                
                // Broadcast event untuk real-time sync
                broadcast(new \App\Events\PaymentStatusUpdated($id, 'rejected', $paymentData))->toOthers();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran ditolak!',
                'data' => $this->getPaymentDetailForSync($id)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fresh payment data for real-time UI synchronization
     */
    public function getPaymentStatus($id)
    {
        try {
            $user = Auth::user();
            $payment = DB::table('payments as p')
                ->leftJoin('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                ->where('p.id', $id)
                ->select(
                    'p.*',
                    'si.invoice_type',
                    'si.invoice_month',
                    'si.reference_no',
                    'si.student_id'
                )
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran tidak ditemukan'
                ], 404);
            }

            // Authorization check: Student can only see own payments
            if ($user->role === 'siswa') {
                $student = Student::where('user_id', $user->id)->first();
                if (!$student || $payment->student_id != $student->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak memiliki akses ke pembayaran ini'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatPaymentForUI($payment)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payments for a student (untuk real-time sync di dashboard siswa)
     */
    public function getStudentPaymentsSummary(Request $request, $studentId = null)
    {
        try {
            $user = Auth::user();
            
            // Jika route student/api/payments-summary tanpa parameter
            if ($studentId === null) {
                $student = Student::where('user_id', $user->id)->first();
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data siswa tidak ditemukan'
                    ], 404);
                }
                $studentId = $student->id;
            } else {
                // Jika route /api/student/{studentId}/payments dengan parameter
                // Authorization check untuk student
                if ($user->role === 'siswa') {
                    $student = Student::where('user_id', $user->id)->first();
                    if (!$student || $student->id != $studentId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Anda tidak memiliki akses ke data pembayaran ini'
                        ], 403);
                    }
                }
            }

            $year = $request->input('year', date('Y'));
            
            // Get semua payments untuk student ini
            $payments = DB::table('payments as p')
                ->join('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
                ->where('si.student_id', $studentId)
                ->where('si.invoice_year', $year)
                ->select(
                    'p.id',
                    'p.status',
                    'p.amount',
                    'p.paid_at',
                    'si.invoice_type',
                    'si.invoice_month',
                    'si.reference_no'
                )
                ->orderByDesc('p.updated_at')
                ->orderByDesc('p.id')
                ->get();

            // Group by invoice type
            $grouped = [];
            foreach ($payments as $payment) {
                $type = $payment->invoice_type;
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [];
                }
                
                // Untuk uniform, tambahkan reference_no
                $key = $type === 'uniform' ? ($payment->reference_no ?? 'unknown') : ($payment->invoice_month ?? 'unknown');
                // Keep the newest payment per key (query is ordered newest-first)
                if (!isset($grouped[$type][$key])) {
                    $grouped[$type][$key] = [
                        'id' => $payment->id,
                        'status' => $payment->status,
                        'amount' => $payment->amount,
                        'paid_at' => $payment->paid_at,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $grouped,
                'timestamp' => now()->timestamp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Format payment data untuk UI
     */
    private function formatPaymentForUI($payment)
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'method' => $payment->method ?? null,
            'bank_name' => $payment->bank_name ?? null,
            'place_paid' => $payment->place_paid ?? null,
            'proof_path' => $payment->proof_path ?? null,
            'paid_at' => $payment->paid_at,
            'verified_at' => $payment->verified_at ?? null,
            'updated_at' => $payment->updated_at,
            'invoice_type' => $payment->invoice_type ?? null,
            'invoice_month' => $payment->invoice_month ?? null,
            'reference_no' => $payment->reference_no ?? null,
        ];
    }

    /**
     * Helper: Get payment data untuk keperluan sync/broadcast
     */
    private function getPaymentDetailForSync($id)
    {
        $payment = DB::table('payments as p')
            ->leftJoin('spp_invoices as si', 'p.invoice_id', '=', 'si.id')
            ->where('p.id', $id)
            ->select(
                'p.*',
                'si.invoice_type',
                'si.invoice_month',
                'si.reference_no',
                'si.student_id'
            )
            ->first();

        return $payment ? $this->formatPaymentForUI($payment) : null;
    }

    /**
     * Get OCR Receipt data for Admin UI
     */
    public function getOcrReceipt($id)
    {
        try {
            $payment = \App\Models\Payment::with('ocrReceipt')->find($id);
            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
            }

            if (!$payment->ocrReceipt) {
                return response()->json(['success' => false, 'message' => 'No OCR data found for this payment'], 404);
            }

            $ocrReceipt = $payment->ocrReceipt;
            
            // Format notes JSON to array
            $notes = json_decode($ocrReceipt->notes, true) ?: [];

            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $ocrReceipt->amount,
                    'paid_at' => $ocrReceipt->paid_at ? $ocrReceipt->paid_at->format('Y-m-d') : null,
                    'bank_name' => $ocrReceipt->bank_name,
                    'sender_name' => $ocrReceipt->sender_name,
                    'reference_no' => $ocrReceipt->reference_no,
                    'ocr_raw_text' => $ocrReceipt->ocr_raw_text,
                    'ocr_confidence' => $ocrReceipt->ocr_confidence,
                    'mapped_fields' => $notes['mapped_fields'] ?? [],
                    'validation_checks' => $notes['validation_checks'] ?? [],
                    'status' => $ocrReceipt->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
