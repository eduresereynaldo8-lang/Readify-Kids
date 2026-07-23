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
    Schema::create('game_rounds', function (Blueprint $table) {
        $table->id();
        $table->foreignId('game_session_id')->constrained('game_sessions')->onDelete('cascade');
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->string('word_or_passage', 1000);   // what the student read
        $table->string('recording_path')->nullable();
        $table->decimal('ml_score', 5, 2)->nullable();
        $table->decimal('teacher_score', 5, 2)->nullable();
        $table->decimal('final_score', 5, 2)->nullable(); // teacher overrides ml
        $table->integer('damage_dealt')->default(0);
        $table->enum('status', ['pending', 'ml_scored', 'teacher_reviewed'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};
