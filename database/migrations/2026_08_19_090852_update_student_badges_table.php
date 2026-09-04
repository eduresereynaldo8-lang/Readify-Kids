<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_badges', function (Blueprint $table) {
            $table->renameColumn('awarded_at', 'earned_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_badges', function (Blueprint $table) {
            $table->renameColumn('earned_at', 'awarded_at');
        });
    }
};