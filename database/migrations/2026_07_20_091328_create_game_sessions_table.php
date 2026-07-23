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
    Schema::create('game_sessions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
        $table->foreignId('enemy_id')->constrained('enemies')->onDelete('cascade');
        $table->integer('enemy_current_hp');
        $table->integer('enemy_max_hp');
        $table->integer('total_damage')->default(0);
        $table->integer('rounds_played')->default(0);
        $table->enum('status', ['ongoing', 'won', 'lost'])->default('ongoing');
        $table->integer('points_earned')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
