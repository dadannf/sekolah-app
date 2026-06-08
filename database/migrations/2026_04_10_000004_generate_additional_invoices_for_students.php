<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Generate invoices untuk uniform, pts, pas untuk setiap siswa
     */
    public function up(): void
    {
        $currentYear = date('Y');
        
        // Get all active students
        $students = DB::table('students')
            ->where('student_status', 'active')
            ->get();

        // Get tariffs
        $tariffs = DB::table('spp_tariffs')
            ->where('is_active', 1)
            ->get()
            ->keyBy('grade_level');

        // Generate invoices for each invoice type
        $invoiceTypes = [
            'uniform' => ['month' => 1, 'dueDate' => null],      // Uniform during orientation (month 1)
            'pts' => ['month' => 5, 'dueDate' => null],          // PTS around mid-semester (month 5)
            'pas' => ['month' => 12, 'dueDate' => null],         // PAS end of year (month 12)
        ];

        foreach ($students as $student) {
            // Get student's current tariff
            $studentTariff = $tariffs->get($student->current_grade_level);
            
            if (!$studentTariff) {
                continue; // Skip if no tariff found
            }

            foreach ($invoiceTypes as $type => $config) {
                // Check if invoice already exists
                $existingInvoice = DB::table('spp_invoices')
                    ->where('student_id', $student->id)
                    ->where('invoice_year', $currentYear)
                    ->where('invoice_type', $type)
                    ->exists();

                if ($existingInvoice) {
                    continue; // Skip if already exists
                }

                // Determine amount based on type
                $column = $type . '_cost';
                $amount = $studentTariff->$column ?? 0;

                if ($amount <= 0) {
                    continue; // Skip if no cost defined
                }

                // Create invoice
                DB::table('spp_invoices')->insert([
                    'student_id' => $student->id,
                    'invoice_year' => $currentYear,
                    'invoice_month' => $config['month'],
                    'grade_level_at_invoice' => $student->current_grade_level,
                    'tariff_id' => $studentTariff->id,
                    'invoice_type' => $type,
                    'amount_due' => (int)$amount,
                    'due_date' => $config['dueDate'],
                    'status' => 'unpaid',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $currentYear = date('Y');
        
        // Delete invoices for non-SPP types
        DB::table('spp_invoices')
            ->where('invoice_year', $currentYear)
            ->whereIn('invoice_type', ['uniform', 'pts', 'pas'])
            ->delete();
    }
};
