<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix: Update unique constraint to include invoice_type column
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Check if new constraint already exists
            $newConstraints = DB::select(<<<SQL
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'spp_invoices' 
                AND CONSTRAINT_NAME = 'uq_invoice_student_period_type'
                LIMIT 1
            SQL);
            
            if (empty($newConstraints)) {
                // New constraint doesn't exist, so we need to create it
                // First, check if old constraint exists and drop it
                $oldConstraints = DB::select(<<<SQL
                    SELECT CONSTRAINT_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'spp_invoices' 
                    AND CONSTRAINT_NAME = 'uq_invoice_student_period'
                    AND COLUMN_NAME = 'student_id'
                    LIMIT 1
                SQL);
                
                if (!empty($oldConstraints)) {
                    DB::statement('ALTER TABLE `spp_invoices` DROP INDEX `uq_invoice_student_period`');
                }
                
                // Create new constraint dengan invoice_type
                DB::statement('ALTER TABLE `spp_invoices` ADD UNIQUE KEY `uq_invoice_student_period_type` 
                    (`student_id`, `invoice_year`, `invoice_month`, `invoice_type`)');
            }
        } catch (\Exception $e) {
            // Log but don't fail
            \Log::warning('Migration warning: ' . $e->getMessage());
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint
        DB::statement('ALTER TABLE `spp_invoices` DROP INDEX IF EXISTS `uq_invoice_student_period_type`');
        
        // Restore old constraint
        DB::statement('ALTER TABLE `spp_invoices` ADD UNIQUE KEY `uq_invoice_student_period` 
            (`student_id`, `invoice_year`, `invoice_month`)');
    }
};
