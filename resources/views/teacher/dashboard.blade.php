@extends('layouts.teacher')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', 'Here\'s what\'s happening in your class today.')

@section('content')

{{-- Metric Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DBEAFE;">
                <i class="ti ti-users" style="color:#1E40AF;"></i>
            </div>
            <div>
                <div class="metric-label">Total Students</div>
                <div class="metric-value">{{ $total }}</div>
                <div class="metric-sub">Grade 2 Learners</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;">
                <i class="ti ti-activity" style="color:#166534;"></i>
            </div>
            <div>
                <div class="metric-label">Active Today</div>
                <div class="metric-value">{{ $activeToday ?? 0 }}</div>
                <div class="metric-sub">Logged in today</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEF3C7;">
                <i class="ti ti-clipboard-check" style="color:#92400E;"></i>
            </div>
            <div>
                <div class="metric-label">Activities Done</div>
                <div class="metric-value">{{ $activitiesDone ?? 0 }}</div>
                <div class="metric-sub">This week</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEE2E2;">
                <i class="ti ti-microphone" style="color:#991B1B;"></i>
            </div>
            <div>
                <div class="metric-label">Pending Reviews</div>
                <div class="metric-value">{{ $pendingReviews ?? 0 }}</div>
                <div class="metric-sub">Recordings to evaluate</div>
            </div>
        </div>
    </div>
</div>

{{-- Student Progress table + Status Breakdown --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="dash-card">
            <div class="dash-card-title">
                Student Reading Progress
                <a href="{{ route('teacher.students.index') }}">View all →</a>
            </div>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Level</th>
                        <th>Score</th>
                        <th>Progress</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    @php
                        $avg = round($student->activityResults->avg('score') ?? 0, 1);
                        if ($avg >= 75)     { $status = 'On Track';   $badgeClass = 'badge-green'; $color = '#22C55E'; }
                        elseif ($avg >= 50) { $status = 'Needs Help'; $badgeClass = 'badge-amber'; $color = '#F59E0B'; }
                        else               { $status = 'Struggling';  $badgeClass = 'badge-red';   $color = '#EF4444'; }
                    @endphp
                    <tr>
                        <td><strong>{{ $student->firstname }} {{ $student->lastname }}</strong></td>
                        <td>
                            <span class="status-badge badge-blue">
                                Level {{ $student->current_level }}
                            </span>
                        </td>
                        <td>{{ $avg }}%</td>
                        <td>
                            <div class="prog-bg">
                                <div class="prog-fill"
                                     style="width:{{ $avg }}%;background:{{ $color }};"></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $badgeClass }}">{{ $status }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            No students yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Student Status Breakdown (replaces Recent Activity) --}}
    <div class="col-md-5">
        <div class="dash-card h-100">
            <div class="dash-card-title">Student Status Breakdown</div>
            @php
                $onTrackP   = $total > 0 ? round($onTrack    / $total * 100) : 0;
                $needsHelpP = $total > 0 ? round($needsHelp  / $total * 100) : 0;
                $strugglingP= $total > 0 ? round($struggling  / $total * 100) : 0;
            @endphp
            @if($total > 0)
            <div class="d-flex align-items-center gap-4">
                {{-- Donut --}}
                <div style="width:90px;height:90px;border-radius:50%;flex-shrink:0;
                            background:conic-gradient(
                                #22C55E 0% {{ $onTrackP }}%,
                                #F59E0B {{ $onTrackP }}% {{ $onTrackP + $needsHelpP }}%,
                                #EF4444 {{ $onTrackP + $needsHelpP }}% 100%
                            );position:relative;">
                    <div style="position:absolute;width:56px;height:56px;
                                background:#fff;border-radius:50%;
                                top:50%;left:50%;transform:translate(-50%,-50%);
                                display:flex;align-items:center;justify-content:center;
                                font-size:13px;font-weight:700;color:#374151;">
                        {{ $total }}
                    </div>
                </div>
                {{-- Legend --}}
                <div class="d-flex flex-column gap-2 flex-grow-1">
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;
                                    background:#22C55E;flex-shrink:0;"></div>
                        On Track — {{ $onTrack }} ({{ $onTrackP }}%)
                    </div>
                    <div style="background:#E5E7EB;border-radius:4px;height:5px;margin-bottom:2px;">
                        <div style="height:5px;border-radius:4px;
                                    background:#22C55E;width:{{ $onTrackP }}%;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;
                                    background:#F59E0B;flex-shrink:0;"></div>
                        Needs Help — {{ $needsHelp }} ({{ $needsHelpP }}%)
                    </div>
                    <div style="background:#E5E7EB;border-radius:4px;height:5px;margin-bottom:2px;">
                        <div style="height:5px;border-radius:4px;
                                    background:#F59E0B;width:{{ $needsHelpP }}%;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:12px;">
                        <div style="width:10px;height:10px;border-radius:50%;
                                    background:#EF4444;flex-shrink:0;"></div>
                        Struggling — {{ $struggling }} ({{ $strugglingP }}%)
                    </div>
                    <div style="background:#E5E7EB;border-radius:4px;height:5px;">
                        <div style="height:5px;border-radius:4px;
                                    background:#EF4444;width:{{ $strugglingP }}%;"></div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center text-muted small py-4">
                <div style="font-size:32px;">👥</div>
                <div class="mt-2">No students yet. Add students to see their status.</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Class Reading Skill Breakdown (replaces Pending Voice Evaluations) --}}
