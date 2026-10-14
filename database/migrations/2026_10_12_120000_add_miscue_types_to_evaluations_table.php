<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [];
        foreach (['mispronunciation', 'omission', 'substitution', 'insertion',
            'repetition', 'transposition', 'reversal'] as $column) {
            if (! Schema::hasColumn('evaluations', $column)) {
                $missing[] = $column;
            }
        }

        if ($missing !== []) {
            Schema::table('evaluations', function (Blueprint $table) use ($missing) {
                foreach ($missing as $column) {
                    $table->unsignedInteger($column)->default(0);
                }
            });
        }
        // Existing miscues totals and scores deliberately remain untouched.
    }

    public function down(): void
    {
        throw new \LogicException('This forward-only migration preserves evaluation history and cannot be rolled back.');
    }
};
