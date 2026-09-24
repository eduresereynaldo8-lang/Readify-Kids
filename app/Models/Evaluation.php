<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'teacher_id', 'recording_id', 'pronunciation_score',
        'fluency_score', 'accuracy_score', 'comprehension_score',
        'proficiency_level', 'feedback',
        'reading_minutes', 'reading_seconds', 'total_reading_seconds',
        'total_words', 'miscues', 'oral_reading_score', 'correct_answers',
        'total_questions', 'comprehension_percentage', 'observation_level',
        'learner_experience',
    ];

    protected $casts = [
        'reading_minutes' => 'integer', 'reading_seconds' => 'integer',
        'total_reading_seconds' => 'integer', 'total_words' => 'integer',
        'miscues' => 'integer', 'correct_answers' => 'integer',
        'total_questions' => 'integer', 'observation_level' => 'integer',
        'learner_experience' => 'integer', 'oral_reading_score' => 'decimal:2',
        'comprehension_percentage' => 'decimal:2',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function voiceRecording()
    {
        return $this->belongsTo(VoiceRecording::class, 'recording_id');
    }

    public function getIsLegacyAttribute(): bool
    {
        return $this->oral_reading_score === null && $this->comprehension_percentage === null;
    }

    public function getFinalScoreAttribute(): ?float
    {
        // Use the saved per-recording scores, never another attempt's ActivityResult.
        // Observation and experience are descriptive and never affect the score.
        if (! $this->is_legacy) {
            return $this->oral_reading_score === null ? null : \App\Services\ReadingAssessment::finalScore([
                'oral_reading_score' => $this->oral_reading_score,
                'comprehension_percentage' => $this->comprehension_percentage,
            ]);
        }

        $scores = [$this->pronunciation_score, $this->fluency_score,
            $this->accuracy_score, $this->comprehension_score];

        // Preserve the original rubric for history; do not convert it into new fields.
        return in_array(null, $scores, true) ? null : round(array_sum($scores) / 4 * 20, 2);
    }
}
