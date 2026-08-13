@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('page-title', 'System Dashboard')
@section('page-sub', 'Overview of all users, activities and game sessions.')

@section('content')

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    @foreach([
        ['👩‍🏫', 'Total Teachers',   $totalTeachers,   '#DBEAFE', '#1E40AF'],
        ['👦',   'Total Students',   $totalStudents,   '#DCFCE7', '#166534'],
        ['📖',   'Total Activities', $totalActivities, '#EDE9FE', '#5B21B6'],
        ['🎙️',   'Voice Recordings', $totalRecordings, '#FEF3C7', '#92400E'],
        ['⚔️',   'Games Played',     $totalGames,      '#FFE4E6', '#9F1239'],
        ['🏆',   'Battles Won',      $totalWins,       '#DCFCE7', '#166534'],
    ] as [$emoji, $label, $val, $bg, $color])
    <div class="col-6 col-md-4 col-lg-2">
        <div class="metric-card d-flex align-items-center gap-2">
            <div class="metric-icon" style="background:{{ $bg }};">
                <span style="font-size:18px;">{{ $emoji }}</span>
            </div>
            <div>
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-value" style="font-size:18px;">{{ number_format($val) }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    {{-- Weekly activity chart --}}
    <div class="col-md-7">
        <div class="dash-card h-100">
            <div class="dash-card-title">📊 System Activity — Last 7 Days</div>
            @php $maxVal = max(array_map(fn($d) => $d['games'] + $d['recs'], $weeklyData) ?: [1]); @endphp
            <div style="display:flex;align-items:flex-end;gap:8px;height:100px;padding:0 4px;">
                @foreach($weeklyData as $day)
                @php
                    $total  = $day['games'] + $day['recs'];
                    $h      = $maxVal > 0 ? max(4, round(($total / $maxVal) * 90)) : 4;
                    $isToday= $day['day'] === now()->format('D');
                @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;">
                    <div style="font-size:9px;color:#9CA3AF;">{{ $total }}</div>
                    <div style="width:100%;border-radius:4px 4px 0 0;
                                background:{{ $isToday ? '#DC2626' : '#FCA5A5' }};
                                height:{{ $h }}px;"></div>
                    <div style="font-size:10px;color:{{ $isToday ? '#DC2626' : '#9CA3AF' }};
                                font-weight:{{ $isToday ? '700' : '400' }};">
                        {{ $day['day'] }}
                    </div>
                </div>
                @endforeach
            </div>
            <div class="d-flex gap-3 mt-2" style="font-size:10px;color:#9CA3AF;">
                <span><span style="display:inline-block;width:10px;height:10px;background:#DC2626;border-radius:2px;margin-right:3px;"></span>Today</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#FCA5A5;border-radius:2px;margin-right:3px;"></span>Previous days</span>
            </div>
        </div>
    </div>

    {{-- Recent teachers --}}
    <div class="col-md-5">
        <div class="dash-card h-100">
            <div class="dash-card-title">👩‍🏫 Recent Teachers</div>
            @forelse($recentTeachers as $t)
            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;
                        border-bottom:1px solid #F9FAFB;">
                <div style="width:30px;height:30px;border-radius:50%;
                            background:#FEE2E2;color:#991B1B;font-size:11px;
                            font-weight:700;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                    {{ strtoupper(substr($t->firstname,0,1).substr($t->lastname,0,1)) }}
                </div>
                <div style="flex:1;">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $t->firstname }} {{ $t->lastname }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">{{ $t->school_name }}</div>
                </div>
                <span class="status-badge {{ $t->user->email_verified_at ? 'badge-green' : 'badge-red' }}">
                    {{ $t->user->email_verified_at ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @empty
            <div class="text-center text-muted small py-3">No teachers yet.</div>
            @endforelse
            <div class="mt-2">
                <a href="{{ route('admin.teachers') }}"
                   style="font-size:11px;color:#DC2626;font-weight:600;text-decoration:none;">
                    View all teachers →
                </a>
            </div>
        </div>
    </div>
</div>

@endsection