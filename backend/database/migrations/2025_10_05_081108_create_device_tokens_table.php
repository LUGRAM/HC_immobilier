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
// MIGRATION: device_tokens (pour notifications push)
// ============================================
Schema::create('device_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('token')->unique();
    $table->enum('platform', ['android', 'ios', 'web']);
    $table->string('device_name')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'platform']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
