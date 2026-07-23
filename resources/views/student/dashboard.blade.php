@extends('layouts.student')

@section('title', 'Dashboard')
@section('page-greet', 'Good day, ' . auth()->user()->student->firstname . '! 👋')
@section('page-sub', 'Keep reading and earn more points today!')

@section('content')

{{-- Hero banner --}}
<div class="hero-banner">
    <div>
        <div class="hero-title">Keep it up, {{ auth()->user()->student->firstname }}! 🌟</div>
        <div class="hero-sub">
            You are {{ $nextLevelPoints - $student->total_points }} points away from unlocking Level {{ $student->current_level + 1 }}!
        </div>
        <a href="{{ route('student.activities.index') }}" class="hero-btn">Continue Learning →</a>
    </div>
    <div class="hero-emoji">📚</div>
</div>

{{-- XP bar --}}
<div class="xp-row">
    <span class="level-pill">Level {{ $student->current_level }}</span>
    <span class="xp-label">Progress to Level {{ $student->current_level + 1 }}</span>
    <div class="xp-bar-bg">
        <div class="xp-bar-fill" style="width:{{ $xpPercent }}%;"></div>
    </div>
    <span class="xp-val">{{ number_format($student->total_points) }} / {{ number_format($nextLevelPoints) }} pts</span>
    <span class="level-pill-gray">Level {{ $student->current_level + 1 }}</span>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-emoji">✅</div>
            <div class="stat-value">{{ $activitiesDone }}</div>
            <div class="stat-label">Activities Done</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-emoji">⭐</div>
            <div class="stat-value">{{ number_format($student->total_points) }}</div>
            <div class="stat-label">Total Points</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-emoji">🏅</div>
            <div class="stat-value">{{ $badgesEarned }}</div>
            <div class="stat-label">Badges Earned</div>
        </div>
    </div>
    <div class="col-3">
        <div class="stat-card">
            <div class="stat-emoji">🔥</div>
            <div class="stat-value">{{ $streak }}</div>
            <div class="stat-label">Day Streak</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Activities --}}
    <div class="col-md-7">
        <div class="dash-card">
            <div class="dash-card-title">
                My Activities Today
                <a href="{{ route('student.activities.index') }}">See all →</a>
            </div>
            @forelse($activities as $activity)
            @php
                $result = $results->firstWhere('activity_id', $activity->id);
                if ($result && $result->status === 'completed') {
                    $badgeClass = 'b-done'; $badgeText = 'Done ✓';
                } else {
                    $badgeClass = 'b-new'; $badgeText = 'New';
                }
                $icons = [
                    'Phonics' => ['bg'=>'#DBEAFE','icon'=>'🔤'],
                    'Word Game' => ['bg'=>'#EDE9FE','icon'=>'🧩'],
                    'Read Aloud' => ['bg'=>'#FEF3C7','icon'=>'🎙️'],
                    'Vocabulary' => ['bg'=>'#DCFCE7','icon'=>'📝'],
                    'Word Recognition' => ['bg'=>'#FFE4E6','icon'=>'👀'],
                    'Sound Blending' => ['bg'=>'#E0F2FE','icon'=>'🔊'],
                ];
                $ic = $icons[$activity->activity_type] ?? ['bg'=>'#F3F4F6','icon'=>'📖'];
            @endphp
            <a href="{{ route('student.activities.show', $activity->id) }}" class="act-item">
                <div class="act-icon" style="background:{{ $ic['bg'] }};">{{ $ic['icon'] }}</div>
                <div>
                    <div class="act-title">{{ $activity->activity_name }}</div>
                    <div class="act-sub">{{ $activity->activity_type }} · {{ $activity->duration_minutes }} min · ⭐ {{ $activity->points_reward }} pts</div>
                </div>
                <span class="act-badge {{ $badgeClass }}">{{ $badgeText }}</span>
            </a>
            @empty
            <div class="text-center text-muted py-3">
                <div style="font-size:32px;">📭</div>
                <div class="small">No activities yet. Ask your teacher to add some!</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-md-5">
        {{-- Badges --}}
        <div class="dash-card mb-3">
            <div class="dash-card-title">
                My Badges
                <a href="#">View all →</a>
            </div>
            @php
                $allBadges = \App\Models\Badge::take(8)->get();
                $earnedIds = auth()->user()->student->badges->pluck('badge_id')->toArray();
            @endphp
            @if($allBadges->count())
            <div class="badge-grid">
                @foreach($allBadges as $badge)
                <div class="badge-item {{ !in_array($badge->id, $earnedIds) ? 'badge-locked' : '' }}">
                    <div class="badge-icon" style="background:#DBEAFE;">
                        {{ in_array($badge->id, $earnedIds) ? ($badge->badge_icon ?? '🏅') : '🔒' }}
                    </div>
                    <div class="badge-name">{{ Str::limit($badge->badge_name, 10) }}</div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-muted small py-2">No badges available yet.</div>
            @endif
        </div>

        {{-- Leaderboard --}}
        <div class="dash-card">
            <div class="dash-card-title">
                Class Leaderboard
                <a href="{{ route('student.leaderboard') }}">Full board →</a>
            </div>
            @php $myId = auth()->user()->student->id; @endphp
            @forelse($leaderboard as $i => $s)
            <div class="lb-item {{ $s->id == $myId ? 'me' : '' }}">
                <div class="lb-rank">
                    @if($i == 0) 🥇
                    @elseif($i == 1) 🥈
                    @elseif($i == 2) 🥉
                    @else <span style="font-size:11px;color:#9CA3AF;">{{ $i+1 }}</span>
                    @endif
                </div>
                <div class="lb-av" style="background:#DBEAFE;color:#1E40AF;">
                    {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
                </div>
                <div class="lb-name">
                    {{ $s->firstname }} {{ $s->id == $myId ? '(You)' : '' }}
                </div>
                <div class="lb-pts">{{ number_format($s->total_points) }} pts</div>
            </div>
            @empty
            <div class="text-center text-muted small py-2">No classmates yet.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection