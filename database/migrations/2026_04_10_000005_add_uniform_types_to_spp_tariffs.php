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
            // Add uniform types costs only if they don't exist
            if (!Schema::hasColumn('spp_tariffs', 'uniform_batik_cost')) {
                $table->integer('uniform_batik_cost')->unsigned()->nullable()->after('uniform_cost');
            }
            if (!Schema::hasColumn('spp_tariffs', 'uniform_olahraga_cost')) {
                $table->integer('uniform_olahraga_cost')->unsigned()->nullable()->after('uniform_batik_cost');
            }
            if (!Schema::hasColumn('spp_tariffs', 'uniform_muslim_cost')) {
                $table->integer('uniform_muslim_cost')->unsigned()->nullable()->after('uniform_olahraga_cost');
            }
            if (!Schema::hasColumn('spp_tariffs', 'uniform_pramuka_cost')) {
                $table->integer('uniform_pramuka_cost')->unsigned()->nullable()->after('uniform_muslim_cost');
            }
            if (!Schema::hasColumn('spp_tariffs', 'uniform_almamater_cost')) {
                $table->integer('uniform_almamater_cost')->unsigned()->nullable()->after('uniform_pramuka_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->dropColumn(['uniform_batik_cost', 'uniform_olahraga_cost', 'uniform_muslim_cost', 'uniform_pramuka_cost', 'uniform_almamater_cost']);
        });
    }
};
