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
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('notifications', 'performed_by_id')) {
                $table->unsignedBigInteger('performed_by_id')->nullable()->after('user_id');
                $table->foreign('performed_by_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('notifications', 'performed_by_name')) {
                $table->string('performed_by_name')->nullable()->after('performed_by_id');
            }

            if (!Schema::hasColumn('notifications', 'changes')) {
                $table->json('changes')->nullable()->after('data');
            }

            // Add index for performed_by_id if it doesn't exist
            if (!Schema::hasColumn('notifications', 'performed_by_id')) {
                $table->index('performed_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeignIfExists('notifications_performed_by_id_foreign');
            $table->dropIndexIfExists('notifications_performed_by_id_index');
            $table->dropColumnIfExists('performed_by_id');
            $table->dropColumnIfExists('performed_by_name');
            $table->dropColumnIfExists('changes');
        });
    }
};
