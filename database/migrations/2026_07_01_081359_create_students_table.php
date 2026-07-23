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
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
        $table->string('student_number', 50)->unique()->nullable();
        $table->string('firstname', 100)->nullable();
        $table->string('lastname', 100)->nullable();
        $table->string('section', 50)->nullable();
        $table->integer('current_level')->default(1);
        $table->integer('total_points')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
