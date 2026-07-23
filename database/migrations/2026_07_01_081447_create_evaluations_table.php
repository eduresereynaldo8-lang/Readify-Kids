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
    Schema::create('evaluations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
        $table->foreignId('recording_id')->constrained('voice_recordings')->onDelete('cascade');
        $table->integer('pronunciation_score')->nullable();
        $table->integer('fluency_score')->nullable();
        $table->integer('accuracy_score')->nullable();
        $table->integer('comprehension_score')->nullable();
        $table->enum('proficiency_level', ['Beginner', 'Developing', 'Proficient', 'Advanced'])->nullable();
        $table->text('feedback')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
