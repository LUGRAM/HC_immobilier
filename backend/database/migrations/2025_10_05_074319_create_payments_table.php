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
        // MIGRATION: payments (paiements)
        // ============================================
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Identifiants et relations
            $table->string('transaction_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // ✅ Relation polymorphique: peut être lié à Invoice, Appointment, etc.
            $table->morphs('payable'); // payable_id, payable_type
            
            // Informations de paiement
            $table->decimal('amount', 10, 2);
            $table->string('type'); // ✅ STRING au lieu d'ENUM
            // Valeurs: visit, rent, water, electricity, deposit, other
            
            $table->string('method'); // ✅ STRING au lieu d'ENUM
            // Valeurs: mobile_money, cash, bank_transfer, card
            
            $table->string('status')->default('pending'); // ✅ STRING au lieu d'ENUM
            // Valeurs: pending, processing, completed, failed, refunded, cancelled
            
            // Fournisseur de paiement (Mobile Money)
            $table->string('provider')->nullable(); // cinetpay, flutterwave, etc.
            $table->string('provider_transaction_id')->nullable();
            $table->string('operator')->nullable(); // ✅ MTN, Moov, Airtel
            $table->json('provider_response')->nullable();
            $table->string('phone_number')->nullable();
            
            // Dates
            $table->timestamp('completed_at')->nullable();
            $table->date('due_date')->nullable(); // ✅ Date d'échéance
            
            // Métadonnées
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // ✅ Données supplémentaires
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index(['user_id', 'status']);
            $table->index(['payable_type', 'payable_id']);
            $table->index('transaction_id');
            $table->index('provider_transaction_id');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};