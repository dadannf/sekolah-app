<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing tariffs to add default fees
        DB::table('spp_tariffs')->update([
            'uniform_cost' => 500000,  // Rp 500.000 untuk seragam
            'pts_cost' => 200000,       // Rp 200.000 untuk PTS
            'pas_cost' => 200000,       // Rp 200.000 untuk PAS
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('spp_tariffs')->update([
            'uniform_cost' => null,
            'pts_cost' => null,
            'pas_cost' => null,
        ]);
    }
};
