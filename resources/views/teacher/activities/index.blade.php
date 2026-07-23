@extends('layouts.teacher')
@section('title', 'Activity Management')
@section('page-title', 'Activity Management')
@section('page-sub', 'Manage reading activities for your students.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DBEAFE;"><i class="ti ti-books" style="color:#1E40AF;"></i></div>
            <div><div class="metric-label">Total Activities</div><div class="metric-value">{{ $total }}</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;"><i class="ti ti-circle-check" style="color:#166534;"></i></div>
            <div><div class="metric-label">Published</div><div class="metric-value">{{ $published }}</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEF3C7;"><i class="ti ti-pencil" style="color:#92400E;"></i></div>
            <div><div class="metric-label">Drafts</div><div class="metric-value">{{ $drafts }}</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#EDE9FE;"><i class="ti ti-clipboard-list" style="color:#5B21B6;"></i></div>
            <div><div class="metric-label">Completions</div><div class="metric-value">{{ $completions }}</div></div>
        </div>
    </div>
</div>

{{-- Tabs + toolbar --}}
<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-primary tab-btn" data-type="">All</button>
            <button class="btn btn-sm btn-outline-secondary tab-btn" data-type="Phonics">Phonics</button>
            <button class="btn btn-sm btn-outline-secondary tab-btn" data-type="Read Aloud">Read Aloud</button>
            <button class="btn btn-sm btn-outline-secondary tab-btn" data-type="Vocabulary">Vocabulary</button>
            <button class="btn btn-sm btn-outline-secondary tab-btn" data-type="Word Game">Word Game</button>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <input type="text" id="searchInput" class="form-control form-control-sm"
                   placeholder="Search activities…" style="width:180px;" onkeyup="filterTable()">
            <select class="form-select form-select-sm" id="levelFilter" style="width:120px;" onchange="filterTable()">
                <option value="">All levels</option>
                <option value="1">Level 1</option>
                <option value="2">Level 2</option>
                <option value="3">Level 3</option>
            </select>
            <select class="form-select form-select-sm" id="diffFilter" style="width:130px;" onchange="filterTable()">
                <option value="">All difficulty</option>
                <option value="Easy">Easy</option>
                <option value="Medium">Medium</option>
                <option value="Hard">Hard</option>
            </select>
<div class="d-flex gap-2">
                <a href="{{ route('teacher.activities.create.readaloud') }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-microphone"></i> Read Aloud
                </a>
                <a href="{{ route('teacher.activities.create.battle') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-sword"></i> Battle
                </a>
            </div>
        </div>
    </div>

    <table class="dash-table" id="activityTable">
        <thead>
            <tr>
                <th>Activity Title</th>
                <th>Type</th>
                <th>Level</th>
                <th>Difficulty</th>
                <th>Duration</th>
                <th>Points</th>
                <th>Completions</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
            @php
                $diffColor = match($activity->difficulty_level) {
                    'Easy'   => '#22C55E',
                    'Medium' => '#F59E0B',
                    'Hard'   => '#EF4444',
                    default  => '#9CA3AF'
                };
            @endphp
            <tr data-type="{{ $activity->activity_type }}">
                <td>
                    <div class="fw-semibold" style="font-size:12px;">{{ $activity->activity_name }}</div>
                    <div class="text-muted" style="font-size:10px;">{{ Str::limit($activity->description, 50) }}</div>
                </td>
                <td><span class="status-badge badge-blue">{{ $activity->activity_type }}</span></td>
                <td>Level {{ $activity->level }}</td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $diffColor }};display:inline-block;"></span>
                        {{ $activity->difficulty_level }}
                    </span>
                </td>
                <td>{{ $activity->duration_minutes }} min</td>
                <td>⭐ {{ $activity->points_reward }}</td>
                <td>{{ $activity->results->count() }}</td>
                <td>
                    @if($activity->is_published)
                        <span class="status-badge badge-green">Published</span>
                    @else
                        <span class="status-badge badge-amber">Draft</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('teacher.activities.show', $activity->id) }}"
                           class="btn btn-sm btn-outline-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
<a href="{{ $activity->activity_type === 'Read Aloud' ? route('teacher.activities.edit.readaloud', $activity->id) : route('teacher.activities.edit.battle', $activity->id) }}"
                           class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('teacher.activities.destroy', $activity->id) }}"
                              onsubmit="return confirm('Delete this activity?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No activities yet. <a href="{{ route('teacher.activities.create') }}">Create your first activity →</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
let activeType = '';

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.add('btn-primary');
        this.classList.remove('btn-outline-secondary');
        activeType = this.dataset.type;
        filterTable();
    });
});

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const level  = document.getElementById('levelFilter').value;
    const diff   = document.getElementById('diffFilter').value;
    document.querySelectorAll('#activityTable tbody tr').forEach(row => {
        const title = row.cells[0]?.textContent.toLowerCase() ?? '';
        const type  = row.dataset.type ?? '';
        const lvl   = row.cells[2]?.textContent.trim() ?? '';
        const d     = row.cells[3]?.textContent.trim() ?? '';
        const matchSearch = title.includes(search);
        const matchType   = activeType === '' || type === activeType;
        const matchLevel  = level === '' || lvl.includes(level);
        const matchDiff   = diff === '' || d.includes(diff);
        row.style.display = matchSearch && matchType && matchLevel && matchDiff ? '' : 'none';
    });
}
</script>
@endpush