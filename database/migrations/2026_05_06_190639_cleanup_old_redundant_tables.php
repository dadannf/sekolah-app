<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop redundant tables that have been consolidated:
     * Order is critical due to FK dependencies:
     * 1. Drop additional_fee_payments (FK → additional_fee_invoices)
     * 2. Drop additional_fee_invoices (FK → students_old)
     * 3. Drop student_payments (if has FK)
     * 4. Drop old backup tables
     */
    public function up(): void
    {
        // Drop redundant tables with FKs - must follow dependency order
        Schema::dropIfExists('additional_fee_payments');
        Schema::dropIfExists('additional_fee_invoices');
        Schema::dropIfExists('student_payments');
        
        // Drop backup tables (safe after migration verified)
        Schema::dropIfExists('students_old');
        Schema::dropIfExists('spp_invoices_old');
        Schema::dropIfExists('payments_old');
        Schema::dropIfExists('ocr_payment_receipts_old');
        Schema::dropIfExists('notifications_old');
    }

    /**
     * Reverse the migrations.
     * 
     * Note: Cannot recreate old backup tables or consolidation mapping.
     * This migration is destructive and should only be rolled back if absolutely necessary
     * by restoring the entire database from backup.
     */
    public function down(): void
    {
        // Intentionally empty - cannot safely restore consolidated data
        // If rollback is needed, restore database from backup instead
    }
};
