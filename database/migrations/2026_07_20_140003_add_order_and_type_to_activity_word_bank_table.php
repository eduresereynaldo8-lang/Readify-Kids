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
    Schema::table('activity_word_bank', function (Blueprint $table) {
        $table->text('word')->change(); // allow longer text for paragraphs
        $table->integer('order')->default(0)->after('word');
        $table->enum('type', ['word', 'phrase', 'paragraph'])->default('word')->after('order');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_word_bank', function (Blueprint $table) {
            //
        });
    }
};
