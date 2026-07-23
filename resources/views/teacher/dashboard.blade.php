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
                <div class="metric-value">{{ $totalStudents }}</div>
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
                <div class="metric-value">{{ $activeToday }}</div>
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
                <div class="metric-value">{{ $activitiesDone }}</div>
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
                <div class="metric-value">{{ $pendingReviews }}</div>
                <div class="metric-sub">Recordings to evaluate</div>
            </div>
        </div>
    </div>
</div>

{{-- Student Progress + Recent Activity --}}
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
                        $avg = $student->activityResults->avg('score') ?? 0;
                        $avg = round($avg, 1);
                        if ($avg >= 75) { $status = 'On Track'; $badgeClass = 'badge-green'; $color = '#22C55E'; }
                        elseif ($avg >= 50) { $status = 'Needs Help'; $badgeClass = 'badge-amber'; $color = '#F59E0B'; }
                        else { $status = 'Struggling'; $badgeClass = 'badge-red'; $color = '#EF4444'; }
                    @endphp
                    <tr>
                        <td><strong>{{ $student->firstname }} {{ $student->lastname }}</strong></td>
                        <td><span class="status-badge badge-blue">Level {{ $student->current_level }}</span></td>
                        <td>{{ $avg }}%</td>
                        <td>
                            <div class="prog-bg">
                                <div class="prog-fill" style="width:{{ $avg }}%; background:{{ $color }};"></div>
                            </div>
                        </td>
                        <td><span class="status-badge {{ $badgeClass }}">{{ $status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-5">
        <div class="dash-card">
            <div class="dash-card-title">Recent Activity</div>
            @forelse($recentActivity as $result)
            <div class="feed-item">
                <div class="feed-dot" style="background:#185FA5;"></div>
                <div>
                    <div class="feed-text">
                        <strong>{{ $result->student->firstname }}</strong>
                        completed <strong>{{ $result->activity->activity_name }}</strong>
                        — {{ $result->score }}%
                    </div>
                    <div class="feed-time">{{ \Carbon\Carbon::parse($result->completed_at)->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <p class="text-muted small text-center py-3">No recent activity.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Pending Voice Evaluations --}}
<div class="row g-3">
    <div class="col-12">
        <div class="dash-card">
            <div class="dash-card-title">
                Pending Voice Evaluations
                <a href="{{ route('teacher.evaluations.index') }}">Evaluate all →</a>
            </div>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Activity</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRecordings as $recording)
                    <tr>
                        <td><strong>{{ $recording->student->firstname }} {{ $recording->student->lastname }}</strong></td>
                        <td>{{ $recording->activity->activity_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($recording->created_at)->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('teacher.evaluations.show', $recording->id) }}" class="btn-listen">
                                <i class="ti ti-player-play"></i> Listen
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No pending evaluations. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection