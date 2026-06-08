<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Student;
use App\Models\AcademicYear;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get all students and active academic year
        $students = Student::all();
        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            $this->command->error('No active academic year found!');
            return;
        }

        foreach ($students as $student) {
            // Random payment scenarios
            $scenarios = [
                // Lunas (100% paid)
                [
                    'total_bill' => 5000000,
                    'total_paid' => 5000000,
                    'status' => 'completed',
                    'description' => 'Pembayaran SPP dan biaya sekolah tahun ajaran ' . $academicYear->year_label
                ],
                // Cicilan (50% paid)
                [
                    'total_bill' => 6000000,
                    'total_paid' => 3000000,
                    'status' => 'active',
                    'description' => 'Pembayaran SPP dan biaya sekolah tahun ajaran ' . $academicYear->year_label . ' - Cicilan'
                ],
                // Cicilan (75% paid)
                [
                    'total_bill' => 4500000,
                    'total_paid' => 3375000,
                    'status' => 'active',
                    'description' => 'Pembayaran SPP dan biaya sekolah tahun ajaran ' . $academicYear->year_label . ' - Cicilan'
                ],
                // Belum bayar (0% paid)
                [
                    'total_bill' => 5500000,
                    'total_paid' => 0,
                    'status' => 'active',
                    'description' => 'Pembayaran SPP dan biaya sekolah tahun ajaran ' . $academicYear->year_label . ' - Belum bayar'
                ],
                // Cicilan (25% paid)
                [
                    'total_bill' => 5000000,
                    'total_paid' => 1250000,
                    'status' => 'active',
                    'description' => 'Pembayaran SPP dan biaya sekolah tahun ajaran ' . $academicYear->year_label . ' - Cicilan'
                ],
            ];

            // Randomly select a scenario
            $scenario = $scenarios[array_rand($scenarios)];

            Payment::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'total_bill' => $scenario['total_bill'],
                'total_paid' => $scenario['total_paid'],
                'status' => $scenario['status'],
                'description' => $scenario['description'],
            ]);
        }

        $this->command->info('Payment records created successfully!');
    }
}
