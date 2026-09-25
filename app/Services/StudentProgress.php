<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class StudentProgress
{
    public const STATUSES = [
        'on_track' => ['label' => 'On Track', 'badge' => 'badge-green', 'color' => '#22C55E'],
        'needs_help' => ['label' => 'Needs Help', 'badge' => 'badge-amber', 'color' => '#F59E0B'],
        'struggling' => ['label' => 'Struggling', 'badge' => 'badge-red', 'color' => '#EF4444'],
        'no_data' => ['label' => 'No Data', 'badge' => 'badge-blue', 'color' => '#94A3B8'],
    ];

    public const EXPORT_FILTERS = ['all', 'on_track', 'needs_help', 'struggling'];

    public static function status(?float $average): array
    {
        // Classify the unrounded average. A measured zero is different from no score.
        $key = match (true) {
            $average === null => 'no_data',
            $average >= 75 => 'on_track',
            $average >= 50 => 'needs_help',
            default => 'struggling',
        };

        return ['key' => $key] + self::STATUSES[$key];
    }

    public static function withActivityProgress(Builder $query): Builder
    {
        return $query
            ->withCount(['activityResults as activities_completed' => fn ($q) => $q->where('status', 'completed')])
            ->withAvg(['activityResults' => fn ($q) => $q->where('status', 'completed')], 'score');
    }

    public static function forTeacher(int $teacherId): Builder
    {
        return self::withActivityProgress(Student::where('teacher_id', $teacherId))
            ->withAvg(['evaluations as average_oral_reading' => fn ($q) => $q->where('evaluations.teacher_id', $teacherId)], 'oral_reading_score')
            ->withAvg(['evaluations as average_comprehension' => fn ($q) => $q->where('evaluations.teacher_id', $teacherId)], 'comprehension_percentage');
    }

    public static function report(Student $student): array
    {
        return [
            'student' => $student,
            'activities_completed' => (int) $student->activities_completed,
            'average_score' => $student->activity_results_avg_score === null ? null : (float) $student->activity_results_avg_score,
            // SQL AVG excludes null values, while retaining genuine zero scores.
            'average_oral_reading' => $student->average_oral_reading === null ? null : (float) $student->average_oral_reading,
            'average_comprehension' => $student->average_comprehension === null ? null : (float) $student->average_comprehension,
            'status' => $student->reading_status,
        ];
    }
}
