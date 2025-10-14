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
        // MIGRATION: leases (contrats de location)
        // ============================================

        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade'); // client devenu locataire
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            
            // Dates
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            // Finances
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('deposit', 10, 2)->default(0); // ✅ Alias pour security_deposit
            $table->decimal('security_deposit', 10, 2)->nullable(); // ✅ Gardé pour compatibilité
            $table->decimal('agency_fees', 10, 2)->default(0); // Frais d'agence
            
            // Statut et conditions
            $table->string('status')->default('pending_approval'); // ✅ STRING au lieu d'ENUM
            // Valeurs possibles: pending_approval, active, terminated, expired
            
            $table->text('terms')->nullable();
            $table->integer('payment_day')->default(1); // Jour du mois pour paiement
            $table->boolean('auto_renew')->default(false);
            
            // Approbation
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index(['tenant_id', 'status']);
            $table->index(['property_id', 'status']);
            $table->index(['landlord_id', 'status']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};