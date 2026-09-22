@extends('layouts.student')
@section('title', 'My Dashboard')
@section('greeting', 'Good day, ' . $student->firstname . '!')
@section('subtitle', "Read, learn, and grow! You're doing great!")
@section('content')
<div class="rk-stats">
    <x-dashboard.stat-card label="Activities Done" :value="$activitiesDone" icon="book" hint="Keep it up!" :href="route('student.progress')" />
    <x-dashboard.stat-card label="Total Points" :value="number_format($student->total_points)" icon="star" tone="green" hint="Every little effort counts!" :href="route('student.leaderboard')" />
    <x-dashboard.stat-card label="Badges Earned" :value="$badgesEarned" icon="shield-star" tone="purple" hint="Look how far you've come!" href="#all-badges" />
    <x-dashboard.stat-card label="Day Streak" :value="$streak" icon="flame" tone="pink" hint="Days completing activities" :href="route('student.progress')" />
</div>
<section class="rk-journey">
    <div class="rk-journey-copy"><h2>You're on your reading journey!</h2><p>Small steps every day lead to big progress.</p><a class="rk-button rk-button-yellow" href="{{ route('student.activities.index') }}">Continue Learning <i class="ti ti-arrow-right" aria-hidden="true"></i></a></div>
    <div class="rk-book-scene" aria-hidden="true"><span class="spark one">✦</span><span class="spark two">★</span><span class="spark three">✦</span><i class="ti ti-book-2"></i><span class="rk-book-letter">Aa</span></div>
</section>
<div class="rk-level-row">
    <strong>Level {{ $student->current_level }}</strong><span>{{ max(0, $nextLevelPoints - $student->total_points) }} points to Level {{ $student->current_level + 1 }}</span>
    <div class="rk-progress" role="progressbar" aria-label="Progress to next level" aria-valuenow="{{ $xpPercent }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $xpPercent }}%"></span></div>
    <small>{{ number_format($student->total_points) }} / {{ number_format($nextLevelPoints) }} pts</small>
    @if($trophy)<a href="{{ route('student.leaderboard') }}" class="rk-level-trophy">{{ $trophy['icon'] }} {{ $trophy['label'] }} · Class rank #{{ $myRank }}</a>@endif
</div>
<div class="rk-student-middle">
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-book" aria-hidden="true"></i> My Activities Today</h2><a href="{{ route('student.activities.index') }}">See all →</a></div>
        <div class="rk-activity-list">
        @forelse($activities as $activity)
            @php
                $completed = in_array($activity->id, $completedActivityIds);
                $readAloud = $activity->activity_type === 'Read Aloud';
                $link = $readAloud ? route('student.readaloud.show', $activity->id) : ($activity->battle_mode ? route('student.game.start', $activity->id) : route('student.activities.show', $activity->id));
                $tone = $readAloud ? 'yellow' : ($activity->battle_mode ? 'purple' : 'green');
            @endphp
            <a class="rk-activity rk-tone-{{ $tone }}" href="{{ $link }}"><span class="rk-activity-icon"><i class="ti ti-{{ $readAloud ? 'microphone' : ($activity->battle_mode ? 'swords' : 'alphabet-latin') }}" aria-hidden="true"></i></span>
                <div class="rk-activity-copy"><strong>{{ $activity->activity_name }}</strong><small>{{ $activity->activity_type }} · {{ $activity->duration_minutes }} min · <span>★ {{ $activity->points_reward }} pts</span></small></div>
                <span class="rk-pill {{ $completed ? 'done' : '' }}">{{ $completed ? 'Done ✓' : 'Start' }}</span>
            </a>
        @empty<div class="rk-empty"><i class="ti ti-books" aria-hidden="true"></i><p>Your next adventure is on its way.<br>Ask your teacher about new activities!</p></div>@endforelse
        </div>
    </section>
    <section class="rk-card">
        <div class="rk-card-heading"><h2><i class="ti ti-star" aria-hidden="true"></i> My Badges</h2><small>{{ count($earnedIds) }} / {{ $allBadges->count() }} earned</small></div>
        <div class="rk-badge-grid">
            @forelse($allBadges->take(8) as $badge)
                @php $unlocked = in_array($badge->id, $earnedIds); @endphp
                <div class="rk-badge {{ $unlocked ? '' : 'locked' }}" title="{{ $badge->description }}"><span class="rk-badge-medal" aria-hidden="true">{{ $badge->badge_icon ?? '🏅' }}</span><strong>{{ $badge->badge_name }}</strong><small>{{ $unlocked ? '★ Earned' : '🔒 Locked' }}</small></div>
            @empty<div class="rk-empty">Your badges will appear here.</div>@endforelse
        </div><div class="rk-card-footer"><a href="#all-badges" class="rk-card-link">Explore all badges →</a></div>
    </section>
</div>
<div class="rk-student-bottom">
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-chart-bar" aria-hidden="true"></i> My Reading Progress</h2><a href="{{ route('student.progress') }}">Details →</a></div>@include('dashboard.partials.skills')</section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-clock" aria-hidden="true"></i> Recent Activity</h2></div>
        @forelse($results as $result)
            <div class="rk-recent-item rk-tone-{{ $result->status === 'completed' ? 'green' : 'purple' }}"><span class="rk-skill-icon"><i class="ti ti-{{ $result->status === 'completed' ? 'circle-check' : 'book' }}" aria-hidden="true"></i></span><div><strong>{{ $result->status === 'completed' ? 'Completed' : 'Started' }}: {{ $result->activity->activity_name ?? 'Activity' }}</strong><small>{{ ($result->completed_at ? \Carbon\Carbon::parse($result->completed_at) : $result->created_at)->format('M j, Y') }}@if($result->score !== null) · {{ round($result->score, 1) }}% score @endif</small></div></div>
        @empty<div class="rk-empty"><i class="ti ti-sparkles" aria-hidden="true"></i><p>Ready for your first chapter?<br>Complete an activity to get started.</p></div>@endforelse
    </section>
    <section class="rk-card"><div class="rk-card-heading"><h2><i class="ti ti-trophy" aria-hidden="true"></i> Class Leaderboard</h2><a href="{{ route('student.leaderboard') }}">Full board →</a></div>@include('dashboard.partials.leaderboard', ['ranking'=>$leaderboard, 'myId'=>$student->id, 'showScores'=>false])</section>
</div>
<details class="rk-card rk-all-badges" id="all-badges">
    <summary>All Badges <small>· {{ count($earnedIds) }} of {{ $allBadges->count() }} earned</small></summary>
    <div class="rk-badge-grid">@foreach($allBadges as $badge)
        @php $earned = $earnedBadges->firstWhere('badge_id', $badge->id); @endphp
        <div class="rk-badge {{ $earned ? '' : 'locked' }}"><span class="rk-badge-medal" aria-hidden="true">{{ $badge->badge_icon ?? '🏅' }}</span><strong>{{ $badge->badge_name }}</strong><p>{{ $badge->description }}</p><small>{{ $earned ? 'Earned ' . \Carbon\Carbon::parse($earned->earned_at)->format('M j, Y') : '🔒 Keep reading to unlock' }}</small></div>
    @endforeach</div>@if($allBadges->isEmpty())<p class="rk-empty">No badges available yet.</p>@endif
</details>
@endsection
