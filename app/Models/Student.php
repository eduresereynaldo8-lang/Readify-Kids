<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    // Activity validation and student placement both support Levels 1 through 5.
    public const MAX_LEVEL = 5;
    public const POINTS_PER_LEVEL = 500;
    public const MIN_READING_SCORE = 100.0;

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
     * Current-level reading performance and cumulative points share one rule for
     * both progression and its display. A null average means no scored results.
     */
    public function levelProgress(): array
    {
        $level = (int) $this->current_level;
        $atMaxLevel = $level >= self::MAX_LEVEL;
        $requiredPoints = $atMaxLevel ? null : $level * self::POINTS_PER_LEVEL;
        $average = $this->activityResults()
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->whereHas('activity', fn ($query) => $query->where('level', $level))
            ->avg('score');
        $average = $average === null ? null : (float) $average;
        // Compare the unrounded average: 99.996% must not qualify as 100%.
        $readingMet = $average !== null && $average >= self::MIN_READING_SCORE;
        $pointsMet = !$atMaxLevel && $this->total_points >= $requiredPoints;

        return [
            'current_level' => $level,
            'max_level' => self::MAX_LEVEL,
            'at_max_level' => $atMaxLevel,
            'next_level' => $atMaxLevel ? null : $level + 1,
            'total_points' => (int) $this->total_points,
            'required_points' => $requiredPoints,
            'points_remaining' => $atMaxLevel ? 0 : max(0, $requiredPoints - $this->total_points),
            'points_percent' => $atMaxLevel ? 100 : min(100, round($this->total_points / max(1, $requiredPoints) * 100)),
            'average_score' => $average,
            'required_score' => self::MIN_READING_SCORE,
            'points_met' => $pointsMet,
            'reading_met' => $readingMet,
            'eligible' => !$atMaxLevel && $level >= 1 && $pointsMet && $readingMet,
        ];
    }

    /**
     * Advance using cumulative points AND completed reading scores at each level.
     * Returns achieved levels, or null when no level changed.
     */
    public function checkAndUpdateLevel(): ?array
    {
        return DB::transaction(function () {
            // Always use current persisted state, including across concurrent awards.
            $student = $this->newQuery()->lockForUpdate()->findOrFail($this->id);
            $achieved = [];
            $originalLevel = (int) $student->current_level;

            while ($student->levelProgress()['eligible']) {
                $student->current_level++;
                $achieved[] = (int) $student->current_level;
            }

            if ($achieved !== []) {
                $student->save();
                \App\Helpers\LogActivity::forStudent($student, 'LEVEL_UP', 'Progress',
                    "Advanced from level {$originalLevel} to level {$student->current_level} with {$student->total_points} points.");

                Log::info("Student #{$student->id} leveled up!", [
                    'from_level' => $originalLevel,
                    'to_level' => $student->current_level,
                    'total_points' => $student->total_points,
                    'teacher_id' => $student->teacher_id,
                ]);
            }

            $this->setRawAttributes($student->getAttributes(), true);
            $this->unsetRelation('activityResults');

            return $achieved === [] ? null : $achieved;
        }, 3);
    }
}
