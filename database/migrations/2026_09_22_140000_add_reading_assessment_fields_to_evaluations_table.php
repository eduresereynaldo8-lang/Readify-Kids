<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->unsignedInteger('reading_minutes')->nullable();
            $table->unsignedInteger('reading_seconds')->nullable();
            $table->unsignedInteger('total_reading_seconds')->nullable();
            $table->unsignedInteger('total_words')->nullable();
            $table->unsignedInteger('miscues')->nullable();
            $table->decimal('oral_reading_score', 5, 2)->nullable();
            $table->unsignedInteger('correct_answers')->nullable();
            $table->unsignedInteger('total_questions')->nullable();
            $table->decimal('comprehension_percentage', 5, 2)->nullable();
            $table->unsignedTinyInteger('observation_level')->nullable();
            $table->unsignedTinyInteger('learner_experience')->nullable();
        });
    }

    public function down(): void
    {
        // Rollback discards only the new rubric; historical columns remain intact.
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'reading_minutes', 'reading_seconds', 'total_reading_seconds',
                'total_words', 'miscues', 'oral_reading_score', 'correct_answers',
                'total_questions', 'comprehension_percentage', 'observation_level',
                'learner_experience',
            ]);
        });
    }
};
