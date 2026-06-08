<?php
/**
 * Test script untuk memverifikasi cascade delete functionality
 * Jalankan: php artisan tinker < test-cascade-delete.php
 */

use App\Models\Student;
use App\Models\SppInvoice;
use App\Models\Payment;
use App\Models\User;

echo "\n=== Testing Cascade Delete Functionality ===\n\n";

// 1. Create test student
echo "1. Creating test student...\n";
$testUser = User::create([
    'name' => 'Test Student Cascade Delete',
    'email' => 'test.cascade.' . time() . '@test.com',
    'password' => bcrypt('password'),
    'role' => 'siswa'
]);

$testStudent = Student::create([
    'user_id' => $testUser->id,
    'nis' => 'NIS-CASCADE-' . time(),
    'nisn' => 'NISN-CASCADE-' . time(),
    'name' => 'TEST CASCADE STUDENT',
    'current_grade_level' => 10,
    'student_status' => 'active'
]);

echo "   ✓ Created student ID: {$testStudent->id}, User ID: {$testUser->id}\n";

// 2. Create test invoices
echo "2. Creating test invoices...\n";
$invoiceIds = [];
for ($i = 1; $i <= 3; $i++) {
    $invoice = SppInvoice::create([
        'student_id' => $testStudent->id,
        'invoice_year' => 2026,
        'invoice_month' => $i,
        'grade_level_at_invoice' => 10,
        'tariff_id' => 1,
        'amount_due' => 200000,
        'status' => 'unpaid'
    ]);
    $invoiceIds[] = $invoice->id;
}
echo "   ✓ Created " . count($invoiceIds) . " invoices: " . implode(', ', $invoiceIds) . "\n";

// 3. Create test payments
echo "3. Creating test payments...\n";
$paymentIds = [];
foreach ($invoiceIds as $invoiceId) {
    $payment = Payment::create([
        'invoice_id' => $invoiceId,
        'student_id' => $testStudent->id,
        'amount' => 200000,
        'method' => 'transfer',
        'status' => 'pending',
        'paid_at' => now()
    ]);
    $paymentIds[] = $payment->id;
}
echo "   ✓ Created " . count($paymentIds) . " payments: " . implode(', ', $paymentIds) . "\n";

// 4. Verify data exists
echo "\n4. Verifying test data exists...\n";
$existingInvoices = SppInvoice::where('student_id', $testStudent->id)->count();
$existingPayments = Payment::whereIn('invoice_id', $invoiceIds)->count();
$studentExists = Student::where('id', $testStudent->id)->exists();
echo "   ✓ Invoices in DB: {$existingInvoices}\n";
echo "   ✓ Payments in DB: {$existingPayments}\n";
echo "   ✓ Student exists: " . ($studentExists ? 'YES' : 'NO') . "\n";

// 5. Delete student and check cascade delete
echo "\n5. Deleting student (should cascade delete invoices and payments)...\n";
$studentIdBefore = $testStudent->id;
$testStudent->delete();
echo "   ✓ Student deleted\n";

// 6. Verify cascade delete worked
echo "\n6. Verifying cascade delete...\n";
$invoicesAfterDelete = SppInvoice::where('student_id', $studentIdBefore)->count();
$paymentsAfterDelete = Payment::whereIn('invoice_id', $invoiceIds)->count();
$studentAfterDelete = Student::where('id', $studentIdBefore)->exists();
$userAfterDelete = User::where('id', $testUser->id)->exists();

echo "   ✓ Invoices after delete: {$invoicesAfterDelete} (should be 0)\n";
echo "   ✓ Payments after delete: {$paymentsAfterDelete} (should be 0)\n";
echo "   ✓ Student after delete: " . ($studentAfterDelete ? 'YES (ERROR!)' : 'NO (OK)') . "\n";
echo "   ✓ User after delete: " . ($userAfterDelete ? 'YES (ERROR!)' : 'NO (OK)') . "\n";

// 7. Test result
echo "\n=== Test Result ===\n";
if ($invoicesAfterDelete === 0 && $paymentsAfterDelete === 0 && !$studentAfterDelete && !$userAfterDelete) {
    echo "✅ SUCCESS: Cascade delete is working correctly!\n";
} else {
    echo "❌ FAILED: Cascade delete did not work as expected!\n";
    echo "   - Invoices remaining: {$invoicesAfterDelete}\n";
    echo "   - Payments remaining: {$paymentsAfterDelete}\n";
    echo "   - Student still exists: " . ($studentAfterDelete ? 'YES' : 'NO') . "\n";
    echo "   - User still exists: " . ($userAfterDelete ? 'YES' : 'NO') . "\n";
}

echo "\n";
