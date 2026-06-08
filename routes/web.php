<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminRegistrationController;
use App\Http\Controllers\NotificationPageController;
use App\Http\Controllers\Api\NotificationController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Admin/Kepala Sekolah Registration (Bootstrap Only)
Route::get('/admin/register', [AdminRegistrationController::class, 'create'])->name('admin.register');
Route::post('/admin/register', [AdminRegistrationController::class, 'store'])->name('admin.register.store');

// Forgot Password Routes
Route::get('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'submitRequest'])->name('password.email');

// Protected Routes (harus login)
Route::middleware('auth')->group(function () {
    // Root redirect based on role
    Route::get('/', function () {
        if (Auth::user()->role === 'siswa') {
            return redirect()->route('student.dashboard');
        }
        return redirect()->route('dashboard');
    });

    // Notification API Routes
    Route::prefix('api/notifications')->name('notifications.')->group(function () {
        // Non-parameter routes (harus didepan agar prioritas lebih tinggi)
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread_count');
        Route::put('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark_all_as_read');
        Route::delete('/', [NotificationController::class, 'destroyAll'])->name('destroy_all');
        
        // Parameter routes (didepan agar bisa di-override)
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::put('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark_as_read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Notifications page
    Route::get('/notifications', [NotificationPageController::class, 'index'])->name('notifications.all');
    Route::post('/notifications/bulk-delete', [NotificationPageController::class, 'bulkDelete'])->name('notifications.bulk-delete');

    // Serve payment proof files through Laravel to avoid direct /storage access issues
    Route::get('/payment-proofs/{payment}/{filename?}', [PaymentController::class, 'showProof'])->name('payment.proof.show');
});

// Admin Routes (admin + kepala sekolah)
Route::middleware(['auth', 'role:admin,kepala_sekolah'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/password', [DashboardController::class, 'editPassword'])->name('password.edit');
    Route::put('/dashboard/password', [DashboardController::class, 'updatePassword'])->name('password.update');
    
    // Forgot Password Requests (Admin)
    Route::get('/dashboard/forgot-password-requests', [\App\Http\Controllers\AdminForgotPasswordController::class, 'index'])->name('admin.forgot-password.index');
    Route::post('/dashboard/forgot-password-requests/{id}/approve', [\App\Http\Controllers\AdminForgotPasswordController::class, 'approve'])->name('admin.forgot-password.approve');
    Route::post('/dashboard/forgot-password-requests/{id}/reject', [\App\Http\Controllers\AdminForgotPasswordController::class, 'reject'])->name('admin.forgot-password.reject');
    Route::get('/dashboard/siswa', [DashboardController::class, 'siswa'])->name('dashboard.siswa');
    Route::post('/dashboard/siswa', [DashboardController::class, 'storeSiswa'])->name('dashboard.siswa.store');
    Route::post('/dashboard/siswa/import-excel', [DashboardController::class, 'importSiswaExcel'])->name('dashboard.siswa.import-excel');
    Route::get('/dashboard/siswa/template-excel', [DashboardController::class, 'templateSiswaExcel'])->name('dashboard.siswa.template-excel');
    Route::get('/dashboard/siswa/check', [DashboardController::class, 'checkSiswa'])->name('dashboard.siswa.check');
    Route::post('/dashboard/siswa/bulk-delete', [DashboardController::class, 'bulkDeleteSiswa'])->name('dashboard.siswa.bulk-delete');
    Route::put('/dashboard/siswa/{id}', [DashboardController::class, 'updateSiswa'])->name('dashboard.siswa.update');
    Route::delete('/dashboard/siswa/{id}', [DashboardController::class, 'deleteSiswa'])->name('dashboard.siswa.delete');
    Route::get('/dashboard/keuangan', [DashboardController::class, 'keuangan'])->name('dashboard.keuangan');
    Route::get('/dashboard/keuangan/pending', [DashboardController::class, 'pendingVerifications'])->name('dashboard.keuangan.pending');
    Route::get('/dashboard/keuangan/print', [DashboardController::class, 'keuanganPrint'])->name('dashboard.keuangan.print');
    Route::get('/dashboard/keuangan/{id}/{year?}', [DashboardController::class, 'keuanganDetail'])->name('dashboard.keuangan.detail');
    Route::get('/dashboard/keuangan/{id}/print/{year?}', [DashboardController::class, 'keuanganDetailPrint'])->name('dashboard.keuangan.detail.print');
    Route::get('/dashboard/keuangan/payment/{paymentId}/receipt', [DashboardController::class, 'printReceipt'])->name('payment.receipt');
    
    // Information Routes - Redirect dashboard.informasi to information.index
    Route::get('/dashboard/informasi', function() {
        return redirect()->route('information.index');
    })->name('dashboard.informasi');
    
    Route::resource('information', InformationController::class);
    Route::post('/information/{information}/toggle', [InformationController::class, 'toggleActive'])->name('information.toggle');
    Route::get('/information/{information}/detail', [InformationController::class, 'getDetail'])->name('information.detail');
    
    // Payment Routes
    Route::post('/dashboard/keuangan/payment/store', [PaymentController::class, 'store'])->name('payment.store');
    Route::post('/dashboard/keuangan/payment/ocr/process', [PaymentController::class, 'processOcrProxy'])->name('payment.ocr.process');
    Route::post('/dashboard/keuangan/payment/{id}/verify', [PaymentController::class, 'verify'])->name('payment.verify');
    Route::post('/dashboard/keuangan/payment/{id}/reject', [PaymentController::class, 'reject'])->name('payment.reject');
    Route::delete('/dashboard/keuangan/payment/{id}', [PaymentController::class, 'destroy'])->name('payment.destroy');
    Route::get('/dashboard/keuangan/payment/{id}/ocr-receipt', [PaymentController::class, 'getOcrReceipt'])->name('payment.ocr.receipt.api');
    
    // Real-time Payment Status API (untuk sync status antara admin dan siswa)
    Route::get('/api/payment/{id}/status', [PaymentController::class, 'getPaymentStatus'])->name('payment.status.api');
    Route::get('/api/student/{studentId}/payments', [PaymentController::class, 'getStudentPaymentsSummary'])->name('student.payments.api');

});

// Admin/Kepala Sekolah user registration (ONLY kepala sekolah)
Route::middleware(['auth', 'role:kepala_sekolah'])->group(function () {
    Route::get('/dashboard/admin/register', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/dashboard/admin/register', [AdminUserController::class, 'store'])->name('admin.users.store');
});

// Student Routes (only for siswa role)
Route::middleware(['auth', 'role:siswa'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [StudentDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/keuangan', [StudentDashboardController::class, 'keuangan'])->name('keuangan');

    // Change password
    Route::get('/password', [StudentDashboardController::class, 'editPassword'])->name('password.edit');
    Route::put('/password', [StudentDashboardController::class, 'updatePassword'])->name('password.update');
    
    // Student can only create payment (no verify/reject)
    Route::post('/keuangan/payment/store', [PaymentController::class, 'store'])->name('payment.store');
    Route::post('/keuangan/payment/ocr/process', [PaymentController::class, 'processOcrProxy'])->name('payment.ocr.process');
    
    // Real-time Payment Status API (untuk sinkronisasi live di student dashboard)
    Route::get('/api/payment/{id}/status', [PaymentController::class, 'getPaymentStatus'])->name('payment.status.api');
    Route::get('/api/payments-summary', [PaymentController::class, 'getStudentPaymentsSummary'])->name('payments.summary.api');
    
    // Student can view/print receipt for their own payments
    Route::get('/keuangan/payment/{paymentId}/receipt', [DashboardController::class, 'printReceipt'])->name('payment.receipt');
    // Student: print reports per category
    Route::get('/keuangan/print/spp', [StudentDashboardController::class, 'printSppReport'])->name('keuangan.print.spp');
    Route::get('/keuangan/print/seragam', [StudentDashboardController::class, 'printUniformReport'])->name('keuangan.print.seragam');
    Route::get('/keuangan/print/ujian', [StudentDashboardController::class, 'printExamReport'])->name('keuangan.print.ujian');
    
    // Student can view information detail
    Route::get('/information/{information}/detail', [InformationController::class, 'getDetail'])->name('information.detail');
});
