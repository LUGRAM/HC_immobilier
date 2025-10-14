<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // INDEXES SUPPLÉMENTAIRES & OPTIMISATIONS
        // ============================================
        // Ajouter ces indexes après création des tables pour performance

        // Full-text search sur properties
        DB::statement('ALTER TABLE properties ADD FULLTEXT search_index (title, description, address, district)');

        // Composite indexes pour requêtes fréquentes
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['property_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['lease_id', 'type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            //
        });
    }
};
