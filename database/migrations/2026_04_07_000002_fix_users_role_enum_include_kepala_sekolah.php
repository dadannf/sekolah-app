<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        try {
            $driver = DB::connection()->getDriverName();
        } catch (Throwable $e) {
            return;
        }

        if ($driver !== 'mysql') {
            return;
        }

        try {
            $columns = DB::select("SHOW COLUMNS FROM `users` WHERE Field = 'role'");
            $type = $columns[0]->Type ?? '';

            if (!is_string($type)) {
                return;
            }

            // If it's an ENUM, make sure kepala_sekolah is included.
            if (str_starts_with(strtolower($type), 'enum(')) {
                $lowerType = strtolower($type);
                if (!str_contains($lowerType, "'kepala_sekolah'")) {
                    DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','kepala_sekolah','siswa') NOT NULL DEFAULT 'siswa'");
                }
            }
        } catch (Throwable $e) {
            // Best-effort migration.
        }
    }

    public function down(): void
    {
        // No-op: do not attempt to remove enum values.
    }
};
