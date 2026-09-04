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
            You are {{ $nextLevelPoints - $student->total_points }} points away
            from unlocking Level {{ $student->current_level + 1 }}!
        </div>
        <a href="{{ route('student.activities.index') }}" class="hero-btn">
            Continue Learning →
        </a>
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
    <span class="xp-val">
        {{ number_format($student->total_points) }}
        / {{ number_format($nextLevelPoints) }} pts
    </span>
    <span class="level-pill-gray">Level {{ $student->current_level + 1 }}</span>
</div>

{{-- Trophy banner (only for top 3) --}}
@if($trophy)
<div style="background:linear-gradient(135deg,#1C1400,#3D2E00);
            border-radius:14px;padding:16px 22px;margin-bottom:18px;
            display:flex;align-items:center;gap:16px;
            border:2px solid {{ $trophy['color'] }}80;">
    <div style="font-size:60px;line-height:1;
                filter:drop-shadow(0 0 16px {{ $trophy['color'] }});">
        {{ $trophy['icon'] }}
    </div>
    <div>
        <div style="font-size:10px;color:rgba(255,255,255,0.5);
                    margin-bottom:3px;text-transform:uppercase;letter-spacing:.08em;">
            🏆 Class Rank #{{ $myRank }}
        </div>
        <div style="font-size:18px;font-weight:800;color:#fff;">
            {{ $trophy['label'] }}
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,0.65);margin-top:3px;">
            You are in the TOP {{ $myRank }} of your class! Keep reading! 🎉
        </div>
    </div>
</div>
@endif

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

