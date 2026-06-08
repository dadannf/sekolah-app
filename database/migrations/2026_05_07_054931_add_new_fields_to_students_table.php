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
            // NIK (Nomor Identitas Nasional)
            $table->string('nik', 20)->nullable()->after('nisn');
            
            // Uniform size (ukuran seragam)
            $table->string('uniform_size', 10)->nullable()->after('religion');
            
            // Previous school (asal sekolah)
            $table->string('previous_school', 255)->nullable()->after('major');
            
            // Phone number (nomor telephone)
            $table->string('phone_number', 20)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['nik', 'uniform_size', 'previous_school', 'phone_number']);
        });
    }
};
