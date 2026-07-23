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
    Schema::create('voice_recordings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
        $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
        $table->string('recording_path', 255);
        $table->integer('attempt_number')->default(1);
        $table->enum('status', ['pending', 'evaluated'])->default('pending');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_recordings');
    }
};
