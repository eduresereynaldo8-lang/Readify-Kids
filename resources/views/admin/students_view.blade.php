@extends('layouts.admin')
@section('title', 'View Student')
@section('page-title', 'Student Profile')
@section('page-sub', 'Detailed view of student performance.')

@section('content')

{{-- Profile header --}}
<div style="background:linear-gradient(135deg,#185FA5,#2563EB);
            border-radius:14px;padding:20px 24px;margin-bottom:20px;
            display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
    <div style="width:60px;height:60px;border-radius:50%;
                background:rgba(255,255,255,0.2);color:#fff;
                font-size:22px;font-weight:800;
                display:flex;align-items:center;justify-content:center;
                border:3px solid rgba(255,255,255,0.4);">
        {{ strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1)) }}
    </div>
    <div style="flex:1;">
        <div style="font-size:20px;font-weight:800;color:#fff;">
            {{ $student->firstname }} {{ $student->lastname }}
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:2px;">
            ID: {{ $student->student_number }} ·
            Section: {{ $student->section ?? '—' }} ·
            Level {{ $student->current_level }} ·
            Teacher: {{ $student->teacher?->firstname }} {{ $student->teacher?->lastname }}
        </div>
    </div>
    <div class="d-flex gap-3">
        <div style="text-align:center;color:#fff;">
            <div style="font-size:22px;font-weight:800;">{{ number_format($student->total_points) }}</div>
            <div style="font-size:11px;opacity:.75;">Total Points</div>
        </div>
        <div style="text-align:center;color:#fff;">
            <div style="font-size:22px;font-weight:800;">{{ $avgScore }}%</div>
            <div style="font-size:11px;opacity:.75;">Avg Score</div>
        </div>
        <div style="text-align:center;color:#fff;">
            <div style="font-size:22px;font-weight:800;">{{ $battlesWon }}</div>
            <div style="font-size:11px;opacity:.75;">Battles Won</div>
        </div>
        <div style="text-align:center;color:#fff;">
            <div style="font-size:22px;font-weight:800;">{{ $totalCompleted }}</div>
            <div style="font-size:11px;opacity:.75;">Activities Done</div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Activity Results --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div class="dash-card-title">📖 Activity Results</div>
            @forelse($student->activityResults->sortByDesc('completed_at')->take(10) as $r)
            @php
                $c = $r->score >= 75 ? '#166534' : ($r->score >= 50 ? '#92400E' : '#991B1B');
                $b = $r->score >= 75 ? '#DCFCE7' : ($r->score >= 50 ? '#FEF3C7' : '#FEE2E2');
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:8px 0;border-bottom:1px solid #F3F4F6;">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $r->activity?->activity_name ?? '—' }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ \Carbon\Carbon::parse($r->completed_at)->format('M d, Y') }}
                    </div>
                </div>
                <span style="background:{{ $b }};color:{{ $c }};
                             font-size:11px;font-weight:700;
                             padding:2px 10px;border-radius:20px;">
                    {{ $r->score }}%
                </span>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No activity results yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Battle Sessions --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div class="dash-card-title">⚔️ Battle Sessions</div>
            @forelse($gameSessions->take(10) as $gs)
            @php
                $sColor = $gs->status==='won'?'#166534':($gs->status==='lost'?'#991B1B':'#92400E');
                $sBg    = $gs->status==='won'?'#DCFCE7':($gs->status==='lost'?'#FEE2E2':'#FEF3C7');
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:8px 0;border-bottom:1px solid #F3F4F6;">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $gs->activity?->activity_name ?? '—' }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        vs {{ $gs->enemy?->name }} ·
                        {{ \Carbon\Carbon::parse($gs->created_at)->format('M d, Y') }}
                    </div>
                </div>
                <span style="background:{{ $sBg }};color:{{ $sColor }};
                             font-size:11px;font-weight:700;
                             padding:2px 10px;border-radius:20px;">
                    {{ ucfirst($gs->status) }}
                </span>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No battle sessions yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Badges --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">🏅 Earned Badges ({{ $student->studentBadges->count() }})</div>
            @if($student->studentBadges->count())
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($student->studentBadges as $sb)
                @if($sb->badge)
                <div style="background:#FEF3C7;border:2px solid #F59E0B;
                            border-radius:10px;padding:8px 12px;text-align:center;
                            min-width:80px;">
                    <div style="font-size:26px;">{{ $sb->badge->badge_icon }}</div>
                    <div style="font-size:10px;font-weight:700;color:#92400E;margin-top:3px;">
                        {{ $sb->badge->badge_name }}
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div class="text-center text-muted small py-3">No badges earned yet.</div>
            @endif
        </div>
    </div>

    {{-- Voice Recordings --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">🎙️ Voice Recordings ({{ $recordings->count() }})</div>
            @forelse($recordings->take(8) as $rec)
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:7px 0;border-bottom:1px solid #F3F4F6;">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $rec->activity?->activity_name ?? '—' }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ \Carbon\Carbon::parse($rec->created_at)->format('M d, Y h:i A') }}
                    </div>
                </div>
                <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;
                             background:{{ $rec->status==='evaluated'?'#DCFCE7':'#FEF3C7' }};
                             color:{{ $rec->status==='evaluated'?'#166534':'#92400E' }};">
                    {{ ucfirst($rec->status) }}
                </span>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No recordings yet.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('admin.students') }}" class="btn btn-sm btn-outline-secondary">
        ← Back to Students
    </a>
</div>

@endsection