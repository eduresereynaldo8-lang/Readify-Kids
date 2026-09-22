@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('greeting', 'Good day, Administrator!')
@section('subtitle', "Here's what's happening with Readify Kids today.")
@section('content')
<div class="rk-stats">
    <x-dashboard.stat-card label="Teachers" :value="$totalTeachers" icon="school" hint="Across Readify Kids" :href="route('admin.teachers')" />
    <x-dashboard.stat-card label="Students" :value="$totalStudents" icon="users" tone="green" hint="Growing readers" :href="route('admin.students')" />
    <x-dashboard.stat-card label="Activities" :value="$totalActivities" icon="book" tone="purple" hint="Learning experiences" :href="route('admin.activities')" />
    <x-dashboard.stat-card label="Recordings" :value="$totalRecordings" icon="microphone" tone="pink" hint="Student submissions" :href="route('admin.evaluations')" />
    <x-dashboard.stat-card label="Games Played" :value="$totalGames" icon="device-gamepad-2" hint="All battle sessions" :href="route('admin.reports')" />
    <x-dashboard.stat-card label="Battles Won" :value="$totalWins" icon="trophy" tone="yellow" hint="Reading victories" :href="route('admin.reports')" />
    <x-dashboard.stat-card label="Total Points Earned" :value="number_format($totalPoints)" icon="star" tone="yellow" hint="Across all students" :href="route('admin.students')" />
    <x-dashboard.stat-card label="Average Student Score" :value="$averageScore === null ? '—' : $averageScore.'%'" icon="chart-bar" tone="green" hint="Completed activity results" :href="route('admin.reports')" />
</div>
<div class="rk-admin-overview">
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-chart-bar" aria-hidden="true"></i> Activity Completion Trend</h2><small>Last 7 days</small></div>
        @php $chartMax = max(1, collect($weeklyData)->max(fn($day)=>$day['completed'])); @endphp
        <div class="rk-chart" role="img" aria-label="Completed activities in the last seven days">
            @foreach($weeklyData as $day)<div class="rk-chart-column"><strong class="rk-chart-value">{{ $day['completed'] }}</strong><div class="rk-chart-bar" style="height:{{ $day['completed']/$chartMax*150 }}px;background:{{ $day['completed'] ? '#47c394' : '#e6eef6' }}"></div><span class="rk-chart-label">{{ $day['day'] }}</span></div>@endforeach
        </div>
        <p class="rk-footnote">Completed activity results, by completion date.</p>
        <details><summary class="rk-footnote">View exact daily counts</summary><div class="rk-table-scroll"><table class="rk-table"><thead><tr><th>Date</th><th>Completed</th></tr></thead><tbody>@foreach($weeklyData as $day)<tr><td>{{ $day['date'] }}</td><td>{{ $day['completed'] }}</td></tr>@endforeach</tbody></table></div></details>
    </section>
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-swords" aria-hidden="true"></i> Battle Performance</h2></div>
        @php $winRate = $totalGames ? round($totalWins/$totalGames*100) : 0; @endphp
        <div class="rk-donut-layout"><div class="rk-donut" role="img" aria-label="{{ $winRate }}% of battle sessions won" style="background:conic-gradient(#4cbf99 0% {{ $winRate }}%,#eaf0f7 {{ $winRate }}% 100%)"><div class="rk-donut-hole"><strong>{{ $winRate }}%</strong><small>Win rate</small></div></div>
            <div class="rk-legend"><div class="rk-legend-row rk-tone-blue"><span class="rk-legend-dot"></span> Played<strong>{{ $totalGames }}</strong></div><div class="rk-legend-row rk-tone-green"><span class="rk-legend-dot"></span> Won<strong>{{ $totalWins }}</strong></div><div class="rk-legend-row rk-tone-pink"><span class="rk-legend-dot"></span> Lost<strong>{{ $battleLost }}</strong></div><div class="rk-legend-row rk-tone-purple"><span class="rk-legend-dot"></span> Ongoing<strong>{{ $battleOngoing }}</strong></div></div>
        </div><p class="rk-footnote">All recorded battle sessions, including ongoing battles.</p><a href="{{ route('admin.reports') }}" class="rk-button rk-button-full">View battle reports →</a>
    </section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-books" aria-hidden="true"></i> Activities by Type</h2></div>@include('dashboard.partials.activity-types')<a href="{{ route('admin.activities') }}" class="rk-button rk-button-soft rk-button-full">Manage activities →</a></section>
