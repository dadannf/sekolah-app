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
        Schema::table('students', function (Blueprint $table) {
            // Add major column after current_grade_level if it doesn't exist
            if (!Schema::hasColumn('students', 'major')) {
                $table->string('major', 100)->nullable()->after('current_grade_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'major')) {
                $table->dropColumn('major');
            }
        });
    }
};
