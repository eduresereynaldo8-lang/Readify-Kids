@extends('layouts.teacher')
@section('title', 'Reading Assessment Progress')
@section('page-title', 'Reading Assessment Progress')
@section('page-sub', 'Follow your class’s reading growth and review assessment history.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/reading-assessment.css') }}">
@endpush
@section('content')
<div class="ra-page">
    <div class="ra-progress-stats">
        <div class="rk-card ra-stat"><small>Total Evaluations</small><strong>{{ $assessment['total'] }}</strong><small>Including legacy history</small></div>
        <div class="rk-card ra-stat"><small>Average Oral Reading</small><strong>{{ $assessment['averages']['oral'] === null ? '—' : number_format($assessment['averages']['oral'], 2).'%' }}</strong></div>
        <div class="rk-card ra-stat"><small>Average Comprehension</small><strong>{{ $assessment['averages']['comprehension'] === null ? '—' : number_format($assessment['averages']['comprehension'], 2).'%' }}</strong></div>
        <div class="rk-card ra-stat"><small>Average Reading Time</small><strong>{{ $assessment['readingTime'] }}</strong></div>
        <div class="rk-card ra-stat"><small>Average Miscues</small><strong>{{ $assessment['averages']['miscues'] === null ? '—' : number_format($assessment['averages']['miscues'], 1) }}</strong></div>
        <div class="rk-card ra-stat"><small>Average Learner Experience</small><strong>{{ $assessment['averages']['experience'] === null ? '—' : number_format($assessment['averages']['experience'], 1).' / 5' }}</strong></div>
    </div>
    <p class="rk-footnote">Rubric averages include only recorded values. Legacy evaluations remain in history with their original final score.</p>
    <div class="ra-dashboard-grid">
        @include('teacher.assessments.trend')
        @include('teacher.assessments.observations')
        @include('teacher.assessments.experience')
    </div>
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-clipboard-list" aria-hidden="true"></i> Detailed Assessment History</h2></div>
        <div class="rk-table-scroll" role="region" aria-label="Detailed assessment history" tabindex="0">
            <table class="rk-table ra-history"><thead><tr><th>Student</th><th>Activity / Rubric</th><th>Evaluation Date</th><th>Oral Reading</th><th>Comprehension</th><th>Observation</th><th>Miscues</th><th>Reading Time</th><th>Experience</th><th>Final Score</th></tr></thead><tbody>
                @forelse($history as $evaluation)
                <tr>
                    <td><a href="{{ route('teacher.students.show', $evaluation->voiceRecording->student_id) }}">{{ $evaluation->voiceRecording->student->firstname }} {{ $evaluation->voiceRecording->student->lastname }}</a></td>
                    <td><a href="{{ route('teacher.evaluations.show', $evaluation->recording_id) }}">{{ $evaluation->voiceRecording->activity->activity_name }}</a><small class="d-block">{{ $evaluation->is_legacy ? 'Legacy rubric' : 'Reading assessment' }} · Attempt {{ $evaluation->voiceRecording->attempt_number }}</small></td>
                    <td>{{ $evaluation->created_at->format('M j, Y') }}</td>
                    <td>{{ $evaluation->oral_reading_score === null ? '—' : $evaluation->oral_reading_score.'%' }}</td>
                    <td>{{ $evaluation->comprehension_percentage === null ? '—' : $evaluation->comprehension_percentage.'%' }}</td>
                    <td>{{ $evaluation->observation_level === null ? '—' : 'Level '.$evaluation->observation_level }}</td>
                    <td>{{ $evaluation->miscues ?? '—' }}</td><td>{{ $evaluation->reading_time_label }}</td>
                    <td>{{ $evaluation->learner_experience === null ? '—' : $evaluation->learner_experience.' / 5' }}</td>
                    <td>{{ $evaluation->final_score === null ? '—' : number_format($evaluation->final_score, 2).'%' }}</td>
                </tr>
                @empty<tr><td colspan="10" class="rk-empty">No evaluations yet. Completed evaluations will appear here.</td></tr>@endforelse
            </tbody></table>
        </div>
        <div class="ra-pagination">{{ $history->links() }}</div>
        <p class="rk-footnote">Final score reflects this recording’s saved assessment, so older attempts keep their own score. Activity summaries use the most recently saved evaluation for that activity.</p>
    </section>
</div>
@endsection
