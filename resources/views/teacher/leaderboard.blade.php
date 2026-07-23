@extends('layouts.teacher')
@section('title', 'Leaderboard')
@section('page-title', 'Class Leaderboard')
@section('page-sub', 'See how students rank by points.')

@section('content')

{{-- Section filter tabs --}}
<div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="section-tab btn btn-sm btn-primary" data-section="all">
        All Sections
    </button>
    @foreach($sections as $section)
    <button class="section-tab btn btn-sm btn-outline-secondary"
            data-section="{{ $section }}">
        {{ $section }}
    </button>
    @endforeach
</div>

{{-- Top 3 podium --}}
@php
    $top3 = $students->take(3);
    $first  = $top3->get(0);
    $second = $top3->get(1);
    $third  = $top3->get(2);
@endphp

@if($students->count() >= 3)
<div class="dash-card mb-4">
    <div style="font-size:13px;font-weight:700;color:#111827;
                text-align:center;margin-bottom:20px;">
        🏆 Top 3 Students
    </div>
    <div class="d-flex align-items-end justify-content-center gap-4">

        {{-- 2nd place --}}
        @if($second)
        <div class="text-center">
            <div style="width:52px;height:52px;border-radius:50%;
                        background:#E5E7EB;color:#374151;
                        display:flex;align-items:center;justify-content:center;
                        font-size:16px;font-weight:700;margin:0 auto 6px;
                        border:2px solid #D1D5DB;">
                {{ strtoupper(substr($second->firstname,0,1).substr($second->lastname,0,1)) }}
            </div>
            <div style="font-size:12px;font-weight:700;color:#111827;">
                {{ $second->firstname }}
            </div>
            <div style="font-size:10px;color:#9CA3AF;margin-bottom:4px;">
                {{ $second->section }}
            </div>
            <div style="font-size:11px;font-weight:700;color:#F59E0B;margin-bottom:8px;">
                ⭐ {{ number_format($second->total_points) }}
            </div>
            <div style="background:#9CA3AF;border-radius:8px 8px 0 0;
                        height:64px;width:80px;display:flex;align-items:center;
                        justify-content:center;font-size:24px;margin:0 auto;">
                🥈
            </div>
        </div>
        @endif

        {{-- 1st place --}}
        @if($first)
        <div class="text-center">
            <div style="width:64px;height:64px;border-radius:50%;
                        background:#FEF3C7;color:#92400E;
                        display:flex;align-items:center;justify-content:center;
                        font-size:20px;font-weight:700;margin:0 auto 6px;
                        border:3px solid #F59E0B;">
                {{ strtoupper(substr($first->firstname,0,1).substr($first->lastname,0,1)) }}
            </div>
            <div style="font-size:13px;font-weight:700;color:#111827;">
                {{ $first->firstname }}
            </div>
            <div style="font-size:10px;color:#9CA3AF;margin-bottom:4px;">
                {{ $first->section }}
            </div>
            <div style="font-size:12px;font-weight:700;color:#F59E0B;margin-bottom:8px;">
                ⭐ {{ number_format($first->total_points) }}
            </div>
            <div style="background:#F59E0B;border-radius:8px 8px 0 0;
                        height:86px;width:80px;display:flex;align-items:center;
                        justify-content:center;font-size:30px;margin:0 auto;">
                🥇
            </div>
        </div>
        @endif

        {{-- 3rd place --}}
        @if($third)
        <div class="text-center">
            <div style="width:48px;height:48px;border-radius:50%;
                        background:#FEE2E2;color:#991B1B;
                        display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;margin:0 auto 6px;
                        border:2px solid #FCA5A5;">
                {{ strtoupper(substr($third->firstname,0,1).substr($third->lastname,0,1)) }}
            </div>
            <div style="font-size:11px;font-weight:700;color:#111827;">
                {{ $third->firstname }}
            </div>
            <div style="font-size:10px;color:#9CA3AF;margin-bottom:4px;">
                {{ $third->section }}
            </div>
            <div style="font-size:11px;font-weight:700;color:#F59E0B;margin-bottom:8px;">
                ⭐ {{ number_format($third->total_points) }}
            </div>
            <div style="background:#CD7C0A;border-radius:8px 8px 0 0;
                        height:48px;width:80px;display:flex;align-items:center;
                        justify-content:center;font-size:22px;margin:0 auto;">
                🥉
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Full rankings table --}}
<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div style="font-size:13px;font-weight:600;color:#111827;">
            📋 Full Rankings
        </div>
        <div style="font-size:11px;color:#9CA3AF;">
            {{ $students->count() }} students total
        </div>
    </div>

    <table class="dash-table" id="leaderboard-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Student</th>
                <th>Section</th>
                <th>Level</th>
                <th>Activities Done</th>
                <th>Avg. Score</th>
                <th>Total Points</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $student)
            @php
                $avg  = round($student->activityResults->avg('score') ?? 0, 1);
                $done = $student->activityResults->count();
            @endphp
            <tr data-section="{{ $student->section }}">
                <td>
                    @if($i == 0) <span style="font-size:18px;">🥇</span>
                    @elseif($i == 1) <span style="font-size:18px;">🥈</span>
                    @elseif($i == 2) <span style="font-size:18px;">🥉</span>
                    @else <span style="font-size:12px;color:#9CA3AF;font-weight:700;">#{{ $i + 1 }}</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:50%;
                                    background:#DBEAFE;color:#1E40AF;
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:11px;font-weight:700;flex-shrink:0;">
                            {{ strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1)) }}
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;color:#111827;">
                                {{ $student->firstname }} {{ $student->lastname }}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                                 background:#F3F4F6;color:#374151;font-weight:500;">
                        {{ $student->section ?? '—' }}
                    </span>
                </td>
                <td>
                    <span class="status-badge badge-blue">
                        Level {{ $student->current_level }}
                    </span>
                </td>
                <td style="color:#6B7280;">{{ $done }}</td>
                <td>
                    @php
                        $scoreColor = $avg >= 75 ? '#166534' : ($avg >= 50 ? '#92400E' : '#991B1B');
                        $scoreBg    = $avg >= 75 ? '#DCFCE7' : ($avg >= 50 ? '#FEF3C7' : '#FEE2E2');
                    @endphp
                    <span style="background:{{ $scoreBg }};color:{{ $scoreColor }};
                                 font-size:11px;padding:2px 10px;border-radius:20px;
                                 font-weight:600;">
                        {{ $avg }}%
                    </span>
                </td>
                <td>
                    <span style="font-size:13px;font-weight:700;color:#F59E0B;">
                        ⭐ {{ number_format($student->total_points) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">
                    No students yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
let activeSection = 'all';

document.querySelectorAll('.section-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update button styles
        document.querySelectorAll('.section-tab').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.add('btn-primary');
        this.classList.remove('btn-outline-secondary');

        activeSection = this.dataset.section;
        filterLeaderboard();
    });
});

function filterLeaderboard() {
    const rows = document.querySelectorAll('#leaderboard-table tbody tr');
    let rank = 1;
    rows.forEach(row => {
        const section = row.dataset.section ?? '';
        const show    = activeSection === 'all' || section === activeSection;
        row.style.display = show ? '' : 'none';
    });
}
</script>
@endpush