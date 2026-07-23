@extends('layouts.teacher')
@section('title', 'View Activity')
@section('page-title', 'Activity Details')
@section('page-sub', 'Viewing activity information.')

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <div class="dash-card mb-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1">{{ $activity->activity_name }}</h5>
                    <p class="text-muted small mb-0">{{ $activity->description }}</p>
                </div>
                @if($activity->is_published)
                    <span class="status-badge badge-green">Published</span>
                @else
                    <span class="status-badge badge-amber">Draft</span>
                @endif
            </div>
            <div class="d-flex gap-3 flex-wrap" style="font-size:12px;">
                <span><strong>Type:</strong> {{ $activity->activity_type }}</span>
                @if($activity->battle_mode)
                <span><span class="status-badge" style="background:#F5F3FF;color:#5B21B6;">⚔️ Battle Mode</span></span>
                @endif
                <span><strong>Level:</strong> {{ $activity->level }}</span>
                <span><strong>Difficulty:</strong> {{ $activity->difficulty_level }}</span>
                <span><strong>Duration:</strong> {{ $activity->duration_minutes }} min</span>
                <span><strong>Points:</strong> ⭐ {{ $activity->points_reward }}</span>
            </div>
        </div>

        @if($activity->activity_type === 'Read Aloud' && $activity->readingMaterial)
        <div class="dash-card mb-3">
            <div class="dash-card-title">📖 Reading Passage</div>
            <div style="background:#F8FAFF;border:1px solid #DBEAFE;border-radius:8px;
                        padding:14px;font-size:13px;line-height:2;color:#1E3A5F;">
                {{ $activity->readingMaterial->content }}
            </div>
        </div>
        @endif

        @if($activity->wordBank->count())
        <div class="dash-card mb-3">
            <div class="dash-card-title">⚔️ Battle Words / Paragraphs ({{ $activity->wordBank->count() }})</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($activity->wordBank as $wb)
                <span class="badge bg-light text-dark border" style="font-size:12px;padding:6px 10px;">
                    {{ $wb->word }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="dash-card">
            <div class="dash-card-title">Student Completions ({{ $activity->results->count() }})</div>
            <table class="dash-table">
                <thead>
                    <tr><th>Student</th><th>Score</th><th>Attempts</th><th>Completed</th></tr>
                </thead>
                <tbody>
                    @forelse($activity->results as $result)
                    <tr>
                        <td>{{ $result->student->firstname }} {{ $result->student->lastname }}</td>
                        <td>{{ $result->score }}%</td>
                        <td>{{ $result->attempts }}</td>
                        <td style="color:#9CA3AF;">{{ \Carbon\Carbon::parse($result->completed_at)->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No completions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dash-card mb-3">
            <div class="dash-card-title">Actions</div>
            <div class="d-flex flex-column gap-2">
                @if($activity->activity_type === 'Read Aloud')
                <a href="{{ route('teacher.activities.edit.readaloud', $activity->id) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-edit"></i> Edit Read Aloud
                </a>
                @else
                <a href="{{ route('teacher.activities.edit.battle', $activity->id) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-edit"></i> Edit Battle Activity
                </a>
                @endif
                <form method="POST" action="{{ route('teacher.activities.destroy', $activity->id) }}"
                      onsubmit="return confirm('Delete this activity?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="ti ti-trash"></i> Delete Activity
                    </button>
                </form>
                <a href="{{ route('teacher.activities.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> Back to Activities
                </a>
            </div>
        </div>
        <div class="dash-card" style="background:#FFFBF0;border-color:#FDE68A;">
            <div class="dash-card-title" style="color:#92400E;">Settings</div>
            <div style="font-size:12px;" class="d-flex flex-column gap-1">
                <div>✅ Allow re-attempts: <strong>{{ $activity->allow_reattempt ? 'Yes' : 'No' }}</strong></div>
            </div>
        </div>
    </div>
</div>
@endsection
