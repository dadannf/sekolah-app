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
        Schema::table('spp_tariffs', function (Blueprint $table) {
            // Add columns for additional fees only if they don't exist
            if (!Schema::hasColumn('spp_tariffs', 'uniform_cost')) {
                $table->unsignedInteger('uniform_cost')
                      ->after('amount')
                      ->nullable()
                      ->comment('Cost of school uniform (Rp)');
            }
            
            if (!Schema::hasColumn('spp_tariffs', 'pts_cost')) {
                $table->unsignedInteger('pts_cost')
                      ->after('uniform_cost')
                      ->nullable()
                      ->comment('PTS (Penilaian Tengah Semester) fee (Rp)');
            }
            
            if (!Schema::hasColumn('spp_tariffs', 'pas_cost')) {
                $table->unsignedInteger('pas_cost')
                      ->after('pts_cost')
                      ->nullable()
                      ->comment('PAS (Penilaian Akhir Semester) fee (Rp)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->dropColumn(['uniform_cost', 'pts_cost', 'pas_cost']);
        });
    }
};
