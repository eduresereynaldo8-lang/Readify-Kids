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
    Schema::create('enemies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('sprite');          // emoji or image filename
        $table->integer('max_hp');
        $table->integer('level');          // 1, 2, or 3
        $table->string('description')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enemies');
    }
};