<div class="row g-3">
    <div class="col-md-7">
        <div class="dash-card h-100">
            <div class="dash-card-title">📊 Class Reading Skill Breakdown</div>
            @if(array_sum($skills) > 0)
            <div class="d-flex flex-column gap-3">
                @foreach($skills as $skill => $score)
                @php
                    $color      = $score >= 75 ? '#22C55E' : ($score >= 50 ? '#F59E0B' : '#EF4444');
                    $label      = $score >= 75 ? 'Good'    : ($score >= 50 ? 'Average' : 'Needs Work');
                    $labelColor = $score >= 75 ? '#166534' : ($score >= 50 ? '#92400E' : '#991B1B');
                    $labelBg    = $score >= 75 ? '#DCFCE7' : ($score >= 50 ? '#FEF3C7' : '#FEE2E2');
                @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;
                                align-items:center;margin-bottom:5px;">
                        <span style="font-size:12px;color:#374151;font-weight:600;">
                            {{ $skill }}
                        </span>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="background:{{ $labelBg }};color:{{ $labelColor }};
                                         font-size:10px;padding:1px 8px;
                                         border-radius:20px;font-weight:600;">
                                {{ $label }}
                            </span>
                            <span style="font-size:12px;font-weight:700;
                                         color:#6B7280;min-width:36px;text-align:right;">
                                {{ $score }}%
                            </span>
                        </div>
                    </div>
                    <div style="background:#E5E7EB;border-radius:6px;height:10px;">
                        <div style="height:10px;border-radius:6px;
                                    background:{{ $color }};
                                    width:{{ $score }}%;
                                    transition:width 0.6s ease;"></div>
                    </div>
                </div>
                @endforeach
            </div>
            <div style="margin-top:12px;font-size:11px;color:#9CA3AF;text-align:right;">
                Based on teacher evaluations ·
                <a href="{{ route('teacher.progress') }}"
                   style="color:#185FA5;text-decoration:none;font-weight:600;">
                    View full report →
                </a>
            </div>
            @else
            <div class="text-center text-muted small py-4">
                <div style="font-size:32px;">📊</div>
                <div class="mt-2">No evaluation data yet.</div>
                <div class="mt-1">
                    Complete some Read Aloud evaluations to see skill breakdown!
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Top Students preview --}}
    <div class="col-md-5">
        <div class="dash-card h-100">
            <div class="dash-card-title">
                🏆 Top Students
                <a href="{{ route('teacher.leaderboard') }}"
                   style="font-size:11px;color:#185FA5;text-decoration:none;
                          font-weight:600;float:right;">
                    View leaderboard →
                </a>
            </div>
            @forelse($topStudents as $i => $student)
            @php $avg = round($student->activityResults->avg('score') ?? 0, 1); @endphp
            <div style="display:flex;align-items:center;gap:10px;
                        padding:9px 10px;border-radius:8px;margin-bottom:6px;
                        background:#F9FAFB;border:1px solid #E5E7EB;">
                <div style="font-size:18px;width:24px;text-align:center;flex-shrink:0;">
                    @if($i==0) 🥇
                    @elseif($i==1) 🥈
                    @elseif($i==2) 🥉
                    @else <span style="font-size:11px;color:#9CA3AF;">#{{ $i+1 }}</span>
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
                <div style="text-align:right;">
                    <div style="font-size:13px;font-weight:700;color:#22C55E;">
                        {{ $avg }}%
                    </div>
                    <div style="font-size:10px;color:#F59E0B;font-weight:600;">
                        ⭐ {{ number_format($student->total_points) }}
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted small py-3">
                No student data yet.
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection