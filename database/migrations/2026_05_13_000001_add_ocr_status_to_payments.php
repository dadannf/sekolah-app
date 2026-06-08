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
        Schema::table('payments', function (Blueprint $table) {
            // Add verified_by and verified_at if they don't exist
            if (!Schema::hasColumn('payments', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('bank_name');
            }
            
            // Add ocr_status to track OCR service status during transfer payment
            // Values: null (not applicable for cash), 'success' (OCR ran successfully),
            //         'unavailable' (OCR service down/timeout), 'failed' (OCR validation failed)
            if (!Schema::hasColumn('payments', 'ocr_status')) {
                $table->enum('ocr_status', ['success', 'unavailable', 'failed'])->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'ocr_status')) {
                $table->dropColumn('ocr_status');
            }
            
            if (Schema::hasColumn('payments', 'verified_by')) {
                $table->dropForeign(['verified_by']);
                $table->dropColumn('verified_by');
            }
            
            if (Schema::hasColumn('payments', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
        });
    }
};
