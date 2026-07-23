@extends('layouts.teacher')
@section('title', 'View Student')
@section('page-title', 'Student Profile')
@section('page-sub', 'Viewing student details and progress.')

@section('content')

{{-- Profile header --}}
<div class="dash-card mb-3 d-flex align-items-center gap-3">
    <div style="width:52px;height:52px;border-radius:50%;background:#DBEAFE;color:#1E40AF;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0;">
        {{ strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1)) }}
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold" style="font-size:16px;">{{ $student->firstname }} {{ $student->lastname }}</div>
        <div class="text-muted small d-flex gap-3 mt-1">
            <span><i class="ti ti-id-badge"></i> {{ $student->student_number }}</span>
            <span><i class="ti ti-users"></i> {{ $student->section }}</span>
            <span><i class="ti ti-chart-bar"></i> Level {{ $student->current_level }}</span>
            <span><span class="status-badge {{ $badgeClass }}">{{ $status }}</span></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('teacher.students.edit', $student->id) }}" class="btn btn-sm btn-outline-primary">
            <i class="ti ti-edit"></i> Edit
        </a>
        <a href="{{ route('teacher.evaluations.index') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-microphone"></i> Evaluate
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    {{-- Stats --}}
    <div class="col-md-4">
        <div class="dash-card">
            <div class="dash-card-title">Student Info</div>
            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:12px;">
                <span class="text-muted">Overall Score</span>
                <strong>{{ $avg }}%</strong>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:12px;">
                <span class="text-muted">Current Level</span>
                <strong>Level {{ $student->current_level }}</strong>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:12px;">
                <span class="text-muted">Total Points</span>
                <strong>{{ $student->total_points }} pts</strong>
            </div>
            <div class="d-flex justify-content-between py-1" style="font-size:12px;">
                <span class="text-muted">Activities Done</span>
                <strong>{{ $student->activityResults->count() }}</strong>
            </div>
        </div>
    </div>

    {{-- Recent activities --}}
    <div class="col-md-8">
        <div class="dash-card">
            <div class="dash-card-title">Recent Activity History</div>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($student->activityResults->take(5) as $result)
                    <tr>
                        <td>{{ $result->activity->activity_name ?? '—' }}</td>
                        <td><span class="status-badge badge-blue">{{ $result->activity->activity_type ?? '—' }}</span></td>
                        <td>{{ $result->score }}%</td>
                        <td style="color:#9CA3AF;">{{ \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No activities yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left"></i> Back to Students
    </a>
</div>

@endsection