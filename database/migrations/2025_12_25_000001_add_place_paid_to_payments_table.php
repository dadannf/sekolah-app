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
            // Add place_paid column if it doesn't exist
            if (!Schema::hasColumn('payments', 'place_paid')) {
                $table->string('place_paid', 100)->nullable()->after('bank_name');
            }
            
            // Rename notes to note if notes exists
            if (Schema::hasColumn('payments', 'notes') && !Schema::hasColumn('payments', 'note')) {
                $table->renameColumn('notes', 'note');
            } elseif (!Schema::hasColumn('payments', 'note')) {
                $table->text('note')->nullable()->after('place_paid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'place_paid')) {
                $table->dropColumn('place_paid');
            }
            
            if (Schema::hasColumn('payments', 'note') && !Schema::hasColumn('payments', 'notes')) {
                $table->renameColumn('note', 'notes');
            }
        });
    }
};
