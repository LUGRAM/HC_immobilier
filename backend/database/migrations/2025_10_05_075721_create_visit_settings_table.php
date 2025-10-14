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
Schema::create('visit_settings', function (Blueprint $table) {
    $table->id();
    $table->decimal('visit_price', 10, 2)->default(5000); // Prix fixe visite
    $table->integer('reminder_hours_before')->default(24); // Rappel 24h avant
    $table->boolean('auto_reminders_enabled')->default(true);
    $table->json('available_time_slots')->nullable(); // [9:00, 10:00, 14:00, etc.]
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_settings');
    }
};
