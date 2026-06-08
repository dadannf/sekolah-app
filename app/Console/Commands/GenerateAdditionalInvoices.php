<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAdditionalInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-additional {--year=} {--type=} {--force}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Generate additional invoices (uniform, pts, pas) for all active students';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year') ?? date('Y');
        $type = $this->option('type');
        $force = $this->option('force');

        // Valid invoice types
        $validTypes = ['uniform', 'pts', 'pas'];
        $typesToGenerate = $type ? [$type] : $validTypes;

        // Validate type if specified
        if ($type && !in_array($type, $validTypes)) {
            $this->error("Invalid invoice type. Must be one of: " . implode(', ', $validTypes));
            return 1;
        }

        $this->info("Generating additional invoices for year {$year}...");
        $this->info("Types to generate: " . implode(', ', $typesToGenerate));

        // Check if invoices already exist
        $existingCount = DB::table('spp_invoices')
            ->where('invoice_year', $year)
            ->whereIn('invoice_type', $typesToGenerate)
            ->count();

        if ($existingCount > 0 && !$force) {
            $this->warn("Found {$existingCount} existing invoices for these types in {$year}");
            if (!$this->confirm('Continue? This may create duplicates', false)) {
                $this->info('Aborted');
                return 1;
            }
        }

        $config = [
            'uniform' => ['month' => 1, 'label' => 'Seragam'],
            'pts' => ['month' => 5, 'label' => 'PTS (Penilaian Tengah Semester)'],
            'pas' => ['month' => 12, 'label' => 'PAS (Penilaian Akhir Semester)'],
        ];

        $totalCreated = 0;
        $bar = $this->output->createProgressBar(count($typesToGenerate));

        foreach ($typesToGenerate as $invoiceType) {
            $cfg = $config[$invoiceType];
            
            // Get all active students with their tariffs
            $invoices = DB::table('students')
                ->where('student_status', 'active')
                ->whereNotNull('current_grade_level')
                ->join('spp_tariffs', function ($join) {
                    $join->on('students.current_grade_level', '=', 'spp_tariffs.grade_level')
                        ->where('spp_tariffs.is_active', 1);
                })
                ->select(
                    'students.id as student_id',
                    'students.current_grade_level',
                    'spp_tariffs.id as tariff_id',
                    DB::raw($cfg['month'] . ' as invoice_month'),
                    DB::raw("'{$invoiceType}' as invoice_type"),
                    'spp_tariffs.' . $invoiceType . '_cost'
                )
                ->whereRaw("`spp_tariffs`.`{$invoiceType}_cost` > 0")
                ->get();

            $batchInsert = [];
            foreach ($invoices as $record) {
                // Check if already exists
                $exists = DB::table('spp_invoices')
                    ->where('student_id', $record->student_id)
                    ->where('invoice_year', $year)
                    ->where('invoice_month', $record->invoice_month)
                    ->where('invoice_type', $invoiceType)
                    ->where('invoice_subtype', '')
                    ->exists();

                if (!$exists) {
                    $batchInsert[] = [
                        'student_id' => $record->student_id,
                        'invoice_year' => $year,
                        'invoice_month' => $record->invoice_month,
                        'grade_level_at_invoice' => $record->current_grade_level,
                        'tariff_id' => $record->tariff_id,
                        'invoice_type' => $invoiceType,
                        'invoice_subtype' => '',
                        'amount_due' => $record->{$invoiceType . '_cost'},
                        'status' => 'unpaid',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (count($batchInsert) > 0) {
                DB::table('spp_invoices')->insert($batchInsert);
                $totalCreated += count($batchInsert);
                $this->line("\n  ✓ {$cfg['label']}: " . count($batchInsert) . " invoices created");
            } else {
                $this->line("\n  ✓ {$cfg['label']}: No new invoices created");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\n✓ Total invoices created: {$totalCreated}");

        return 0;
    }
}
