<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Normalize legacy statuses.
        DB::table('payments')
            ->where('status', 'submitted')
            ->update(['status' => 'pending']);

        // Deduplicate by invoice_id, keep the latest row (highest id).
        DB::statement(
            "DELETE p FROM payments p "
            . "JOIN ("
            . "  SELECT invoice_id, MAX(id) AS keep_id, COUNT(*) AS c "
            . "  FROM payments "
            . "  GROUP BY invoice_id "
            . "  HAVING c > 1"
            . ") d ON p.invoice_id = d.invoice_id AND p.id <> d.keep_id"
        );

        // Drop redundant student_id to prevent inconsistency.
        if (Schema::hasColumn('payments', 'student_id')) {
            try {
                DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `fk_payments_student`');
            } catch (Throwable $e) {
                // Ignore if FK does not exist.
            }

            try {
                DB::statement('ALTER TABLE `payments` DROP INDEX `idx_student_id`');
            } catch (Throwable $e) {
                // Ignore if index does not exist.
            }

            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('student_id');
            });
        }

        // Enforce one payment per invoice.
        try {
            DB::statement('ALTER TABLE `payments` DROP INDEX `idx_invoice`');
        } catch (Throwable $e) {
            // Ignore if index does not exist.
        }

        try {
            DB::statement('ALTER TABLE `payments` ADD UNIQUE KEY `uq_payments_invoice_id` (`invoice_id`)');
        } catch (Throwable $e) {
            // Ignore if unique index already exists.
        }

        // Add missing foreign keys.
        try {
            DB::statement(
                'ALTER TABLE `payments` '
                . 'ADD CONSTRAINT `fk_payments_invoice` '
                . 'FOREIGN KEY (`invoice_id`) REFERENCES `spp_invoices` (`id`) '
                . 'ON DELETE CASCADE ON UPDATE CASCADE'
            );
        } catch (Throwable $e) {
            // Ignore if FK already exists.
        }

        try {
            DB::statement(
                'ALTER TABLE `payments` '
                . 'ADD CONSTRAINT `fk_payments_received_by` '
                . 'FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) '
                . 'ON DELETE SET NULL ON UPDATE CASCADE'
            );
        } catch (Throwable $e) {
            // Ignore if FK already exists.
        }

        try {
            DB::statement(
                'ALTER TABLE `payments` '
                . 'ADD CONSTRAINT `fk_payments_verified_by` '
                . 'FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) '
                . 'ON DELETE SET NULL ON UPDATE CASCADE'
            );
        } catch (Throwable $e) {
            // Ignore if FK already exists.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `fk_payments_invoice`');
        } catch (Throwable $e) {
            // Ignore if FK does not exist.
        }

        try {
            DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `fk_payments_received_by`');
        } catch (Throwable $e) {
            // Ignore if FK does not exist.
        }

        try {
            DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `fk_payments_verified_by`');
        } catch (Throwable $e) {
            // Ignore if FK does not exist.
        }

        try {
            DB::statement('ALTER TABLE `payments` DROP INDEX `uq_payments_invoice_id`');
        } catch (Throwable $e) {
            // Ignore if index does not exist.
        }

        try {
            DB::statement('ALTER TABLE `payments` ADD INDEX `idx_invoice` (`invoice_id`)');
        } catch (Throwable $e) {
            // Ignore if index already exists.
        }

        if (!Schema::hasColumn('payments', 'student_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('student_id')->nullable()->after('invoice_id');
                $table->index('student_id', 'idx_student_id');
            });

            try {
                DB::statement(
                    'ALTER TABLE `payments` '
                    . 'ADD CONSTRAINT `fk_payments_student` '
                    . 'FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) '
                    . 'ON DELETE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }
        }
    }
};