<div class="row g-3 mb-4">

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
                    'Phonics'          => ['bg'=>'#DBEAFE','icon'=>'🔤'],
                    'Word Game'        => ['bg'=>'#EDE9FE','icon'=>'🧩'],
                    'Read Aloud'       => ['bg'=>'#FEF3C7','icon'=>'🎙️'],
                    'Vocabulary'       => ['bg'=>'#DCFCE7','icon'=>'📝'],
                    'Word Recognition' => ['bg'=>'#FFE4E6','icon'=>'👀'],
                    'Sound Blending'   => ['bg'=>'#E0F2FE','icon'=>'🔊'],
                ];
                $ic = $icons[$activity->activity_type] ?? ['bg'=>'#F3F4F6','icon'=>'📖'];
            @endphp
            <a href="{{ route('student.activities.show', $activity->id) }}" class="act-item">
                <div class="act-icon" style="background:{{ $ic['bg'] }};">{{ $ic['icon'] }}</div>
                <div>
                    <div class="act-title">{{ $activity->activity_name }}</div>
                    <div class="act-sub">
                        {{ $activity->activity_type }} ·
                        {{ $activity->duration_minutes }} min ·
                        ⭐ {{ $activity->points_reward }} pts
                    </div>
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

        {{-- Badges preview --}}
        <div class="dash-card mb-3">
            <div class="dash-card-title">
                My Badges
                <span style="font-size:11px;color:#9CA3AF;">
                    {{ count($earnedIds) }} / {{ $allBadges->count() }} earned
                </span>
            </div>

            @if($allBadges->count() > 0)
            <div class="badge-grid">
                @foreach($allBadges->take(8) as $badge)
                @php $isEarned = in_array($badge->id, $earnedIds); @endphp
                <div class="badge-item {{ !$isEarned ? 'badge-locked' : '' }}"
                     title="{{ $isEarned ? $badge->badge_name : '🔒 ' . $badge->description }}">
                    <div class="badge-icon"
                         style="background:{{ $isEarned ? '#FEF3C7' : '#F3F4F6' }};
                                border:2px solid {{ $isEarned ? '#F59E0B' : '#E5E7EB' }};
                                border-radius:10px;width:44px;height:44px;
                                display:flex;align-items:center;justify-content:center;
                                font-size:22px;
                                filter:{{ $isEarned ? 'none' : 'grayscale(1)' }};
                                opacity:{{ $isEarned ? '1' : '0.5' }};">
                        {{ $badge->badge_icon ?? '🏅' }}
                    </div>
                    <div class="badge-name"
                         style="font-size:10px;font-weight:600;
                                color:{{ $isEarned ? '#92400E' : '#9CA3AF' }};
                                margin-top:4px;text-align:center;line-height:1.2;">
                        {{ Str::limit($badge->badge_name, 10) }}
                    </div>
                    @if($isEarned)
                    <div style="font-size:8px;color:#B45309;text-align:center;">✨ Earned</div>
                    @else
                    <div style="font-size:9px;color:#D1D5DB;text-align:center;">🔒</div>
                    @endif
                </div>
                @endforeach
            </div>

            @if($allBadges->count() > 8)
            <div style="text-align:center;margin-top:8px;">
             
            </div>
            @endif

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
                    @else
                    <span style="font-size:11px;color:#9CA3AF;">{{ $i + 1 }}</span>
                    @endif
                </div>
                <div class="lb-av" style="background:#DBEAFE;color:#1E40AF;">
                    {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
                </div>
                <div class="lb-name">
                    {{ $s->firstname }}
                    @if($s->id == $myId)
                    <span style="font-size:10px;background:#DBEAFE;color:#1E40AF;
                                 padding:1px 6px;border-radius:10px;margin-left:3px;">
                        You
                    </span>
                    @endif
                </div>
                <div class="lb-pts">{{ number_format($s->total_points) }} pts</div>
            </div>
            @empty
            <div class="text-center text-muted small py-2">No classmates yet.</div>
            @endforelse
        </div>

    </div>
</div>

{{-- Full badges section --}}
<div class="dash-card">
    <div style="display:flex;align-items:center;
                justify-content:space-between;margin-bottom:16px;">
        <div style="font-size:14px;font-weight:700;color:#111827;">
            🏅 All Badges
        </div>
        <div style="font-size:11px;color:#9CA3AF;
                    background:#F3F4F6;padding:3px 10px;border-radius:20px;">
            {{ count($earnedIds) }} / {{ $allBadges->count() }} earned
        </div>
    </div>

    {{-- Earned badges --}}
    @if(count($earnedIds) > 0)
    <div style="margin-bottom:20px;">
        <div style="font-size:10px;font-weight:700;color:#6B7280;
                    text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
            ✅ Earned
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @foreach($earnedBadges as $sb)
            @if($sb->badge)
            <div style="background:linear-gradient(135deg,#FFFBEB,#FEF3C7);
                        border:2.5px solid #F59E0B;border-radius:14px;
                        padding:12px 14px;text-align:center;min-width:96px;
                        box-shadow:0 4px 12px rgba(245,158,11,0.2);
                        position:relative;"
                 title="{{ $sb->badge->description }}">
                <div style="position:absolute;top:5px;right:7px;font-size:10px;">✨</div>
                <div style="font-size:34px;margin-bottom:5px;">
                    {{ $sb->badge->badge_icon }}
                </div>
                <div style="font-size:11px;font-weight:700;
                            color:#92400E;line-height:1.3;">
                    {{ $sb->badge->badge_name }}
                </div>
                <div style="font-size:9px;color:#B45309;margin-top:3px;">
                    {{ \Carbon\Carbon::parse($sb->earned_at)->format('M d, Y') }}
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Locked badges --}}
    @php
        $lockedBadges = $allBadges->filter(fn($b) => !in_array($b->id, $earnedIds));
    @endphp
    @if($lockedBadges->count() > 0)
    <div>
        <div style="font-size:10px;font-weight:700;color:#9CA3AF;
                    text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">
            🔒 Locked — Complete activities to unlock!
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            @foreach($lockedBadges as $badge)
            <div style="background:#F9FAFB;border:2px solid #E5E7EB;
                        border-radius:14px;padding:12px 14px;
                        text-align:center;min-width:96px;position:relative;"
                 title="{{ $badge->description }}">
                <div style="position:absolute;top:5px;right:7px;font-size:10px;">🔒</div>
                <div style="font-size:34px;margin-bottom:5px;
                            filter:grayscale(1);opacity:0.5;">
                    {{ $badge->badge_icon }}
                </div>
                <div style="font-size:11px;font-weight:700;
                            color:#9CA3AF;line-height:1.3;">
                    {{ $badge->badge_name }}
                </div>
                <div style="font-size:9px;color:#D1D5DB;
                            margin-top:3px;line-height:1.4;">
                    {{ $badge->description }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($allBadges->count() === 0)
    <div class="text-center text-muted py-4">
        <div style="font-size:36px;">🏅</div>
        <div class="mt-2 small">No badges available yet.</div>
    </div>
    @endif
</div>

@endsection