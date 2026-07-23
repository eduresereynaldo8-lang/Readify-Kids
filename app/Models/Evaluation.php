<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'teacher_id', 'recording_id', 'pronunciation_score',
        'fluency_score', 'accuracy_score', 'comprehension_score',
        'proficiency_level', 'feedback'
    ];

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function voiceRecording() {
        return $this->belongsTo(VoiceRecording::class, 'recording_id');
    }
}