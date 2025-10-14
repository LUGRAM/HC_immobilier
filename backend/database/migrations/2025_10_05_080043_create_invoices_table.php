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
// ============================================
// MIGRATION: invoices (factures)
// ============================================
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('invoice_number')->unique();
    $table->foreignId('lease_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
    $table->enum('type', ['rent', 'water', 'electricity', 'other']);
    $table->string('description');
    $table->decimal('amount', 10, 2);
    $table->date('due_date');
    $table->date('period_start')->nullable(); // Période facturée
    $table->date('period_end')->nullable();
    $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
    $table->timestamp('paid_at')->nullable();
    $table->decimal('amount_paid', 10, 2)->default(0);
    $table->string('payment_reference')->nullable();
    $table->enum('payment_method', ['mobile_money', 'cash', 'bank_transfer'])->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['tenant_id', 'status', 'due_date']);
    $table->index(['invoice_number']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
