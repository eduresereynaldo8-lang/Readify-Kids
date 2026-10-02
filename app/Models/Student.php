<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    protected $fillable = [
        'user_id', 'teacher_id', 'student_number',
        'firstname', 'lastname', 'section',
        'lrn_no', 'birthday', 'age', 'gender',
        'current_level', 'total_points', 'profile_picture',
    ];

    protected $casts = [
        'birthday' => 'date',
        'age' => 'integer',
    ];

    public function getAgeAttribute($value): ?int
    {
        // Persist a snapshot on save, but display today's age instead of a stale snapshot.
        if ($this->birthday !== null) {
            return (int) $this->birthday->age;
        }

        return $value === null ? null : (int) $value;
    }

    public function ownsProfilePicture(?string $path): bool
    {
        // Only generated filenames within this student's own directory are managed.
        return $path !== null && preg_match(
            '~\Aprofile_pictures/students/'.preg_quote((string) $this->id, '~').'/[A-Za-z0-9]{40}\.(?:jpg|jpeg|png|webp)\z~',
            $path
        ) === 1;
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        $path = $this->profile_picture;
        if (! $this->ownsProfilePicture($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        // The public disk is served by public/storage; use the current request host.
        return asset('storage/'.$path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function activityResults()
    {
        return $this->hasMany(ActivityResult::class);
    }

    public function evaluations()
    {
        return $this->hasManyThrough(Evaluation::class, VoiceRecording::class,
            'student_id', 'recording_id', 'id', 'id');
    }

    /** Requires the completed activity score aggregate from StudentProgress. */
    public function getReadingStatusAttribute(): array
    {
        return \App\Services\StudentProgress::status(
            $this->activity_results_avg_score === null ? null : (float) $this->activity_results_avg_score
        );
    }

    public function voiceRecordings()
    {
        return $this->hasMany(VoiceRecording::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'student_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function studentBadges()
    {
        return $this->hasMany(StudentBadge::class);
    }

    public function rewards()
    {
        return $this->hasMany(StudentReward::class);
    }

    public function achievements()
    {
        return $this->hasMany(StudentAchievement::class);
    }

    public function leaderboard()
    {
        return $this->hasOne(Leaderboard::class);
    }

    public function mlPredictions()
    {
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

        if (! empty($achieved)) {
            $this->save();

            \App\Helpers\LogActivity::forStudent($this, 'LEVEL_UP', 'Progress',
                "Advanced from level {$originalLevel} to level {$this->current_level} with {$this->total_points} points.");

            // Log the level up event
            Log::info("Student #{$this->id} ({$this->firstname} {$this->lastname}) leveled up!", [
                'from_level' => $originalLevel,
                'to_level' => $this->current_level,
                'total_points' => $this->total_points,
                'teacher_id' => $this->teacher_id,
            ]);
        }

        return ! empty($achieved) ? $achieved : null;
    }
}
