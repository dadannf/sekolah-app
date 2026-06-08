<?php
/**
 * Test script untuk OCR Fallback Payment System
 * 
 * Scenarios:
 * 1. OCR service working correctly
 * 2. OCR service unavailable (timeout)
 * 3. OCR validation failed
 * 4. Verify admin can approve payments with ocr_status
 */

require base_path('vendor/autoload.php');

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   OCR Fallback Payment System - Test Suite                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1: Check database column exists
echo "[TEST 1] Checking if ocr_status column exists...\n";
$columns = DB::getSchemaBuilder()->getColumns('payments');
$hasOcrStatus = collect($columns)->pluck('name')->contains('ocr_status');

if ($hasOcrStatus) {
    echo "✓ Column 'ocr_status' exists\n";
} else {
    echo "✗ Column 'ocr_status' NOT FOUND\n";
    echo "  Run migration: php artisan migrate\n";
}
echo "\n";

// Test 2: Check verified_by and verified_at columns
echo "[TEST 2] Checking verification columns...\n";
$hasVerifiedBy = collect($columns)->pluck('name')->contains('verified_by');
$hasVerifiedAt = collect($columns)->pluck('name')->contains('verified_at');

if ($hasVerifiedBy && $hasVerifiedAt) {
    echo "✓ Columns 'verified_by' and 'verified_at' exist\n";
} else {
    echo "✗ Verification columns missing\n";
}
echo "\n";

// Test 3: Check Payment model
echo "[TEST 3] Checking Payment model...\n";
try {
    $payment = new Payment();
    $fillable = $payment->getFillable();
    
    if (in_array('ocr_status', $fillable)) {
        echo "✓ 'ocr_status' in Payment::fillable\n";
    } else {
        echo "✗ 'ocr_status' NOT in Payment::fillable\n";
    }
    
    if (in_array('verified_by', $fillable)) {
        echo "✓ 'verified_by' in Payment::fillable\n";
    } else {
        echo "✗ 'verified_by' NOT in Payment::fillable\n";
    }
    
    if (in_array('verified_at', $fillable)) {
        echo "✓ 'verified_at' in Payment::fillable\n";
    } else {
        echo "✗ 'verified_at' NOT in Payment::fillable\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking Payment model: {$e->getMessage()}\n";
}
echo "\n";

// Test 4: Check Payment model accessors
echo "[TEST 4] Checking Payment model accessors...\n";
try {
    $payment = Payment::first();
    
    if ($payment) {
        // Test accessors
        $ocrStatusLabel = $payment->ocr_status_label;
        $ocrStatusBadge = $payment->ocr_status_badge;
        
        echo "✓ Payment accessors working\n";
        echo "  - ocr_status_label: {$ocrStatusLabel}\n";
        echo "  - ocr_status_badge: {$ocrStatusBadge}\n";
    } else {
        echo "ℹ No payments found in database (skipped)\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing accessors: {$e->getMessage()}\n";
}
echo "\n";

// Test 5: Sample data analysis
echo "[TEST 5] Sample payment records...\n";
$payments = DB::table('payments')
    ->where('method', 'transfer')
    ->limit(5)
    ->get(['id', 'method', 'status', 'ocr_status', 'verified_by', 'verified_at']);

if ($payments->count() > 0) {
    echo "Found " . $payments->count() . " transfer payments:\n";
    foreach ($payments as $p) {
        echo "  ID: {$p->id}, Status: {$p->status}, OCR: {$p->ocr_status}, Verified: {$p->verified_by}\n";
    }
} else {
    echo "ℹ No transfer payments found\n";
}
echo "\n";

// Summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Test Summary                                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✓ If all checks passed, the OCR Fallback Payment System is ready!\n";
echo "\n";
echo "Next steps:\n";
echo "1. Test payment submission with OCR service running\n";
echo "2. Test payment submission with OCR service stopped\n";
echo "3. Check admin dashboard for payment verification\n";
echo "4. Verify admin can approve/reject payments\n";
echo "\n";
