@extends('layouts.teacher')
@section('title', 'Progress & Reports')
@section('page-title', 'Progress & Reports')
@section('page-sub', 'Monitor class reading performance.')

@section('content')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DBEAFE;">
                <i class="ti ti-chart-line" style="color:#1E40AF;"></i>
            </div>
            <div>
                <div class="metric-label">Class Avg. Score</div>
                <div class="metric-value">{{ $classAvg }}%</div>
                <div class="metric-sub">Across all activities</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;">
                <i class="ti ti-clipboard-check" style="color:#166534;"></i>
            </div>
            <div>
                <div class="metric-label">Total Completions</div>
                <div class="metric-value">{{ $totalDone }}</div>
                <div class="metric-sub">All time</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;">
                <i class="ti ti-trending-up" style="color:#166534;"></i>
            </div>
            <div>
                <div class="metric-label">On Track</div>
                <div class="metric-value">{{ $onTrack }}</div>
                <div class="metric-sub">Score ≥ 75%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEE2E2;">
                <i class="ti ti-alert-circle" style="color:#991B1B;"></i>
            </div>
            <div>
                <div class="metric-label">Need Intervention</div>
                <div class="metric-value">{{ $struggling }}</div>
                <div class="metric-sub">Score below 50%</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- Weekly bar chart --}}
    <div class="col-md-7">
        <div class="dash-card h-100">
            <div class="dash-card-title">Weekly Activity Completions</div>
            @php $maxCount = max(array_column($weeklyData, 'count') ?: [1]); @endphp
            <div style="display:flex;align-items:flex-end;gap:10px;height:120px;padding:0 4px;">
                @foreach($weeklyData as $day)
                @php
                    $height = $maxCount > 0 ? round(($day['count'] / $maxCount) * 110) : 4;
                    $height = max($height, 4);
                    $isToday = $day['day'] === now()->format('D');
                @endphp
                <div style="flex:1;display:flex;flex-direction:column;
                            align-items:center;gap:4px;">
                    <div style="font-size:10px;color:#6B7280;">{{ $day['count'] }}</div>
                    <div style="width:100%;border-radius:4px 4px 0 0;
                                background:{{ $isToday ? '#185FA5' : '#BFDBFE' }};
                                height:{{ $height }}px;transition:height 0.5s;">
                    </div>
                    <div style="font-size:10px;color:{{ $isToday ? '#185FA5' : '#9CA3AF' }};
                                font-weight:{{ $isToday ? '700' : '400' }};">
                        {{ $day['day'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Status donut --}}
    <div class="col-md-5">
        <div class="dash-card h-100">
            <div class="dash-card-title">Student Status Breakdown</div>
            @php
                $total    = $students->count();
                $onTrackP = $total > 0 ? round($onTrack / $total * 100) : 0;
                $needH    = $students->filter(fn($s) =>
                                ($s->activityResults->avg('score') ?? 0) >= 50 &&
                                ($s->activityResults->avg('score') ?? 0) < 75)->count();
                $needHP   = $total > 0 ? round($needH / $total * 100) : 0;
                $strugP   = $total > 0 ? round($struggling / $total * 100) : 0;
            @endphp
            <div class="d-flex align-items-center gap-4">
                {{-- Simple donut using conic-gradient --}}
                <div style="width:90px;height:90px;border-radius:50%;flex-shrink:0;
                            background:conic-gradient(
                                #22C55E 0% {{ $onTrackP }}%,
                                #F59E0B {{ $onTrackP }}% {{ $onTrackP + $needHP }}%,
                                #EF4444 {{ $onTrackP + $needHP }}% 100%
                            );position:relative;">
                    <div style="position:absolute;width:56px;height:56px;
                                background:#fff;border-radius:50%;
                                top:50%;left:50%;transform:translate(-50%,-50%);
                                display:flex;align-items:center;justify-content:center;
                                font-size:11px;font-weight:700;color:#374151;">
                        {{ $total }}
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#22C55E;flex-shrink:0;"></div>
                        On Track — {{ $onTrack }} ({{ $onTrackP }}%)
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#F59E0B;flex-shrink:0;"></div>
                        Needs Help — {{ $needH }} ({{ $needHP }}%)
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#EF4444;flex-shrink:0;"></div>
                        Struggling — {{ $struggling }} ({{ $strugP }}%)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- Skill breakdown --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div class="dash-card-title">Class Reading Skill Breakdown</div>
            @if(array_sum($skills) > 0)
            @foreach($skills as $skill => $score)
            @php
                $color = $score >= 75 ? '#22C55E' : ($score >= 50 ? '#F59E0B' : '#EF4444');
            @endphp
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:120px;font-size:12px;color:#6B7280;flex-shrink:0;">
                    {{ $skill }}
                </div>
                <div style="flex:1;background:#E5E7EB;border-radius:4px;height:8px;">
                    <div style="height:8px;border-radius:4px;
                                background:{{ $color }};width:{{ $score }}%;"></div>
                </div>
                <div style="width:36px;font-size:11px;
                            color:#6B7280;text-align:right;">
                    {{ $score }}%
                </div>
            </div>
            @endforeach
            @else
            <div class="text-center text-muted small py-3">
                No evaluation data yet. Complete some Read Aloud evaluations!
            </div>
            @endif
        </div>
    </div>

    {{-- Activity type breakdown --}}
    <div class="col-md-6">
        <div class="dash-card h-100">
            <div class="dash-card-title">Completions by Activity Type</div>
            @php
            $typeColors = [
                'Phonics'         => ['bg'=>'#DBEAFE','color'=>'#1E40AF','emoji'=>'🔤'],
                'Word Game'       => ['bg'=>'#EDE9FE','color'=>'#5B21B6','emoji'=>'🧩'],
                'Read Aloud'      => ['bg'=>'#FEF3C7','color'=>'#92400E','emoji'=>'🎙️'],
                'Vocabulary'      => ['bg'=>'#DCFCE7','color'=>'#166534','emoji'=>'📝'],
                'Word Recognition'=> ['bg'=>'#FFE4E6','color'=>'#9F1239','emoji'=>'👀'],
                'Sound Blending'  => ['bg'=>'#E0F2FE','color'=>'#075985','emoji'=>'🔊'],
            ];
            @endphp
            @if($byType->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($byType as $type => $count)
                @php $tc = $typeColors[$type] ?? ['bg'=>'#F3F4F6','color'=>'#374151','emoji'=>'📖']; @endphp
                <div style="background:{{ $tc['bg'] }};border-radius:10px;
                            padding:12px 16px;text-align:center;min-width:85px;">
                    <div style="font-size:22px;">{{ $tc['emoji'] }}</div>
                    <div style="font-size:18px;font-weight:700;
                                color:{{ $tc['color'] }};margin:2px 0;">
                        {{ $count }}
                    </div>
                    <div style="font-size:10px;color:{{ $tc['color'] }};">{{ $type }}</div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-muted small py-3">No completions yet.</div>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Top performers --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">🏆 Top Performers</div>
            @forelse($topStudents as $i => $student)
            @php $avg = round($student->activityResults->avg('score') ?? 0, 1); @endphp
            <div style="display:flex;align-items:center;gap:10px;
                        padding:9px 10px;border-radius:8px;margin-bottom:6px;
                        background:#F9FAFB;border:1px solid #E5E7EB;">
                <div style="font-size:18px;width:24px;text-align:center;flex-shrink:0;">
                    @if($i==0)🥇@elseif($i==1)🥈@elseif($i==2)🥉
                    @else <span style="font-size:12px;color:#9CA3AF;">#{{ $i+1 }}</span>
                    @endif
                </div>
                <div style="width:30px;height:30px;border-radius:50%;
                            background:#DBEAFE;color:#1E40AF;flex-shrink:0;
                            display:flex;align-items:center;justify-content:center;
                            font-size:11px;font-weight:700;">
                    {{ strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $student->firstname }} {{ $student->lastname }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ $student->section }} · Level {{ $student->current_level }}
                    </div>
                </div>
                <div style="font-size:14px;font-weight:700;color:#22C55E;">
                    {{ $avg }}%
                </div>
                <div style="font-size:11px;color:#F59E0B;font-weight:600;">
                    ⭐ {{ number_format($student->total_points) }}
                </div>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No data yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Needs intervention --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">⚠️ Students Needing Intervention</div>
            @forelse($needHelp as $student)
            @php $avg = round($student->activityResults->avg('score') ?? 0, 1); @endphp
            <div style="display:flex;align-items:center;gap:10px;
                        padding:9px 10px;border-radius:8px;margin-bottom:6px;
                        background:#FFF7F7;border:1px solid #FEE2E2;">
                <div style="width:30px;height:30px;border-radius:50%;
                            background:#FEE2E2;color:#991B1B;flex-shrink:0;
                            display:flex;align-items:center;justify-content:center;
                            font-size:11px;font-weight:700;">
                    {{ strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $student->firstname }} {{ $student->lastname }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ $student->section }} · Level {{ $student->current_level }}
                    </div>
                </div>
                <div>
                    <div style="background:#E5E7EB;border-radius:4px;
                                height:5px;width:80px;margin-bottom:3px;">
                        <div style="height:5px;border-radius:4px;
                                    background:#EF4444;width:{{ $avg }}%;"></div>
                    </div>
                    <div style="font-size:11px;font-weight:700;
                                color:#EF4444;text-align:right;">
                        {{ $avg }}%
                    </div>
                </div>
                <a href="{{ route('teacher.students.show', $student->id) }}"
                   class="btn btn-sm btn-outline-danger" style="font-size:11px;">
                    View
                </a>
            </div>
            @empty
            <div class="text-center py-3">
                <div style="font-size:32px;">🎉</div>
                <div class="text-muted small mt-2">All students are doing well!</div>
            </div>
            @endforelse
        </div>
    </div>

</div>
@endsection