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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('invoice_id')
                  ->constrained('spp_invoices')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            
            $table->foreignId('received_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onUpdate('cascade')
                  ->onDelete('set null');
            
            // Payment Info
            $table->dateTime('paid_at')->nullable();
            $table->unsignedInteger('amount');
            $table->enum('method', ['cash', 'transfer', 'qr'])->default('transfer');
            $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
            
            // Optional Fields
            $table->string('reference_no', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->string('place_paid', 100)->nullable();
            $table->text('note')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Unique Constraints
            $table->unique('invoice_id');
            
            // Indexes
            $table->index('status');
            $table->index('method');
            $table->index('paid_at');
        });
        
        // Add check constraint for amount > 0
        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
