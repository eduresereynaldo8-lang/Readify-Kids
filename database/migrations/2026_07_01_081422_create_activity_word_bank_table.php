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
    Schema::create('activity_word_bank', function (Blueprint $table) {
        $table->id();
        $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
        $table->string('word', 100);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_word_bank');
    }
};
