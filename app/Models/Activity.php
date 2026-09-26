<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'teacher_id', 'reading_material_id', 'activity_name',
        'description', 'activity_type', 'level', 'difficulty_level',
        'duration_minutes', 'points_reward', 'is_published',
        'allow_reattempt', 'adaptive_difficulty', 'battle_mode'
    ];

    public function readAloudDurationSeconds(): ?int
    {
        $minutes = filter_var($this->duration_minutes, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => intdiv(PHP_INT_MAX, 60000)],
        ]);

        return $minutes === false ? null : $minutes * 60;
    }

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function readingMaterial() {
        return $this->belongsTo(ReadingMaterial::class);
    }

    public function wordBank() {
        return $this->hasMany(ActivityWordBank::class);
    }

    public function results() {
        return $this->hasMany(ActivityResult::class);
    }

    public function voiceRecordings() {
        return $this->hasMany(VoiceRecording::class);
    }

    public function mlPredictions() {
        return $this->hasMany(MlPrediction::class);
    }


    
}
