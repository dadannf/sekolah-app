<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        // If role column doesn't exist (fresh Laravel users table), add it.
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 32)->default('siswa')->after('password');
            });
            return;
        }

        // If role is an ENUM in MySQL, extend it to include kepala_sekolah.
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

            // Only touch the schema if it's an ENUM (so we don't accidentally convert varchar -> enum).
            if (is_string($type) && str_starts_with(strtolower($type), 'enum(')) {
                DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','kepala_sekolah','siswa') NOT NULL DEFAULT 'siswa'");
            }
        } catch (Throwable $e) {
            // Best-effort migration: do not block deploy if DB doesn't allow SHOW COLUMNS / ALTER.
        }
    }

    public function down(): void
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

            if (is_string($type) && str_starts_with(strtolower($type), 'enum(')) {
                DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('admin','siswa') NOT NULL DEFAULT 'siswa'");
            }
        } catch (Throwable $e) {
            // Best-effort rollback
        }
    }
};
