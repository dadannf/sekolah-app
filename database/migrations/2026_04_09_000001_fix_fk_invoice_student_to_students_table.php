<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('spp_invoices') || !Schema::hasTable('students')) {
            return;
        }

        $databaseName = DB::connection()->getDatabaseName();

        $fkRow = DB::selectOne(
            "SELECT REFERENCED_TABLE_NAME AS referenced_table
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'spp_invoices'
               AND CONSTRAINT_NAME = 'fk_invoice_student'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$databaseName]
        );

        $referencedTable = $fkRow?->referenced_table;

        // Only fix when the FK exists and points to the wrong table.
        if (!$referencedTable || $referencedTable === 'students') {
            return;
        }

        // If the FK still points to a backup table like `students_old`, repoint it.
        DB::statement("ALTER TABLE `spp_invoices` DROP FOREIGN KEY `fk_invoice_student`");

        DB::statement(
            "ALTER TABLE `spp_invoices`
             ADD CONSTRAINT `fk_invoice_student`
             FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
             ON DELETE CASCADE ON UPDATE CASCADE"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('spp_invoices') || !Schema::hasTable('students')) {
            return;
        }

        $databaseName = DB::connection()->getDatabaseName();

        $fkRow = DB::selectOne(
            "SELECT REFERENCED_TABLE_NAME AS referenced_table
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'spp_invoices'
               AND CONSTRAINT_NAME = 'fk_invoice_student'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$databaseName]
        );

        $referencedTable = $fkRow?->referenced_table;

        if (!$referencedTable) {
            return;
        }

        // Safe rollback: just drop the FK if it points to `students`.
        if ($referencedTable === 'students') {
            DB::statement("ALTER TABLE `spp_invoices` DROP FOREIGN KEY `fk_invoice_student`");
        }
    }
};
