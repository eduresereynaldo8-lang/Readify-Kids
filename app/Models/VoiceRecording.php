<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VoiceRecording extends Model
{
    protected $fillable = [
        'student_id', 'activity_id',
        'recording_path', 'attempt_number', 'status'
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function activity() {
        return $this->belongsTo(Activity::class);
    }

    public function evaluation() {
        return $this->hasOne(Evaluation::class, 'recording_id');
    }
}