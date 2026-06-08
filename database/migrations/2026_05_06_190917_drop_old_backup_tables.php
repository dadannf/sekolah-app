<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop all old backup tables created during refactoring process.
     * These tables contain duplicate/archived data no longer needed.
     */
    public function up(): void
    {
        // Disable foreign key checks temporarily to allow dropping referenced tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        Schema::dropIfExists('notifications_old');
        Schema::dropIfExists('ocr_payment_receipts_old');
        Schema::dropIfExists('payments_old');
        Schema::dropIfExists('spp_invoices_old');
        Schema::dropIfExists('students_old');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     * 
     * Note: Cannot recreate old backup tables as data is no longer available.
     * If rollback is needed, restore entire database from backup.
     */
    public function down(): void
    {
        // Intentionally empty - backup tables cannot be safely recreated
    }
};
