@extends('layouts.teacher')
@section('title', 'Student Management')
@section('page-title', 'Student Management')
@section('page-sub', 'Manage your Grade 2 learners.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DBEAFE;">
                <i class="ti ti-users" style="color:#1E40AF;"></i>
            </div>
            <div>
                <div class="metric-label">Total Students</div>
                <div class="metric-value">{{ $total }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;">
                <i class="ti ti-circle-check" style="color:#166534;"></i>
            </div>
            <div>
                <div class="metric-label">On Track</div>
                <div class="metric-value">{{ $onTrack }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEE2E2;">
                <i class="ti ti-alert-circle" style="color:#991B1B;"></i>
            </div>
            <div>
                <div class="metric-label">Need Attention</div>
                <div class="metric-value">{{ $needAttention }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Toolbar --}}
<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" id="searchInput" class="form-control form-control-sm"
                   placeholder="Search name or ID…" style="width:200px;"
                   onkeyup="filterTable()">
            <select class="form-select form-select-sm" id="sectionFilter"
                    style="width:140px;" onchange="filterTable()">
                <option value="">All sections</option>
                @foreach($sections as $section)
                <option value="{{ $section }}">{{ $section }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm" id="levelFilter"
                    style="width:120px;" onchange="filterTable()">
                <option value="">All levels</option>
                <option value="1">Level 1</option>
                <option value="2">Level 2</option>
                <option value="3">Level 3</option>
            </select>
            <select class="form-select form-select-sm" id="statusFilter"
                    style="width:130px;" onchange="filterTable()">
                <option value="">All status</option>
                <option value="On Track">On Track</option>
                <option value="Needs Help">Needs Help</option>
                <option value="Struggling">Struggling</option>
            </select>
        </div>
        <a href="{{ route('teacher.students.create') }}"
           class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> Add Student
        </a>
    </div>

    <table class="dash-table" id="studentTable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Student ID</th>
                <th>Section</th>
                <th>Level</th>
                <th>Score</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
            @php
                $avg = round($student->activityResults->avg('score') ?? 0, 1);
                if ($avg >= 75)     { $status = 'On Track';   $bc = 'badge-green'; $color = '#22C55E'; }
                elseif ($avg >= 50) { $status = 'Needs Help'; $bc = 'badge-amber'; $color = '#F59E0B'; }
                else                { $status = 'Struggling'; $bc = 'badge-red';   $color = '#EF4444'; }
                $initials = strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1));
            @endphp
            <tr data-section="{{ $student->section }}"
                data-level="{{ $student->current_level }}"
                data-status="{{ $status }}">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:50%;
                                    background:#DBEAFE;color:#1E40AF;
                                    display:flex;align-items:center;justify-content:center;
                                    font-size:11px;font-weight:700;flex-shrink:0;">
                            {{ $initials }}
                        </div>
                        {{ $student->firstname }} {{ $student->lastname }}
                    </div>
                </td>
                <td style="color:#9CA3AF;">{{ $student->student_number }}</td>
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
                <td>{{ $avg }}%</td>
                <td>
                    <div class="prog-bg">
                        <div class="prog-fill"
                             style="width:{{ $avg }}%;background:{{ $color }};"></div>
                    </div>
                </td>
                <td>
                    <span class="status-badge {{ $bc }}">{{ $status }}</span>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('teacher.students.show', $student->id) }}"
                           class="btn btn-sm btn-outline-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="{{ route('teacher.students.edit', $student->id) }}"
                           class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="ti ti-edit"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('teacher.students.destroy', $student->id) }}"
                              onsubmit="return confirm('Delete this student?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No students yet.
                    <a href="{{ route('teacher.students.create') }}">
                        Add your first student →
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

{{-- Pagination footer --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
        <div class="d-flex align-items-center gap-2" style="font-size:12px;color:#6b7280;">
            <span>Rows per page:</span>
            <select id="perPage" class="form-select form-select-sm" style="width:70px;" onchange="changePerPage(this.value)">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span id="pageInfo">Showing 0–0 of 0</span>
        </div>
        <nav aria-label="Student table pagination">
            <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
        </nav>
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentPage = 1;
let perPage = 10;

function getVisibleRows() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const section = document.getElementById('sectionFilter').value;
    const level   = document.getElementById('levelFilter').value;
    const status  = document.getElementById('statusFilter').value;

    const rows = [];
    document.querySelectorAll('#studentTable tbody tr').forEach(row => {
        if (row.id === 'noResultsRow') return;
        const name    = row.cells[0]?.textContent.toLowerCase() ?? '';
        const id      = row.cells[1]?.textContent.toLowerCase() ?? '';
        const rowSec  = row.dataset.section ?? '';
        const rowLvl  = row.dataset.level   ?? '';
        const rowStat = row.dataset.status  ?? '';

        const matchSearch  = name.includes(search) || id.includes(search);
        const matchSection = section === '' || rowSec === section;
        const matchLevel   = level   === '' || rowLvl === level;
        const matchStatus  = status  === '' || rowStat === status;

        if (matchSearch && matchSection && matchLevel && matchStatus) rows.push(row);
    });
    return rows;
}

function filterTable() {
    currentPage = 1;
    paginate();
}

function changePerPage(value) {
    perPage = parseInt(value, 10) || 10;
    currentPage = 1;
    paginate();
}

function paginate() {
    const rows = getVisibleRows();
    const tbody = document.querySelector('#studentTable tbody');
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * perPage;
    const end = Math.min(start + perPage, total);

    // Hide all rows first
    tbody.querySelectorAll('tr').forEach(row => {
        row.style.display = 'none';
    });

    // Show rows for the current page
    for (let i = start; i < end; i++) {
        rows[i].style.display = '';
    }

    // Handle no-results message
    let noResults = tbody.querySelector('#noResultsRow');
    if (total === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="8" class="text-center text-muted py-4">No students match your filters.</td>';
            tbody.appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }

    // Update info text
    document.getElementById('pageInfo').textContent =
        total === 0 ? 'Showing 0–0 of 0' : `Showing ${start + 1}–${end} of ${total}`;

    renderPaginationButtons(totalPages, total);
}

function renderPaginationButtons(totalPages, total) {
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';
    if (total === 0) return;

    // Prev button
    const prev = document.createElement('li');
    prev.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
    prev.innerHTML = `<a class="page-link" href="#">«</a>`;
    prev.onclick = (e) => { e.preventDefault(); if (currentPage > 1) { currentPage--; paginate(); } };
    ul.appendChild(prev);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === currentPage ? ' active' : '');
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.onclick = (e) => { e.preventDefault(); currentPage = i; paginate(); };
        ul.appendChild(li);
    }

    // Next button
    const next = document.createElement('li');
    next.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
    next.innerHTML = `<a class="page-link" href="#">»</a>`;
    next.onclick = (e) => { e.preventDefault(); if (currentPage < totalPages) { currentPage++; paginate(); } };
    ul.appendChild(next);
}

// Initial render
paginate();
</script>
@endpush
