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
// MIGRATION: expenses (dépenses quotidiennes client)
// ============================================
Schema::create('expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('category', ['food', 'transport', 'health', 'entertainment', 'shopping', 'other']);
    $table->string('description');
    $table->decimal('amount', 10, 2);
    $table->date('expense_date');
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['user_id', 'expense_date']);
    $table->index(['category', 'expense_date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
