<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('spp_invoices')) {
            return;
        }

        // 1) Add column invoice_subtype
        Schema::table('spp_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('spp_invoices', 'invoice_subtype')) {
                $table->string('invoice_subtype', 50)
                    ->default('')
                    ->after('invoice_type');

                $table->index('invoice_subtype', 'idx_invoice_subtype');
            }
        });

        // 2) Backfill subtype for existing uniform invoices
        try {
            DB::table('spp_invoices')
                ->where('invoice_type', 'uniform')
                ->whereNotNull('reference_no')
                ->where(function ($q) {
                    $q->whereNull('invoice_subtype')->orWhere('invoice_subtype', '');
                })
                ->update([
                    'invoice_subtype' => DB::raw('reference_no'),
                ]);
        } catch (Throwable $e) {
            // Best-effort backfill; do not fail migration
            \Log::warning('Backfill invoice_subtype warning: ' . $e->getMessage());
        }

        // 3) Replace unique key to include invoice_subtype
        try {
            DB::statement('ALTER TABLE `spp_invoices` DROP INDEX `uq_invoice_student_period_type`');
        } catch (Throwable $e) {
            // ignore if doesn't exist
        }

        DB::statement(
            'ALTER TABLE `spp_invoices` ' .
            'ADD UNIQUE KEY `uq_invoice_student_period_type_subtype` ' .
            '(`student_id`, `invoice_year`, `invoice_month`, `invoice_type`, `invoice_subtype`)' 
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('spp_invoices')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `spp_invoices` DROP INDEX `uq_invoice_student_period_type_subtype`');
        } catch (Throwable $e) {
            // ignore
        }

        // Restore old unique key
        try {
            DB::statement(
                'ALTER TABLE `spp_invoices` ' .
                'ADD UNIQUE KEY `uq_invoice_student_period_type` ' .
                '(`student_id`, `invoice_year`, `invoice_month`, `invoice_type`)' 
            );
        } catch (Throwable $e) {
            // ignore
        }

        Schema::table('spp_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('spp_invoices', 'invoice_subtype')) {
                try {
                    $table->dropIndex('idx_invoice_subtype');
                } catch (Throwable $e) {
                    // ignore
                }
                $table->dropColumn('invoice_subtype');
            }
        });
    }
};
