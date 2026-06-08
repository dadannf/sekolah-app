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
        // Update PTS and PAS costs
        DB::table('spp_tariffs')
            ->where('is_active', 1)
            ->update([
                'pts_cost' => 150000,
                'pas_cost' => 200000,
                // Set uniform type costs
                'uniform_batik_cost' => 100000,
                'uniform_olahraga_cost' => 150000,
                'uniform_muslim_cost' => 120000,
                'uniform_pramuka_cost' => 130000,
                'uniform_almamater_cost' => 150000,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('spp_tariffs')
            ->where('is_active', 1)
            ->update([
                'pts_cost' => 200000,
                'pas_cost' => 200000,
                'uniform_batik_cost' => null,
                'uniform_olahraga_cost' => null,
                'uniform_muslim_cost' => null,
                'uniform_pramuka_cost' => null,
                'uniform_almamater_cost' => null,
            ]);
    }
};
