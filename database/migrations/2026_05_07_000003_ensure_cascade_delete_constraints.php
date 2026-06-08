<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Ensure spp_invoices has proper cascade delete constraint to students
        if (Schema::hasTable('spp_invoices') && Schema::hasTable('students')) {
            // Check if constraint exists
            $constraintExists = DB::select("
                SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'spp_invoices' 
                AND COLUMN_NAME = 'student_id'
                AND REFERENCED_TABLE_NAME = 'students'
                AND CONSTRAINT_NAME = 'fk_invoice_student'
            ");

            if (empty($constraintExists)) {
                // Drop old constraint if exists
                try {
                    Schema::table('spp_invoices', function (Blueprint $table) {
                        $table->dropForeign(['student_id']);
                    });
                } catch (\Exception $e) {
                    // Constraint doesn't exist, ignore
                }

                // Add new constraint with cascade delete
                Schema::table('spp_invoices', function (Blueprint $table) {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('students')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
                });
            }
        }

        // Ensure payments has proper cascade delete constraint to spp_invoices
        if (Schema::hasTable('payments') && Schema::hasTable('spp_invoices')) {
            // Check if constraint exists
            $constraintExists = DB::select("
                SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'payments' 
                AND COLUMN_NAME = 'invoice_id'
                AND REFERENCED_TABLE_NAME = 'spp_invoices'
                AND CONSTRAINT_NAME = 'fk_payments_invoice'
            ");

            if (empty($constraintExists)) {
                // Drop old constraint if exists
                try {
                    Schema::table('payments', function (Blueprint $table) {
                        $table->dropForeign(['invoice_id']);
                    });
                } catch (\Exception $e) {
                    // Constraint doesn't exist, ignore
                }

                // Add new constraint with cascade delete
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreign('invoice_id')
                        ->references('id')
                        ->on('spp_invoices')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');
                });
            }
        }

        // Check for and clean up any orphaned invoices (student_id references non-existent students)
        if (Schema::hasTable('spp_invoices') && Schema::hasTable('students')) {
            $orphanedCount = DB::select("
                SELECT COUNT(*) as count FROM spp_invoices si
                LEFT JOIN students s ON si.student_id = s.id
                WHERE s.id IS NULL
            ");

            if ($orphanedCount[0]->count > 0) {
                DB::select("
                    DELETE FROM spp_invoices
                    WHERE student_id NOT IN (SELECT id FROM students WHERE id IS NOT NULL)
                ");
            }
        }

        // Check for and clean up any orphaned payments (invoice_id references non-existent invoices)
        if (Schema::hasTable('payments') && Schema::hasTable('spp_invoices')) {
            $orphanedCount = DB::select("
                SELECT COUNT(*) as count FROM payments p
                LEFT JOIN spp_invoices si ON p.invoice_id = si.id
                WHERE si.id IS NULL
            ");

            if ($orphanedCount[0]->count > 0) {
                DB::select("
                    DELETE FROM payments
                    WHERE invoice_id NOT IN (SELECT id FROM spp_invoices WHERE id IS NOT NULL)
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only ensures constraints exist, so no specific rollback needed
        // The constraints are essential for data integrity
    }
};
