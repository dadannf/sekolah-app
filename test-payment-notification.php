<?php
$path = __DIR__;
require_once $path . '/vendor/autoload.php';

$app = require_once $path . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\SppInvoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "=== TEST PAYMENT NOTIFICATION SYSTEM ===\n\n";

// Find or create test data
$student = Student::first();
if (!$student) {
    echo "❌ Tidak ada student ditemukan\n";
    exit;
}
echo "✓ Student ditemukan: {$student->name} (ID: {$student->id})\n";

// Get or create invoice
$invoice = SppInvoice::where('student_id', $student->id)
    ->where('invoice_year', date('Y'))
    ->where('invoice_type', 'spp')
    ->first();

if (!$invoice) {
    echo "❌ Tidak ada invoice ditemukan\n";
    exit;
}
echo "✓ Invoice ditemukan: ID {$invoice->id}\n";

// Get admin user
$admin = User::where('role', 'admin')->first();
if (!$admin) {
    echo "❌ Tidak ada admin user ditemukan\n";
    exit;
}
echo "✓ Admin user ditemukan: {$admin->name} (ID: {$admin->id})\n";

// Simulate login as admin
Auth::loginUsingId($admin->id);
echo "✓ Logged in as admin\n\n";

// Test 1: Create a new CASH payment
echo "--- Test 1: Create CASH Payment ---\n";
$cashPayment = Payment::create([
    'invoice_id' => $invoice->id,
    'paid_at' => now(),
    'amount' => 50000,
    'method' => 'cash',
    'status' => 'verified',
    'received_by' => $admin->id,
    'verified_by' => $admin->id,
    'verified_at' => now(),
    'bank_name' => 'Sekolah',
    'reference_no' => 'Test Cash Payment',
]);
echo "✓ Cash payment created (ID: {$cashPayment->id})\n";

// Check notifications created
$notifCount = DB::table('notifications')
    ->where('type', 'payment')
    ->where('action', 'created')
    ->count();
echo "✓ Total payment creation notifications: {$notifCount}\n";

$adminNotif = DB::table('notifications')
    ->where('type', 'payment')
    ->where('action', 'created')
    ->where('user_id', $admin->id)
    ->first();

if ($adminNotif) {
    echo "✓ Admin notification found:\n";
    echo "  - Title: {$adminNotif->title}\n";
    echo "  - Message: {$adminNotif->message}\n";
    echo "  - Performed by: {$adminNotif->performed_by_name}\n";
    if ($adminNotif->data) {
        $data = json_decode($adminNotif->data, true);
        echo "  - Data (JSON): " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Admin notification NOT found!\n";
}

// Test 2: Create a TRANSFER payment (pending)
echo "\n--- Test 2: Create TRANSFER Payment (Pending) ---\n";
$transferPayment = Payment::create([
    'invoice_id' => $invoice->id,
    'paid_at' => now(),
    'amount' => 100000,
    'method' => 'transfer',
    'status' => 'pending',
    'bank_name' => 'BCA',
    'reference_no' => 'TRF123456',
    'proof_path' => 'test.jpg',
]);
echo "✓ Transfer payment created (ID: {$transferPayment->id})\n";

// Check notifications
$notifCount = DB::table('notifications')
    ->where('type', 'payment')
    ->where('action', 'created')
    ->count();
echo "✓ Total payment creation notifications: {$notifCount}\n";

// Test 3: Update payment status to verify (change from pending to verified)
echo "\n--- Test 3: Verify Transfer Payment ---\n";
$transferPayment->update([
    'status' => 'verified',
    'verified_at' => now(),
    'verified_by' => $admin->id,
]);
echo "✓ Payment updated to verified (ID: {$transferPayment->id})\n";

// Check status changed notifications
$statusChangedNotif = DB::table('notifications')
    ->where('type', 'payment')
    ->where('action', 'status_changed')
    ->orderBy('created_at', 'desc')
    ->first();

if ($statusChangedNotif) {
    echo "✓ Status change notification found:\n";
    echo "  - Title: {$statusChangedNotif->title}\n";
    echo "  - Message: {$statusChangedNotif->message}\n";
    echo "  - Performed by: {$statusChangedNotif->performed_by_name}\n";
    if ($statusChangedNotif->changes) {
        $changes = json_decode($statusChangedNotif->changes, true);
        echo "  - Changes: " . json_encode($changes, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ Status change notification NOT found!\n";
}

// Test 4: Reject a temporary payment
echo "\n--- Test 4: Reject Payment ---\n";
// Create another transfer payment to reject
$rejectPayment = Payment::create([
    'invoice_id' => $invoice->id,
    'paid_at' => now(),
    'amount' => 75000,
    'method' => 'transfer',
    'status' => 'pending',
    'bank_name' => 'Mandiri',
    'reference_no' => 'TRF999999',
    'proof_path' => 'test2.jpg',
]);
echo "✓ Transfer payment created for rejection (ID: {$rejectPayment->id})\n";

// Reject it
$rejectPayment->update([
    'status' => 'rejected',
    'note' => 'Bukti transfer tidak sesuai',
]);
echo "✓ Payment rejected (ID: {$rejectPayment->id})\n";

// Check rejection notification
$rejectionNotif = DB::table('notifications')
    ->where('type', 'payment')
    ->where('action', 'status_changed')
    ->where('data', 'LIKE', '%"new_status":"rejected"%')
    ->orderBy('created_at', 'desc')
    ->first();

if ($rejectionNotif) {
    echo "✓ Rejection notification found:\n";
    echo "  - Title: {$rejectionNotif->title}\n";
    echo "  - Message: {$rejectionNotif->message}\n";
} else {
    echo "❌ Rejection notification NOT found!\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
$totalPaymentNotif = DB::table('notifications')
    ->where('type', 'payment')
    ->count();
echo "Total payment notifications created: {$totalPaymentNotif}\n";

$unreadNotif = DB::table('notifications')
    ->where('type', 'payment')
    ->whereNull('read_at')
    ->count();
echo "Unread payment notifications: {$unreadNotif}\n";

echo "\n✓ Test completed!\n";
