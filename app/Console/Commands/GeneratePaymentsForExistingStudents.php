<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Grade;

class GeneratePaymentsForExistingStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:generate {--force : Force regenerate even if payments exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate payment records for existing students who don\'t have payment data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting payment generation for existing students...');
        
        $force = $this->option('force');
        
        // Get all students with active enrollment
        $students = Student::with(['activeEnrollment.grade', 'activeEnrollment.academicYear'])
            ->whereHas('activeEnrollment')
            ->get();
        
        if ($students->isEmpty()) {
            $this->warn('No students with active enrollment found!');
            return 0;
        }

        $this->info("Found {$students->count()} students with active enrollment.");
        
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();

        foreach ($students as $student) {
            try {
                $enrollment = $student->activeEnrollment;
                
                if (!$enrollment || !$enrollment->grade || !$enrollment->academic_year_id) {
                    $this->newLine();
                    $this->warn("Skipping {$student->name} ({$student->nis}) - Missing enrollment data");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Check if payment already exists
                $existingPayment = Payment::where('student_id', $student->id)
                    ->where('academic_year_id', $enrollment->academic_year_id)
                    ->first();

                if ($existingPayment && !$force) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                if ($existingPayment && $force) {
                    $existingPayment->delete();
                    $this->newLine();
                    $this->info("Deleted existing payment for {$student->name}");
                }

                // Get grade level
                $grade = $enrollment->grade;
                $gradeLevel = (int) $grade->grade_code;
                
                // Calculate SPP
                if ($gradeLevel == 10) {
                    $sppPerBulan = 200000;
                    $totalTagihan = $sppPerBulan * 12; // Rp 2.400.000
                    $description = "SPP Tahun Ajaran - Kelas 10 (Rp 200.000 x 12 bulan)";
                } else {
                    $sppPerBulan = 190000;
                    $totalTagihan = $sppPerBulan * 12; // Rp 2.280.000
                    $description = "SPP Tahun Ajaran - Kelas {$gradeLevel} (Rp 190.000 x 12 bulan)";
                }

                // Create payment record
                Payment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $enrollment->academic_year_id,
                    'total_bill' => $totalTagihan,
                    'total_paid' => 0,
                    'status' => 'active',
                    'description' => $description,
                ]);

                $created++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing {$student->name}: {$e->getMessage()}");
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Payment Generation Summary ===');
        $this->info("✅ Created: {$created} payment records");
        $this->info("⏭️  Skipped: {$skipped} (already exist)");
        
        if ($errors > 0) {
            $this->error("❌ Errors: {$errors}");
        }

        $this->newLine();
        $this->info('Done!');

        return 0;
    }
}
