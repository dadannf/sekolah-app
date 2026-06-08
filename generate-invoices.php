<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== GENERATING INVOICES FOR STUDENTS ===\n\n";

// Get all active students
$students = DB::table('students')
    ->where('student_status', 'active')
    ->whereNotNull('current_grade_level')
    ->get();

echo "Found {$students->count()} active students\n\n";

foreach ($students as $student) {
    echo "Processing: {$student->name} (Grade {$student->current_grade_level})\n";
    
    // Check if already has invoices for current year
    $currentMonth = date('n');
    $invoiceYear = $currentMonth >= 7 ? date('Y') : date('Y') - 1;
    
    $existingInvoices = DB::table('spp_invoices')
        ->where('student_id', $student->id)
        ->where('invoice_year', $invoiceYear)
        ->count();
    
    if ($existingInvoices > 0) {
        echo "  ⏭️  Already has {$existingInvoices} invoices, skipping...\n\n";
        continue;
    }
    
    // Get tariff
    $tariff = DB::table('spp_tariffs')
        ->where('grade_level', $student->current_grade_level)
        ->where('is_active', 1)
        ->first();
    
    if (!$tariff) {
        echo "  ❌ No tariff found for grade {$student->current_grade_level}\n\n";
        continue;
    }
    
    echo "  Using tariff: Rp " . number_format($tariff->amount, 0, ',', '.') . "/month\n";
    echo "  Invoice year: {$invoiceYear}\n";
    
    // Generate 12 months of invoices
    $invoices = [];
    for ($month = 1; $month <= 12; $month++) {
        // Calculate due date (10th of each month)
        // Month 1-6 = July-December of invoice_year
        // Month 7-12 = January-June of invoice_year+1
        if ($month <= 6) {
            // July to December
            $calendarMonth = 6 + $month; // 7, 8, 9, 10, 11, 12
            $calendarYear = $invoiceYear;
        } else {
            // January to June
            $calendarMonth = $month - 6; // 1, 2, 3, 4, 5, 6
            $calendarYear = $invoiceYear + 1;
        }
        
        $dueDate = "{$calendarYear}-" . str_pad($calendarMonth, 2, '0', STR_PAD_LEFT) . "-10";
        
        $invoices[] = [
            'student_id' => $student->id,
            'invoice_year' => $invoiceYear,
            'invoice_month' => $month,
            'grade_level_at_invoice' => $student->current_grade_level,
            'tariff_id' => $tariff->id,
            'amount_due' => $tariff->amount,
            'due_date' => $dueDate,
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
    
    // Insert all invoices
    DB::table('spp_invoices')->insert($invoices);
    
    echo "  ✅ Created 12 invoices\n";
    echo "  📅 Due dates: {$invoiceYear}-07-10 to " . ($invoiceYear + 1) . "-06-10\n\n";
}

// Show summary
$totalInvoices = DB::table('spp_invoices')->count();
echo "\n=== SUMMARY ===\n";
echo "Total invoices in database: {$totalInvoices}\n";
echo "\n✅ DONE!\n";
