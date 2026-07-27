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

    /**
     * Automatically check and update the student's level based on total points.
     * Level threshold = current_level * 500 points.
     * This handles multiple level-ups at once.
     *
     * @return array|null Array of levels achieved, or null if no change
     */
    public function checkAndUpdateLevel()
    {
        $achieved = [];
        $originalLevel = $this->current_level;

        // Keep leveling up while points meet the threshold
        while ($this->total_points >= $this->current_level * 500) {
            $this->current_level++;
            $achieved[] = $this->current_level;
        }

        if (!empty($achieved)) {
            $this->save();

            // Log the level up event
            \Illuminate\Support\Facades\Log::info("Student #{$this->id} ({$this->firstname} {$this->lastname}) leveled up!", [
                'from_level' => $originalLevel,
                'to_level' => $this->current_level,
                'total_points' => $this->total_points,
                'teacher_id' => $this->teacher_id,
            ]);
        }

        return !empty($achieved) ? $achieved : null;
    }
}
