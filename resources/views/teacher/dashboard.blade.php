@extends('layouts.teacher')
@section('title', 'Teacher Dashboard')
@section('greeting', 'Good day, Teacher ' . $teacher->firstname . '!')
@section('subtitle', "Here's your reading assessment overview today.")
@push('styles')
<link rel="stylesheet" href="{{ asset('css/reading-assessment.css') }}">
@endpush
@section('content')
<div class="rk-stats">
    <x-dashboard.stat-card label="Total Students" :value="$total" icon="users" hint="Learners in your classroom" :href="route('teacher.students.index')" />
    <x-dashboard.stat-card label="Pending Evaluations" :value="$pendingReviews" icon="file-description" tone="pink" hint="Recordings needing your review" :href="route('teacher.evaluations.index')" />
    <x-dashboard.stat-card label="Evaluations Completed" :value="$evaluationsCompleted" icon="circle-check" tone="green" hint="First evaluated this week" :href="route('teacher.progress')" />
    <x-dashboard.stat-card label="Average Final Score" :value="$averageFinalScore === null ? '—' : number_format($averageFinalScore, 2).'%'" icon="star" tone="yellow" hint="All completed activity results" :href="route('teacher.progress')" />
</div>
<div class="ra-dashboard-grid">
    @include('teacher.assessments.overview')
    @include('teacher.assessments.observations')
    @include('teacher.assessments.experience')
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-trophy" aria-hidden="true"></i> Top 5 Students</h2><a href="{{ route('teacher.leaderboard') }}">View leaderboard →</a></div>
        <p class="rk-card-subtitle">Highest average scores across completed activities</p>
        <div class="rk-table-scroll" tabindex="0" role="region" aria-label="Top students"><table class="rk-table"><thead><tr><th>#</th><th>Student</th><th>Class / Level</th><th>Average Final Score</th></tr></thead><tbody>
            @forelse($topStudents as $i => $learner)
            <tr><td><span class="rk-rank rank-{{ $i + 1 }}">{{ $i + 1 }}</span></td><td><a href="{{ route('teacher.students.show', $learner->id) }}"><strong>{{ $learner->firstname }} {{ $learner->lastname }}</strong></a></td><td>{{ $learner->section ?: '—' }} · Level {{ $learner->current_level }}</td><td class="ra-score">{{ number_format($learner->activity_results_avg_score, 2) }}%<div class="rk-progress"><span style="width:{{ $learner->activity_results_avg_score }}%"></span></div></td></tr>
            @empty<tr><td colspan="4" class="rk-empty">Rankings will appear after activities are scored.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    @include('teacher.assessments.trend')
    <section class="rk-card rk-attention">
        <div class="rk-card-heading"><h2><i class="ti ti-alert-triangle" aria-hidden="true"></i> Needs Attention</h2><a href="#classroom-progress">View all →</a></div>
        <p class="rk-card-subtitle">Students whose average score is below 75%</p>
        <div class="rk-table-scroll" tabindex="0" role="region" aria-label="Students needing support"><table class="rk-table"><thead><tr><th>Student</th><th>Class / Level</th><th>Average Final Score</th><th>Status</th></tr></thead><tbody>
            @forelse($needHelp as $learner)
            <tr><td><a href="{{ route('teacher.students.show', $learner->id) }}"><strong>{{ $learner->firstname }} {{ $learner->lastname }}</strong></a></td><td>{{ $learner->section ?: '—' }} · Level {{ $learner->current_level }}</td><td>{{ number_format($learner->activity_results_avg_score, 2) }}%</td><td><span class="rk-pill pending">{{ $learner->activity_results_avg_score < 50 ? 'Struggling' : 'Needs Help' }}</span></td></tr>
            @empty<tr><td colspan="4" class="rk-empty">No scored students currently below 75%.</td></tr>@endforelse
        </tbody></table></div>
        <p class="rk-footnote">Support status is based on activity scores. Students awaiting their first score are listed in Your Classroom.</p>
    </section>
</div>
<section class="rk-card" id="classroom-progress">
    <div class="rk-card-heading"><h2><i class="ti ti-school" aria-hidden="true"></i> Your Classroom</h2><a href="{{ route('teacher.students.index') }}">Manage students →</a></div>
    <div class="rk-table-scroll" role="region" aria-label="Class reading progress" tabindex="0"><table class="rk-table"><thead><tr><th>Student</th><th>Class / Level</th><th>Average Final Score</th><th>Status</th></tr></thead><tbody>
    @forelse($students as $learner)
        @php $average = $learner->activity_results_avg_score; $status = $average === null ? 'Awaiting evaluation' : ($average >= 75 ? 'On Track' : ($average >= 50 ? 'Needs Help' : 'Struggling')); @endphp
        <tr><td><a href="{{ route('teacher.students.show', $learner->id) }}"><strong>{{ $learner->firstname }} {{ $learner->lastname }}</strong></a></td><td>{{ $learner->section ?: '—' }} · Level {{ $learner->current_level }}</td><td>{{ $average === null ? '—' : number_format($average, 2).'%' }}</td><td><span class="rk-pill {{ $status === 'On Track' ? 'done' : 'pending' }}">{{ $status }}</span></td></tr>
    @empty<tr><td colspan="4" class="rk-empty">Add your first student to start your classroom’s reading journey.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
