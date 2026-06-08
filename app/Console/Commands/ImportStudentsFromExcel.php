<?php

namespace App\Console\Commands;

use App\Services\StudentExcelImporter;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportStudentsFromExcel extends Command
{
    protected $signature = 'students:import-excel
        {file : Path file .xlsx}
        {--sheet=0 : Nama sheet atau index (0-based)}
        {--header-row= : Nomor baris header (1-based). Jika kosong, auto-detect}
        {--debug : Tampilkan info sheet/header, tanpa mengubah DB}
        {--dry-run : Tanpa menyimpan ke database (hanya preview)}';

    protected $description = 'Import data siswa dari Excel (format SMK)';

    public function handle(): int
    {
        $filePath = (string) $this->argument('file');
        $sheetOption = $this->option('sheet');
        $headerRowOption = $this->option('header-row');
        $debug = (bool) $this->option('debug');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $this->info('Membaca file Excel...');

        if ($debug) {
            $this->warn('DEBUG MODE: hanya preview, tidak menulis DB.');
            $dryRun = true;

            try {
                $spreadsheet = IOFactory::load($filePath);
                $this->line('Ringkasan semua sheet (title | highestRow | highestCol):');
                foreach ($spreadsheet->getWorksheetIterator() as $ws) {
                    $this->line('- ' . $ws->getTitle() . ' | ' . $ws->getHighestDataRow() . ' | ' . $ws->getHighestDataColumn());
                }
                $this->newLine();
            } catch (\Throwable $e) {
                $this->warn('Tidak bisa baca ringkasan sheet: ' . $e->getMessage());
            }
        }

        $this->info($dryRun ? 'Mode DRY RUN: tidak ada data yang disimpan.' : 'Mulai import...');

        $importer = app(StudentExcelImporter::class);

        try {
            $result = $importer->import($filePath, [
                'sheet' => $sheetOption,
                'header_row' => ($headerRowOption !== null && $headerRowOption !== '') ? (int) $headerRowOption : null,
                'dry_run' => $dryRun,
                'default_grade_level' => 10,
                'default_student_status' => 'active',
            ]);
        } catch (\Throwable $e) {
            $this->error('Gagal import: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($debug) {
            $this->line('Sheet        : ' . $result['sheet_title']);
            $this->line('Header row   : ' . $result['header_row']);
            $this->line('Highest row  : ' . $result['highest_row']);
            $this->line('Highest col  : ' . $result['highest_col']);
        }

        $this->newLine();
        $this->info('Selesai.');
        $this->line('Created: ' . $result['created']);
        $this->line('Updated: ' . $result['updated']);
        $this->line('Skipped: ' . $result['skipped']);
        $this->line('Errors : ' . $result['errors']);

        if ($debug && !empty($result['error_messages'])) {
            $this->newLine();
            $this->warn('Error messages (first 10):');
            foreach (array_slice($result['error_messages'], 0, 10) as $msg) {
                $this->line('- ' . $msg);
            }
        }

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