</div>
<div class="rk-admin-bottom">
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-brain" aria-hidden="true"></i> System-Wide Reading Progress</h2></div>@include('dashboard.partials.skills')</section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-school" aria-hidden="true"></i> Top Teachers by Students</h2></div>
        <div class="rk-table-scroll" role="region" aria-label="Top teachers" tabindex="0"><table class="rk-table"><thead><tr><th>Teacher</th><th>Students</th><th>Activities</th></tr></thead><tbody>
        @forelse($topTeachers as $educator)<tr><td><div class="rk-person"><span class="rk-avatar">{{ mb_strtoupper(mb_substr($educator->firstname,0,1).mb_substr($educator->lastname,0,1)) }}</span><strong>{{ $educator->firstname }} {{ $educator->lastname }}</strong></div></td><td>{{ $educator->students_count }}</td><td>{{ $educator->activities_count }}</td></tr>
        @empty<tr><td colspan="3" class="rk-empty">Teachers will appear here after registering.</td></tr>@endforelse
        </tbody></table></div><div class="rk-card-footer"><a class="rk-card-link" href="{{ route('admin.teachers') }}">View all teachers →</a></div>
    </section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-trophy" aria-hidden="true"></i> Top 10 Students</h2></div>@include('dashboard.partials.leaderboard', ['ranking'=>$topStudents, 'showScores'=>true])<div class="rk-card-footer"><a href="{{ route('admin.students') }}" class="rk-card-link">View all students →</a></div></section>
</div>
@include('dashboard.partials.attention', ['attentionUrl'=>route('admin.reports')])
<div class="rk-student-middle">
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-activity" aria-hidden="true"></i> System Activity</h2><small>Last 7 days</small></div>
        @php $systemMax = max(1, collect($weeklyData)->max(fn($day)=>$day['games']+$day['recs'])); @endphp
        <div class="rk-chart" role="img" aria-label="Battle sessions and recordings per day">
            @foreach($weeklyData as $day)<div class="rk-chart-column"><span class="rk-chart-value">{{ $day['games']+$day['recs'] }}</span><div class="rk-chart-bar" style="height:{{ ($day['games']+$day['recs'])/$systemMax*150 }}px"><span style="height:{{ ($day['games']+$day['recs']) ? $day['games']/($day['games']+$day['recs'])*100 : 0 }}%;background:#9470e7"></span><span style="height:{{ ($day['games']+$day['recs']) ? $day['recs']/($day['games']+$day['recs'])*100 : 0 }}%;background:#54a5eb"></span></div><span class="rk-chart-label">{{ $day['day'] }}</span></div>@endforeach
        </div><div class="rk-chart-key"><span class="rk-tone-purple"><i></i> Battle sessions</span><span class="rk-tone-blue"><i></i> Recordings</span></div>
    </section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-school" aria-hidden="true"></i> Recent Teachers</h2><a href="{{ route('admin.teachers') }}">View all →</a></div>
        @forelse($recentTeachers as $educator)<div class="rk-recent-item"><span class="rk-avatar">{{ mb_strtoupper(mb_substr($educator->firstname,0,1).mb_substr($educator->lastname,0,1)) }}</span><div><strong>{{ $educator->firstname }} {{ $educator->lastname }}</strong><small>{{ $educator->school_name }}</small></div><span class="rk-pill {{ $educator->user?->email_verified_at ? 'done' : 'pending' }}">{{ $educator->user?->email_verified_at ? 'Active' : 'Inactive' }}</span></div>
        @empty<p class="rk-empty">No teachers yet.</p>@endforelse
    </section>
</div>
@endsection
