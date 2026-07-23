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
    Schema::create('ml_predictions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('activity_id')->nullable()->constrained('activities')->onDelete('set null');
        $table->enum('predicted_level', ['Beginner', 'Developing', 'Proficient', 'Advanced'])->nullable();
        $table->decimal('prediction_confidence', 5, 2)->nullable();
        $table->enum('recommended_difficulty', ['Easy', 'Medium', 'Hard'])->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};
