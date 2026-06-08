<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T-02: Fix missing index on notifications.performed_by_id
 *
 * The migration `2026_04_13_update_notifications_add_user_info` had a bug:
 * line 30 re-checked `!Schema::hasColumn('notifications', 'performed_by_id')`
 * (duplicate of line 16) instead of checking for the index, so the index
 * was never created. This migration adds the missing index safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        if (!Schema::hasColumn('notifications', 'performed_by_id')) {
            return;
        }

        // Add index only if it doesn't already exist
        try {
            DB::statement(
                'ALTER TABLE `notifications` ADD INDEX `notifications_performed_by_id_index` (`performed_by_id`)'
            );
        } catch (\Throwable $e) {
            // Ignore if index already exists (duplicate key name error)
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        try {
            DB::statement(
                'ALTER TABLE `notifications` DROP INDEX `notifications_performed_by_id_index`'
            );
        } catch (\Throwable $e) {
            // Ignore if index does not exist
        }
    }
};
