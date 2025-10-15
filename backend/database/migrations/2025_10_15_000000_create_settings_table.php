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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
            $table->index('group');
        });
        
        DB::table('settings')->insert([
            [
                'key' => 'visit_price',
                'value' => '5000',
                'type' => 'integer',
                'group' => 'appointments',
                'description' => 'Prix fixe pour un rendez-vous de visite en FCFA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'XOF',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Devise utilisée pour l\'application',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'reminder_24h_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Activer les rappels 24h avant les rendez-vous',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'reminder_1h_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Activer les rappels 1h avant les rendez-vous',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_appointments_per_day',
                'value' => '10',
                'type' => 'integer',
                'group' => 'appointments',
                'description' => 'Nombre maximum de rendez-vous par jour',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
