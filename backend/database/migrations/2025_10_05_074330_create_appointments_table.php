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
Schema::create('appointments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('property_id')->constrained()->onDelete('cascade');
    $table->dateTime('scheduled_at');
    $table->enum('status', [
        'pending_payment',
        'paid',
        'confirmed',
        'completed',
        'cancelled',
        'no_show'
    ])->default('pending_payment');
    $table->decimal('amount_paid', 10, 2);
    $table->string('payment_reference')->nullable();
    $table->enum('payment_method', ['mobile_money', 'cash', 'bank_transfer'])->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('reminder_sent_at')->nullable();
    $table->text('client_notes')->nullable();
    $table->text('admin_notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['client_id', 'status']);
    $table->index(['scheduled_at']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
