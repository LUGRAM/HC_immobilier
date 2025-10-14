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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('category', [
                'plumbing',      // Plomberie
                'electrical',    // Électricité
                'hvac',          // Climatisation/Chauffage
                'appliance',     // Électroménager
                'structural',    // Structure (murs, toit, etc.)
                'security',      // Sécurité
                'other'          // Autre
            ])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->json('images')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Technicien/prestataire
            $table->timestamps();
            $table->softDeletes();

            // Index pour performances
            $table->index(['tenant_id', 'status']);
            $table->index(['property_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};