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
    Schema::create('activity_results', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
        $table->decimal('score', 5, 2)->nullable();
        $table->integer('mistakes')->default(0);
        $table->integer('time_spent')->nullable();
        $table->integer('attempts')->default(1);
        $table->enum('status', ['in_progress', 'completed'])->default('completed');
        $table->dateTime('completed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_results');
    }
};
