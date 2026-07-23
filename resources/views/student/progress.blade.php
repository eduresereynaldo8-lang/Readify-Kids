@extends('layouts.student')
@section('title', 'My Progress')
@section('page-greet', 'My Progress 📊')
@section('page-sub', 'See how far you have come!')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-emoji">✅</div>
            <div class="stat-value">{{ $totalDone }}</div>
            <div class="stat-label">Activities Done</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-emoji">📈</div>
            <div class="stat-value">{{ $avgScore }}%</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-emoji">⭐</div>
            <div class="stat-value">{{ number_format($totalPoints) }}</div>
            <div class="stat-label">Total Points</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-emoji">🎙️</div>
            <div class="stat-value">{{ $recordings->count() }}</div>
            <div class="stat-label">Recordings</div>
        </div>
    </div>
</div>

{{-- Level progress --}}
<div class="dash-card mb-4">
    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">
        🎯 Level Progress
    </div>
    <div class="d-flex align-items-center gap-3">
        <span style="background:#185FA5;color:#fff;font-size:11px;font-weight:700;
                     padding:4px 12px;border-radius:20px;">Level {{ $student->current_level }}</span>
        <div style="flex:1;background:#E5E7EB;border-radius:6px;height:12px;">
            <div style="height:12px;border-radius:6px;width:{{ $xpPercent }}%;
                        background:linear-gradient(90deg,#185FA5,#60A5FA);"></div>
        </div>
        <span style="background:#E5E7EB;color:#9CA3AF;font-size:11px;font-weight:700;
                     padding:4px 12px;border-radius:20px;">Level {{ $student->current_level + 1 }}</span>
    </div>
    <div style="font-size:11px;color:#9CA3AF;margin-top:6px;text-align:center;">
        {{ number_format($totalPoints) }} / {{ number_format($nextLevelPoints) }} pts
        ({{ $xpPercent }}%) — {{ number_format($nextLevelPoints - $totalPoints) }} pts to next level
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- Skill breakdown --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:14px;">
                📊 Reading Skill Breakdown
            </div>
            @if(array_sum($skills) > 0)
            @foreach($skills as $skill => $score)
            @php
                $color = $score >= 75 ? '#22C55E' : ($score >= 50 ? '#F59E0B' : '#EF4444');
            @endphp
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:110px;font-size:12px;color:#6B7280;flex-shrink:0;">{{ $skill }}</div>
                <div style="flex:1;background:#E5E7EB;border-radius:4px;height:8px;">
                    <div style="height:8px;border-radius:4px;background:{{ $color }};
                                width:{{ $score }}%;"></div>
                </div>
                <div style="width:36px;font-size:11px;color:#6B7280;text-align:right;">{{ $score }}%</div>
            </div>
            @endforeach
            @else
            <div class="text-center text-muted small py-3">
                No evaluations yet. Complete some Read Aloud activities!
            </div>
            @endif
        </div>
    </div>

    {{-- Activity type breakdown --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:14px;">
                🗂️ Activities by Type
            </div>
            @php
            $typeColors = [
                'Phonics'=>'#DBEAFE','Word Game'=>'#EDE9FE',
                'Read Aloud'=>'#FEF3C7','Vocabulary'=>'#DCFCE7',
                'Word Recognition'=>'#FFE4E6','Sound Blending'=>'#E0F2FE',
            ];
            $typeEmojis = [
                'Phonics'=>'🔤','Word Game'=>'🧩','Read Aloud'=>'🎙️',
                'Vocabulary'=>'📝','Word Recognition'=>'👀','Sound Blending'=>'🔊',
            ];
            @endphp
            @if($byType->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($byType as $type => $count)
                <div style="background:{{ $typeColors[$type] ?? '#F3F4F6' }};
                            border-radius:10px;padding:10px 14px;text-align:center;min-width:80px;">
                    <div style="font-size:20px;">{{ $typeEmojis[$type] ?? '📖' }}</div>
                    <div style="font-size:18px;font-weight:700;color:#111827;">{{ $count }}</div>
                    <div style="font-size:10px;color:#6B7280;">{{ $type }}</div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-muted small py-3">No activities completed yet.</div>
            @endif
        </div>
    </div>
</div>

{{-- Recent activity history --}}
<div class="dash-card">
    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">
        📋 Recent Activity History
    </div>
    @forelse($results->take(10) as $result)
    @php
        $scoreColor = $result->score >= 75 ? '#166534' : ($result->score >= 50 ? '#92400E' : '#991B1B');
        $scoreBg    = $result->score >= 75 ? '#DCFCE7' : ($result->score >= 50 ? '#FEF3C7' : '#FEE2E2');
        $emoji      = $typeEmojis[$result->activity->activity_type ?? ''] ?? '📖';
    @endphp
    <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;
                border-radius:10px;background:#F9FAFB;border:1px solid #E5E7EB;margin-bottom:8px;">
        <div style="font-size:22px;">{{ $emoji }}</div>
        <div style="flex:1;">
            <div style="font-size:12px;font-weight:600;color:#111827;">
                {{ $result->activity->activity_name ?? 'Activity' }}
            </div>
            <div style="font-size:10px;color:#9CA3AF;margin-top:2px;">
                {{ $result->activity->activity_type ?? '' }} ·
                {{ \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') }}
            </div>
        </div>
        <div style="font-size:14px;font-weight:700;padding:4px 12px;border-radius:20px;
                    background:{{ $scoreBg }};color:{{ $scoreColor }};">
            {{ $result->score }}%
        </div>
        <div style="font-size:11px;font-weight:600;color:#F59E0B;">
            +{{ $result->activity->points_reward ?? 0 }} pts
        </div>
    </div>
    @empty
    <div class="text-center py-4">
        <div style="font-size:40px;">📭</div>
        <div class="text-muted small mt-2">No activities completed yet. Start learning!</div>
    </div>
    @endforelse
</div>

@endsection