@extends('layouts.teacher')
@section('title', 'Teacher Dashboard')
@section('greeting', 'Good day, Teacher ' . $teacher->firstname . '!')
@section('subtitle', "Here's a quick overview of your class today.")
@section('content')
<div class="rk-stats">
    <x-dashboard.stat-card label="Total Students" :value="$total" icon="users" hint="Learners in your classroom" :href="route('teacher.students.index')" />
    <x-dashboard.stat-card label="Active Today" :value="$activeToday" icon="bolt" tone="green" hint="Seen in recorded sessions today" :href="route('teacher.students.index')" />
    <x-dashboard.stat-card label="Activities Done" :value="$activitiesDone" icon="clipboard-check" tone="yellow" hint="Completed this week" :href="route('teacher.progress')" />
    <x-dashboard.stat-card label="Pending Reviews" :value="$pendingReviews" icon="microphone" tone="pink" hint="Recordings to evaluate" :href="route('teacher.evaluations.index')" />
</div>
<div class="rk-teacher-overview">
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-book" aria-hidden="true"></i> Student Reading Progress</h2></div><p class="rk-card-subtitle">Average performance per skill · your class</p>@include('dashboard.partials.skills')</section>
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-users" aria-hidden="true"></i> Student Status</h2></div>
        @php $green = $total ? $onTrack/$total*100 : 0; $amber = $total ? $needsHelp/$total*100 : 0; @endphp
        <div class="rk-donut-layout">
            <div class="rk-donut" role="img" aria-label="Student status: {{ $onTrack }} on track, {{ $needsHelp }} need help, {{ $struggling }} struggling" style="background:{{ $total ? 'conic-gradient(#35bd85 0% '.$green.'%, #efb72b '.$green.'% '.($green+$amber).'%, #ef799c '.($green+$amber).'% 100%)' : '#edf3f9' }}"><div class="rk-donut-hole"><strong>{{ $total }}</strong><small>Total students</small></div></div>
            <div class="rk-legend">@foreach([['green','On Track',$onTrack],['yellow','Needs Help',$needsHelp],['pink','Struggling',$struggling]] as [$tone,$label,$count])<div class="rk-legend-row rk-tone-{{ $tone }}"><span class="rk-legend-dot"></span><span>{{ $label }}</span><strong>{{ $count }}</strong></div>@endforeach</div>
        </div>
        <p class="rk-footnote">On track: 75%+ · Needs help: 50–74%. Students without scores are included in the struggling group and need an initial review.</p>
        <a class="rk-callout" href="{{ route('teacher.progress') }}"><i class="ti ti-alert-circle" aria-hidden="true"></i><span><strong>{{ $needsHelp + $struggling }} students need attention</strong><small>Check their progress and provide support.</small></span></a>
    </section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-books" aria-hidden="true"></i> Activities by Type</h2></div>@include('dashboard.partials.activity-types')<a class="rk-button rk-button-full" href="{{ route('teacher.activities.create.readaloud') }}"><i class="ti ti-plus" aria-hidden="true"></i> Create Read Aloud</a><a class="rk-button rk-button-soft rk-button-full" href="{{ route('teacher.activities.create.battle') }}">Create Battle →</a></section>
</div>
<div class="rk-teacher-bottom">
    <div class="rk-stack">
        <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-chart-bar" aria-hidden="true"></i> Class Reading Skill Breakdown</h2><a href="{{ route('teacher.progress') }}">Full report →</a></div><p class="rk-card-subtitle">Your students' teacher-evaluated reading skills.</p>
            <div class="rk-skill-tiles">@foreach($skills as $skill=>$score)
                @php $tone = ['Pronunciation'=>'blue','Fluency'=>'green','Accuracy'=>'purple','Comprehension'=>'yellow'][$skill]; @endphp
                <div class="rk-skill-tile rk-tone-{{ $tone }}"><span class="rk-skill-icon"><i class="ti ti-{{ ['Pronunciation'=>'microphone','Fluency'=>'volume','Accuracy'=>'alphabet-latin','Comprehension'=>'brain'][$skill] }}" aria-hidden="true"></i></span><h3>{{ $skill }}</h3><strong>{{ $score === null ? '—' : $score.'%' }}</strong><div class="rk-progress"><span style="width:{{ $score ?? 0 }}%"></span></div><small>{{ $score === null ? 'Awaiting evaluations' : 'Average evaluated score' }}</small></div>
            @endforeach</div>
        </section>
        @include('dashboard.partials.attention', ['attentionUrl'=>route('teacher.progress')])
    </div>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-trophy" aria-hidden="true"></i> Top 5 Students</h2><a href="{{ route('teacher.leaderboard') }}">View leaderboard →</a></div>@include('dashboard.partials.leaderboard', ['ranking'=>$topStudents, 'showScores'=>true])</section>
</div>
<section class="rk-card">
    <div class="rk-card-heading"><h2><i class="ti ti-school" aria-hidden="true"></i> Your Classroom</h2><a href="{{ route('teacher.students.index') }}">Manage students →</a></div>
    <div class="rk-table-scroll" role="region" aria-label="Class reading progress" tabindex="0"><table class="rk-table"><thead><tr><th>Student</th><th>Level</th><th>Average score</th><th>Status</th></tr></thead><tbody>
    @forelse($students as $learner)
        @php $average = $learner->activity_results_avg_score; $status = ($average ?? 0) >= 75 ? 'On Track' : (($average ?? 0) >= 50 ? 'Needs Help' : 'Struggling'); @endphp
        <tr><td><a href="{{ route('teacher.students.show', $learner->id) }}"><strong>{{ $learner->firstname }} {{ $learner->lastname }}</strong></a></td><td><span class="rk-pill">Level {{ $learner->current_level }}</span></td><td>{{ $average === null ? 'Not yet scored' : round($average,1).'%' }}</td><td><span class="rk-pill {{ $status === 'On Track' ? 'done' : 'pending' }}">{{ $status }}</span></td></tr>
    @empty<tr><td colspan="4" class="rk-empty">Add your first student to start your classroom's reading journey.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
