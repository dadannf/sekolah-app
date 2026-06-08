<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$argv = $_SERVER['argv'] ?? [];

$studentId = isset($argv[1]) ? (int) $argv[1] : 3;
$invoiceYear = isset($argv[2]) ? (int) $argv[2] : 2026;
$invoiceMonth = isset($argv[3]) ? (int) $argv[3] : 1;
$invoiceType = isset($argv[4]) ? (string) $argv[4] : 'spp';
$invoiceSubtype = isset($argv[5]) ? (string) $argv[5] : '';

$invoice = DB::table('spp_invoices')
    ->where('student_id', $studentId)
    ->where('invoice_year', $invoiceYear)
    ->where('invoice_month', $invoiceMonth)
    ->where('invoice_type', $invoiceType)
    ->where('invoice_subtype', $invoiceSubtype)
    ->first();

if (!$invoice) {
    fwrite(STDOUT, "invoice:notfound\n");
    exit(0);
}

fwrite(STDOUT, "invoice_id={$invoice->id}\n");

$payments = DB::table('payments')
    ->where('invoice_id', $invoice->id)
    ->orderBy('id')
    ->get(['id', 'status', 'amount', 'method', 'paid_at', 'created_at']);

foreach ($payments as $p) {
    $status = $p->status === null ? 'NULL' : $p->status;
    fwrite(STDOUT, $p->id . "\t" . $status . "\t" . $p->method . "\t" . $p->amount . "\t" . $p->paid_at . "\n");
}
