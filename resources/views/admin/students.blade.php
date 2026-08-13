@extends('layouts.admin')
@section('title', 'Students')
@section('page-title', 'Student Management')
@section('page-sub', 'View all students across all teachers.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div style="font-size:13px;font-weight:600;color:#111827;">
            {{ $students->count() }} students total
        </div>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search student…" style="width:200px;"
               onkeyup="filterTable()">
    </div>

    <table class="dash-table" id="studentTable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Student ID</th>
                <th>Section</th>
                <th>Teacher</th>
                <th>Level</th>
                <th>Points</th>
                <th>Avg Score</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $s)
            @php $avg = round($s->activityResults->avg('score') ?? 0, 1); @endphp
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:50%;
                                    background:#DCFCE7;color:#166534;font-size:11px;
                                    font-weight:700;display:flex;align-items:center;
                                    justify-content:center;flex-shrink:0;">
                            {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
                        </div>
                        {{ $s->firstname }} {{ $s->lastname }}
                    </div>
                </td>
                <td style="color:#9CA3AF;">{{ $s->student_number }}</td>
                <td>
                    <span style="font-size:11px;padding:2px 8px;border-radius:20px;
                                 background:#F3F4F6;color:#374151;">
                        {{ $s->section ?? '—' }}
                    </span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $s->teacher?->firstname }} {{ $s->teacher?->lastname }}
                </td>
                <td>
                    <span class="status-badge badge-blue">Level {{ $s->current_level }}</span>
                </td>
                <td style="font-weight:700;color:#F59E0B;">
                    ⭐ {{ number_format($s->total_points) }}
                </td>
                <td>
                    @php
                        $c = $avg >= 75 ? '#166534' : ($avg >= 50 ? '#92400E' : '#991B1B');
                        $b = $avg >= 75 ? '#DCFCE7' : ($avg >= 50 ? '#FEF3C7' : '#FEE2E2');
                    @endphp
                    <span style="background:{{ $b }};color:{{ $c }};font-size:11px;
                                 padding:2px 8px;border-radius:20px;font-weight:600;">
                        {{ $avg }}%
                    </span>
                </td>
                <td>
                    <form method="POST"
                          action="{{ route('admin.students.delete', $s->id) }}"
                          onsubmit="return confirm('Delete this student?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No students yet.</td>
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
    const rows = document.querySelectorAll('#studentTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush