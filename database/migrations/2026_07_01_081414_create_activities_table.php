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
    Schema::create('activities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
        $table->foreignId('reading_material_id')->nullable()->constrained('reading_materials')->onDelete('set null');
        $table->string('activity_name', 255);
        $table->text('description')->nullable();
        $table->enum('activity_type', ['Phonics', 'Vocabulary', 'Word Recognition', 'Sound Blending', 'Read Aloud', 'Word Game']);
        $table->integer('level');
        $table->enum('difficulty_level', ['Easy', 'Medium', 'Hard'])->nullable();
        $table->integer('duration_minutes')->default(15);
        $table->integer('points_reward')->default(10);
        $table->boolean('is_published')->default(false);
        $table->boolean('allow_reattempt')->default(true);
        $table->boolean('adaptive_difficulty')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
