<?php
namespace App\Services;

use App\Models\GameSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    /** Null means no evaluated score, rather than a measured zero. */
    public static function readingSkills(Builder $evaluations): array
    {
        $summary = $evaluations->selectRaw(
            'AVG(pronunciation_score) as pronunciation, AVG(fluency_score) as fluency, ' .
            'AVG(accuracy_score) as accuracy, AVG(comprehension_score) as comprehension'
        )->first();
        $skills = [];
        foreach (['Pronunciation'=>'pronunciation', 'Fluency'=>'fluency', 'Accuracy'=>'accuracy', 'Comprehension'=>'comprehension'] as $label=>$column) {
            $skills[$label] = $summary->{$column} === null ? null : round($summary->{$column} * 20, 1);
        }
        return $skills;
    }

    /** Each activity counts once, including battles typed as Word Game. */
    public static function activityTypes(Builder $activities): Collection
    {
        return $activities
            ->selectRaw("CASE WHEN battle_mode = 1 THEN 'Battle' ELSE activity_type END as dashboard_type, COUNT(*) as total")
            ->groupBy('dashboard_type')->orderByDesc('total')->get()
            ->mapWithKeys(fn ($row) => [$row->dashboard_type => (int) $row->total]);
    }

    public static function unattemptedActivities(Builder $activities): int
    {
        return $activities->where('is_published', true)
            ->whereDoesntHave('results')->whereDoesntHave('voiceRecordings')
            ->whereNotIn('id', GameSession::select('activity_id'))->count();
    }
}
