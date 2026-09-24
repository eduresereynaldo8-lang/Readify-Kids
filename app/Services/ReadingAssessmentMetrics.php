<?php

namespace App\Services;

use App\Models\Evaluation;
use Illuminate\Database\Eloquent\Builder;

class ReadingAssessmentMetrics
{
    public static function forTeacher(int $teacherId): Builder
    {
        return Evaluation::whereHas('voiceRecording.student', fn ($q) => $q->where('teacher_id', $teacherId))
            ->whereHas('voiceRecording.activity', fn ($q) => $q->where('teacher_id', $teacherId)
                ->where('activity_type', 'Read Aloud')->where('battle_mode', false));
    }

    public static function skills(Builder $query): array
    {
        $row = (clone $query)->selectRaw(
            'AVG(oral_reading_score) as oral, AVG(comprehension_percentage) as comprehension'
        )->first();

        return [
            'Oral Reading' => $row->oral === null ? null : round((float) $row->oral, 2),
            'Comprehension' => $row->comprehension === null ? null : round((float) $row->comprehension, 2),
        ];
    }

    public static function report(Builder $query): array
    {
        // SQL AVG ignores NULL but includes real zero scores.
        $row = (clone $query)->selectRaw('COUNT(*) as total,
            AVG(oral_reading_score) as oral, AVG(comprehension_percentage) as comprehension,
            AVG(total_reading_seconds) as reading_time, AVG(miscues) as miscues,
            AVG(learner_experience) as experience')->first();
        $averages = [];
        foreach (['oral', 'comprehension', 'reading_time', 'miscues', 'experience'] as $field) {
            $averages[$field] = $row->{$field} === null ? null : round((float) $row->{$field}, 2);
        }
        $observations = self::distribution($query, 'observation_level', 4);
        $experiences = self::distribution($query, 'learner_experience', 5);

        // Four calendar weeks ending today; each average is weighted per evaluation.
        // A bounded four-query series avoids loading the entire evaluation history.
        $trend = [];
        $end = now();
        for ($i = 3; $i >= 0; $i--) {
            $start = $end->copy()->startOfWeek()->subWeeks($i);
            $until = $i === 0 ? $end : $start->copy()->endOfWeek();
            $scores = self::skills((clone $query)->whereBetween('created_at', [$start, $until]));
            $trend[] = [
                'label' => $start->format('M j'),
                'period' => $start->format('M j, Y').' – '.$until->format('M j, Y'),
                'oral' => $scores['Oral Reading'],
                'comprehension' => $scores['Comprehension'],
            ];
        }

        return [
            'total' => (int) $row->total, 'averages' => $averages,
            'readingTime' => ReadingAssessment::formatTime($averages['reading_time']),
            'observations' => $observations, 'observationTotal' => array_sum($observations),
            'experiences' => $experiences, 'experienceTotal' => array_sum($experiences),
            'trend' => $trend, 'observationLabels' => ReadingAssessment::OBSERVATIONS,
        ];
    }

    private static function distribution(Builder $query, string $column, int $maximum): array
    {
        $counts = (clone $query)->whereBetween($column, [1, $maximum])
            ->selectRaw($column.', COUNT(*) as total')->groupBy($column)->pluck('total', $column);
        $distribution = [];
        for ($i = 1; $i <= $maximum; $i++) {
            $distribution[$i] = (int) ($counts[$i] ?? 0);
        }

        return $distribution;
    }
}
