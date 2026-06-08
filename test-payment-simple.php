#!/usr/bin/env php
<?php

use App\Models\Payment;
use App\Models\SppInvoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Setup Laravel
define('LARAVEL_START', microtime(true));
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Get test data
    $student = Student::first();
    $admin = User::where('role', 'admin')->first();
    $invoice = SppInvoice::where('student_id', $student->id)->where('invoice_year', 2026)->first();

    echo "Test Payment Notification System\n";
    echo "================================\n\n";

    if (!$student) {
        echo "❌ Student not found\n";
        exit(1);
    }
    echo "✓ Student: {$student->name} (ID: {$student->id})\n";

    if (!$admin) {
        echo "❌ Admin not found\n";
        exit(1);
    }
    echo "✓ Admin: {$admin->name} (ID: {$admin->id})\n";

    if (!$invoice) {
        echo "❌ Invoice not found\n";
        exit(1);
    }
    echo "✓ Invoice: ID {$invoice->id}\n\n";

    // Login as admin
    Auth::loginUsingId($admin->id);

    // Test 1: Create payment
    echo "Creating payment...\n";
    $payment = Payment::create([
        'invoice_id' => $invoice->id,
        'paid_at' => now(),
        'amount' => 50000,
        'method' => 'cash',
        'status' => 'verified',
        'received_by' => $admin->id,
        'verified_by' => $admin->id,
        'verified_at' => now(),
        'bank_name' => 'TEST',
        'reference_no' => 'Test Payment',
    ]);

    echo "✓ Payment created (ID: {$payment->id})\n";

    // Check notifications
    $notif = DB::table('notifications')
        ->where('type', 'payment')
        ->where('action', 'created')
        ->latest()
        ->first();

    if ($notif) {
        echo "✓ Notification created!\n";
        echo "  - Title: {$notif->title}\n";
        echo "  - Message: {$notif->message}\n";
        echo "  - Performed by: {$notif->performed_by_name}\n";
    } else {
        echo "❌ NO NOTIFICATION CREATED!\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo $e->getTraceAsString();
    exit(1);
}
