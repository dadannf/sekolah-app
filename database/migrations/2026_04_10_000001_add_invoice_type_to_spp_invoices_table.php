<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spp_invoices', function (Blueprint $table) {
            // Add invoice_type column if it doesn't exist
            if (!Schema::hasColumn('spp_invoices', 'invoice_type')) {
                $table->enum('invoice_type', ['spp', 'uniform', 'pts', 'pas'])
                      ->after('tariff_id')
                      ->default('spp')
                      ->comment('Type of invoice: spp (monthly), uniform (seragam), pts (penilaian tengah semester), pas (penilaian akhir semester)');

                // Add index for faster queries by type
                $table->index('invoice_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spp_invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_type']);
            $table->dropColumn('invoice_type');
        });
    }
};
