<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Individual Student Record</title>
    @include('teacher.students.pdf-styles')
</head>
<body>
    <h1>READIFY KIDS</h1>
    <h2>Individual Student Record</h2>
    <p><strong>Teacher:</strong> {{ $teacher->firstname }} {{ $teacher->lastname }}</p>
    <p><strong>School:</strong> {{ $teacher->school_name ?? '—' }}</p>
    <p><strong>Generated Date:</strong> {{ $generatedAt->format('M d, Y h:i A T') }}</p>

    <h3>Student Information</h3>
    <table>
        <tr><th>Full Name</th><td colspan="3">{{ $student->firstname }} {{ $student->lastname }}</td></tr>
        <tr><th>LRN No.</th><td>{{ $student->lrn_no ?? '—' }}</td><th>Section</th><td>{{ $student->section ?? '—' }}</td></tr>
        <tr><th>Birthday</th><td>{{ $student->birthday?->format('M d, Y') ?? '—' }}</td><th>Age</th><td>{{ $student->age ?? '—' }}</td></tr>
        <tr><th>Gender</th><td>{{ $student->gender ?? '—' }}</td><th>Current Level</th><td>{{ $student->current_level }}</td></tr>
        <tr><th>Total Points</th><td colspan="3">{{ $student->total_points }}</td></tr>
    </table>

    <h3>Reading Summary</h3>
    <table>
        <tr><th>Completed Activities</th><td>{{ $progress['activities_completed'] }}</td><th>Average Final Score</th><td>{{ $progress['average_score'] === null ? '—' : number_format($progress['average_score'], 2).'%' }}</td></tr>
        <tr><th>Average Oral Reading</th><td>{{ $progress['average_oral_reading'] === null ? '—' : number_format($progress['average_oral_reading'], 2).'%' }}</td><th>Average Comprehension</th><td>{{ $progress['average_comprehension'] === null ? '—' : number_format($progress['average_comprehension'], 2).'%' }}</td></tr>
        <tr><th>Reading Status</th><td colspan="3"><span class="badge {{ $progress['status']['key'] }}">{{ $progress['status']['label'] }}</span></td></tr>
    </table>
    <p class="note">Final score/status use completed activity scores. Status uses the unrounded average: On Track ≥ 75%; Needs Help ≥ 50% and &lt; 75%; Struggling &lt; 50%. No Data means no completed scored result. Missing assessment fields are excluded from averages.</p>

    <h3>Recent Activity Results</h3>
    <p class="note">All saved activity results, newest completion first.</p>
    <table>
        <thead><tr><th>Activity</th><th>Type</th><th>Score</th><th>Status</th><th>Completed</th></tr></thead>
        <tbody>
            @forelse($student->activityResults as $result)
            <tr>
                <td>{{ $result->activity?->activity_name ?? '—' }}</td>
                <td>{{ $result->activity?->activity_type ?? '—' }}</td>
                <td>{{ $result->score === null ? '—' : number_format($result->score, 2).'%' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $result->status ?? 'unknown')) }}</td>
                <td>{{ $result->completed_at ? \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty">No activity results yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Reading Assessment History</h3>
    <p class="note">All saved assessments by this teacher, newest first. Legacy scores retain their original rubric; no new rubric scores are inferred.</p>
    @forelse($student->evaluations as $evaluation)
    <table class="assessment">
        <tr><th>Date / Activity</th><td>{{ $evaluation->created_at?->format('M d, Y') ?? '—' }} · {{ $evaluation->voiceRecording?->activity?->activity_name ?? '—' }} · Attempt {{ $evaluation->voiceRecording?->attempt_number ?? '—' }}</td></tr>
        <tr><th>Evaluated By</th><td>{{ $evaluation->teacher?->firstname }} {{ $evaluation->teacher?->lastname }}</td></tr>
        @if($evaluation->is_legacy)
        <tr><th>Legacy Rubric</th><td>Pronunciation: {{ $evaluation->pronunciation_score ?? '—' }} / 5;
            Fluency: {{ $evaluation->fluency_score ?? '—' }} / 5;
            Accuracy: {{ $evaluation->accuracy_score ?? '—' }} / 5;
            Comprehension: {{ $evaluation->comprehension_score ?? '—' }} / 5</td></tr>
        <tr><th>Proficiency</th><td>{{ $evaluation->proficiency_level ?? '—' }}</td></tr>
        @else
        <tr><th>Reading Scores</th><td>Oral Reading: {{ $evaluation->oral_reading_score === null ? '—' : number_format($evaluation->oral_reading_score, 2).'%' }};
            Comprehension: {{ $evaluation->comprehension_percentage === null ? '—' : number_format($evaluation->comprehension_percentage, 2).'%' }}</td></tr>
        <tr><th>Reading Details</th><td>Words: {{ $evaluation->total_words ?? '—' }}; Miscues: {{ $evaluation->miscues ?? '—' }};
            Reading time: {{ $evaluation->total_reading_seconds === null ? '—' : $evaluation->total_reading_seconds.' seconds' }};
            Correct answers: {{ $evaluation->correct_answers ?? '—' }} / {{ $evaluation->total_questions ?? '—' }}</td></tr>
        <tr><th>Observation / Experience</th><td>Observation: {{ \App\Services\ReadingAssessment::OBSERVATIONS[$evaluation->observation_level] ?? '—' }};
            Learner experience: {{ $evaluation->learner_experience ?? '—' }} / 5</td></tr>
        @endif
    </table>
    {{-- Keep potentially long feedback outside a table row so it can flow across pages. --}}
    <p><strong>Feedback:</strong> {!! nl2br(e($evaluation->feedback ?? '—')) !!}</p>
    @empty
    <p>No reading assessments yet.</p>
    @endforelse
</body>
</html>
