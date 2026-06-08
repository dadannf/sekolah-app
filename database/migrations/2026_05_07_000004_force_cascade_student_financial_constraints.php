<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasTable('spp_invoices')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $databaseName = DB::connection()->getDatabaseName();

            $invoiceConstraint = DB::selectOne(
                "SELECT rc.DELETE_RULE AS delete_rule,
                        kcu.REFERENCED_TABLE_NAME AS referenced_table
                 FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                 JOIN information_schema.KEY_COLUMN_USAGE kcu
                   ON rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
                  AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                  AND rc.TABLE_NAME = kcu.TABLE_NAME
                 WHERE rc.CONSTRAINT_SCHEMA = ?
                   AND rc.TABLE_NAME = 'spp_invoices'
                   AND rc.CONSTRAINT_NAME = 'fk_invoice_student'
                 LIMIT 1",
                [$databaseName]
            );

            $needsInvoiceFix = !$invoiceConstraint
                || strtolower((string) $invoiceConstraint->delete_rule) !== 'cascade'
                || $invoiceConstraint->referenced_table !== 'students';

            if ($needsInvoiceFix) {
                try {
                    DB::statement('ALTER TABLE `spp_invoices` DROP FOREIGN KEY `fk_invoice_student`');
                } catch (\Throwable $e) {
                    // Ignore if the constraint does not exist yet.
                }

                DB::statement(
                    'ALTER TABLE `spp_invoices` '
                    . 'ADD CONSTRAINT `fk_invoice_student` '
                    . 'FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) '
                    . 'ON DELETE CASCADE ON UPDATE CASCADE'
                );
            }

            if (Schema::hasTable('payments') && Schema::hasTable('spp_invoices')) {
                $paymentConstraint = DB::selectOne(
                    "SELECT rc.DELETE_RULE AS delete_rule,
                            kcu.REFERENCED_TABLE_NAME AS referenced_table
                     FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                     JOIN information_schema.KEY_COLUMN_USAGE kcu
                       ON rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
                      AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                      AND rc.TABLE_NAME = kcu.TABLE_NAME
                     WHERE rc.CONSTRAINT_SCHEMA = ?
                       AND rc.TABLE_NAME = 'payments'
                       AND rc.CONSTRAINT_NAME = 'fk_payments_invoice'
                     LIMIT 1",
                    [$databaseName]
                );

                $needsPaymentFix = !$paymentConstraint
                    || strtolower((string) $paymentConstraint->delete_rule) !== 'cascade'
                    || $paymentConstraint->referenced_table !== 'spp_invoices';

                if ($needsPaymentFix) {
                    try {
                        DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `fk_payments_invoice`');
                    } catch (\Throwable $e) {
                        // Ignore if the constraint does not exist yet.
                    }

                    DB::statement(
                        'ALTER TABLE `payments` '
                        . 'ADD CONSTRAINT `fk_payments_invoice` '
                        . 'FOREIGN KEY (`invoice_id`) REFERENCES `spp_invoices` (`id`) '
                        . 'ON DELETE CASCADE ON UPDATE CASCADE'
                    );
                }
            }

            // Clean orphaned invoices from older data before cascade existed.
            DB::statement(
                'DELETE si FROM `spp_invoices` si '
                . 'LEFT JOIN `students` s ON si.`student_id` = s.`id` '
                . 'WHERE s.`id` IS NULL'
            );

            if (Schema::hasTable('payments') && Schema::hasTable('spp_invoices')) {
                // Clean orphaned payments from older data.
                DB::statement(
                    'DELETE p FROM `payments` p '
                    . 'LEFT JOIN `spp_invoices` si ON p.`invoice_id` = si.`id` '
                    . 'WHERE si.`id` IS NULL'
                );
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // Keep the cascade constraints in place; they are required for data integrity.
    }
};
