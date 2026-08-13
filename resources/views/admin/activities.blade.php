@extends('layouts.admin')
@section('title', 'Activities')
@section('page-title', 'Activity Management')
@section('page-sub', 'View all activities across all teachers.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div style="font-size:13px;font-weight:600;color:#111827;">
            {{ $activities->count() }} activities total
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="searchInput" class="form-control form-control-sm"
                   placeholder="Search activity…" style="width:180px;"
                   onkeyup="filterTable()">
            <select class="form-select form-select-sm" id="typeFilter"
                    style="width:140px;" onchange="filterTable()">
                <option value="">All types</option>
                @foreach($activities->pluck('activity_type')->unique() as $type)
                <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <table class="dash-table" id="activityTable">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Type</th>
                <th>Teacher</th>
                <th>Level</th>
                <th>Difficulty</th>
                <th>Points</th>
                <th>Status</th>
                <th>Battle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $a)
            <tr data-type="{{ $a->activity_type }}">
                <td>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $a->activity_name }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ Str::limit($a->description, 40) }}
                    </div>
                </td>
                <td>
                    <span class="status-badge badge-blue">{{ $a->activity_type }}</span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $a->teacher?->firstname }} {{ $a->teacher?->lastname }}
                </td>
                <td>
                    <span class="status-badge badge-amber">L{{ $a->level }}</span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $a->difficulty_level }}
                </td>
                <td style="font-size:12px;font-weight:700;color:#F59E0B;">
                    ⭐ {{ $a->points_reward }}
                </td>
                <td>
                    <span class="status-badge {{ $a->is_published ? 'badge-green' : 'badge-red' }}">
                        {{ $a->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td>
                    @if($a->battle_mode)
                    <span class="status-badge" style="background:#EDE9FE;color:#5B21B6;">⚔️ Yes</span>
                    @else
                    <span style="font-size:11px;color:#9CA3AF;">—</span>
                    @endif
                </td>
                <td>
                    <form method="POST"
                          action="{{ route('admin.activities.delete', $a->id) }}"
                          onsubmit="return confirm('Delete this activity?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No activities yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
function filterTable() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const type = document.getElementById('typeFilter').value;
    const rows = document.querySelectorAll('#activityTable tbody tr');
    rows.forEach(row => {
        const matchQ = row.textContent.toLowerCase().includes(q);
        const matchT = type === '' || row.dataset.type === type;
        row.style.display = matchQ && matchT ? '' : 'none';
    });
}
</script>
@endpush