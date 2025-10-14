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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('users')->onDelete('cascade');
            
            // Informations de base
            $table->string('title');
            $table->text('description');
            
            // Type et statut - ✅ CHANGÉ DE property_type à type
            $table->string('type'); // apartment, house, studio, villa, office, land, commercial
            $table->string('status')->default('available'); // available, rented, maintenance, unavailable
            
            // Localisation
            $table->string('address');
            $table->string('district')->nullable(); // quartier
            $table->string('city');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Caractéristiques
            $table->decimal('monthly_rent', 10, 2);
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->decimal('surface_area', 8, 2)->nullable(); // m²
            
            // Équipements et médias
            $table->json('amenities')->nullable(); // [wifi, parking, garden, etc.]
            $table->json('images')->nullable(); // URLs des images
            
            // Métadonnées
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index(['landlord_id', 'status']);
            $table->index(['city', 'type', 'status']);
            $table->index(['status', 'district', 'monthly_rent']);
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};