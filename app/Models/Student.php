<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'teacher_id', 'student_number',
        'firstname', 'lastname', 'section',
        'current_level', 'total_points'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    public function activityResults() {
        return $this->hasMany(ActivityResult::class);
    }

    public function voiceRecordings() {
        return $this->hasMany(VoiceRecording::class);
    }

    public function badges() {
        return $this->hasMany(StudentBadge::class);
    }

    public function rewards() {
        return $this->hasMany(StudentReward::class);
    }

    public function achievements() {
        return $this->hasMany(StudentAchievement::class);
    }

    public function leaderboard() {
        return $this->hasOne(Leaderboard::class);
    }

    public function mlPredictions() {
        return $this->hasMany(MlPrediction::class);
    }
}