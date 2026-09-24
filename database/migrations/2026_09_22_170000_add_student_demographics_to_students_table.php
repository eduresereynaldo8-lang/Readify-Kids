<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Nullable for existing accounts; new/edited profiles require these fields.
            // Keep student_number and its historical values unchanged.
            $table->string('lrn_no', 12)->nullable()->unique();
            $table->date('birthday')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->string('gender', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['lrn_no']);
            $table->dropColumn(['lrn_no', 'birthday', 'age', 'gender']);
        });
    }
};
