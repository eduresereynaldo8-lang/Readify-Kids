@extends('layouts.admin')
@section('title', 'System Reports')
@section('page-title', 'System Reports')
@section('page-sub', 'Full system-wide statistics and performance overview.')

@section('content')

{{-- Top stat cards --}}
<div class="row g-3 mb-4">
    @foreach([
        ['👩‍🏫', 'Teachers',          $totalTeachers,   '#DBEAFE', '#1E40AF'],
        ['👦',   'Students',          $totalStudents,   '#DCFCE7', '#166534'],
        ['📖',   'Activities',        $totalActivities, '#EDE9FE', '#5B21B6'],
        ['🎙️',   'Recordings',        $totalRecordings, '#FEF3C7', '#92400E'],
        ['⚔️',   'Games Played',      $totalGames,      '#FFE4E6', '#9F1239'],
        ['🏆',   'Battles Won',       $totalWins,       '#DCFCE7', '#166534'],
        ['💀',   'Battles Lost',      $totalLosses,     '#FEE2E2', '#991B1B'],
        ['⭐',   'Total Points Earned', number_format($totalPoints), '#FEF9C3', '#854D0E'],
    ] as [$emoji, $label, $val, $bg, $color])
    <div class="col-6 col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:{{ $bg }};">
                <span style="font-size:18px;">{{ $emoji }}</span>
            </div>
            <div>
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-value" style="font-size:18px;">{{ $val }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">

    {{-- Monthly completions chart --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div class="dash-card-title">📅 Monthly Activity Completions (Last 6 months)</div>
            @php $maxM = max(array_column($monthlyData, 'count') ?: [1]); @endphp
            <div style="display:flex;align-items:flex-end;gap:10px;height:110px;padding:0 4px;">
                @foreach($monthlyData as $m)
                @php $h = $maxM > 0 ? max(4, round(($m['count'] / $maxM) * 100)) : 4; @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;">
                    <div style="font-size:9px;color:#9CA3AF;">{{ $m['count'] }}</div>
                    <div style="width:100%;border-radius:4px 4px 0 0;
                                background:#DC2626;height:{{ $h }}px;"></div>
                    <div style="font-size:10px;color:#9CA3AF;">{{ $m['month'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Battle win/loss ratio --}}
    <div class="col-md-3">
        <div class="dash-card h-100">
            <div class="dash-card-title">⚔️ Battle Results</div>
            @php
                $total  = $totalGames ?: 1;
                $winP   = round($totalWins   / $total * 100);
                $lossP  = round($totalLosses / $total * 100);
                $ongoP  = 100 - $winP - $lossP;
            @endphp
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['🏆 Won',     $winP,  '#22C55E', $totalWins],
                    ['💀 Lost',    $lossP, '#EF4444', $totalLosses],
                    ['⚔️ Ongoing', $ongoP, '#F59E0B', $totalGames - $totalWins - $totalLosses],
                ] as [$label, $pct, $color, $count])
                <div>
                    <div style="display:flex;justify-content:space-between;
                                font-size:11px;color:#6B7280;margin-bottom:3px;">
                        <span>{{ $label }}</span>
                        <span>{{ $count }} ({{ $pct }}%)</span>
                    </div>
                    <div style="background:#E5E7EB;border-radius:4px;height:8px;">
                        <div style="height:8px;border-radius:4px;
                                    background:{{ $color }};width:{{ $pct }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Activity type breakdown --}}
    <div class="col-md-3">
        <div class="dash-card h-100">
            <div class="dash-card-title">📊 Activities by Type</div>
            @php
            $typeColors = [
                'Phonics'         => ['bg'=>'#DBEAFE','color'=>'#1E40AF'],
                'Word Game'       => ['bg'=>'#EDE9FE','color'=>'#5B21B6'],
                'Read Aloud'      => ['bg'=>'#FEF3C7','color'=>'#92400E'],
                'Vocabulary'      => ['bg'=>'#DCFCE7','color'=>'#166534'],
                'Word Recognition'=> ['bg'=>'#FFE4E6','color'=>'#9F1239'],
                'Sound Blending'  => ['bg'=>'#E0F2FE','color'=>'#075985'],
            ];
            @endphp
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($byType as $row)
                @php $tc = $typeColors[$row->activity_type] ?? ['bg'=>'#F3F4F6','color'=>'#374151']; @endphp
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:5px 10px;border-radius:8px;
                            background:{{ $tc['bg'] }};">
                    <span style="font-size:11px;color:{{ $tc['color'] }};font-weight:600;">
                        {{ $row->activity_type }}
                    </span>
                    <span style="font-size:12px;font-weight:700;color:{{ $tc['color'] }};">
                        {{ $row->count }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- Reading skill averages --}}
    <div class="col-md-5">
        <div class="dash-card h-100">
            <div class="dash-card-title">🎙️ System-Wide Reading Skills</div>
            @if(array_sum($skills) > 0)
            @foreach($skills as $skill => $score)
            @php $c = $score >= 75 ? '#22C55E' : ($score >= 50 ? '#F59E0B' : '#EF4444'); @endphp
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:120px;font-size:12px;color:#6B7280;flex-shrink:0;">
                    {{ $skill }}
                </div>
                <div style="flex:1;background:#E5E7EB;border-radius:4px;height:8px;">
                    <div style="height:8px;border-radius:4px;
                                background:{{ $c }};width:{{ $score }}%;"></div>
                </div>
                <div style="width:36px;font-size:11px;color:#6B7280;text-align:right;">
                    {{ $score }}%
                </div>
            </div>
            @endforeach
            @else
            <div class="text-center text-muted small py-3">No evaluations yet.</div>
            @endif
        </div>
    </div>

    {{-- Top 5 teachers --}}
    <div class="col-md-3">
        <div class="dash-card h-100">
            <div class="dash-card-title">👩‍🏫 Top Teachers by Students</div>
            @forelse($topTeachers as $i => $t)
            <div style="display:flex;align-items:center;gap:10px;
                        padding:7px 0;border-bottom:1px solid #F9FAFB;">
                <div style="font-size:16px;width:20px;text-align:center;">
                    @if($i==0)🥇@elseif($i==1)🥈@elseif($i==2)🥉
                    @else <span style="font-size:11px;color:#9CA3AF;">#{{ $i+1 }}</span>
                    @endif
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $t->firstname }} {{ $t->lastname }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">{{ $t->school_name }}</div>
                </div>
                <span class="status-badge badge-blue">
                    {{ $t->students_count }} students
                </span>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No data yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Top 10 students system-wide --}}
    <div class="col-md-4">
        <div class="dash-card h-100">
            <div class="dash-card-title">🏆 Top 10 Students System-Wide</div>
            @forelse($topStudents as $i => $s)
            @php $avg = round($s->activityResults->avg('score') ?? 0, 1); @endphp
            <div style="display:flex;align-items:center;gap:8px;
                        padding:6px 0;border-bottom:1px solid #F9FAFB;">
                <div style="font-size:14px;width:20px;text-align:center;flex-shrink:0;">
                    @if($i==0)🥇@elseif($i==1)🥈@elseif($i==2)🥉
                    @else<span style="font-size:11px;color:#9CA3AF;">#{{ $i+1 }}</span>
                    @endif
                </div>
                <div style="width:26px;height:26px;border-radius:50%;
                            background:#DCFCE7;color:#166534;font-size:10px;
                            font-weight:700;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                    {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:11px;font-weight:600;color:#111827;">
                        {{ $s->firstname }} {{ $s->lastname }}
                    </div>
                    <div style="font-size:9px;color:#9CA3AF;">
                        {{ $s->teacher?->firstname }} · {{ $s->section }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:11px;font-weight:700;color:#F59E0B;">
                        ⭐ {{ number_format($s->total_points) }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">{{ $avg }}%</div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No data yet.</div>
            @endforelse
        </div>
    </div>

</div>

@endsection