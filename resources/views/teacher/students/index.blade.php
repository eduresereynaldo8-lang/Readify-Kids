@extends('layouts.teacher')
@section('title', 'Student Management')
@section('page-title', 'Student Management')
@section('page-sub', 'Manage your Grade 2 learners.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-management.css') }}">
@endpush
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-warning mb-3" role="alert">{{ session('error') }}</div>
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
                   placeholder="Search name or LRN…" style="width:200px;"
                   maxlength="200" oninput="filterTable()">
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
                @foreach($levelOptions as $level)<option value="{{ $level }}">Level {{ $level }}</option>@endforeach
            </select>
            <select class="form-select form-select-sm" id="statusFilter"
                    style="width:130px;" onchange="filterTable()">
                <option value="">All status</option>
                <option value="on_track">On Track</option>
                <option value="needs_help">Needs Help</option>
                <option value="struggling">Struggling</option>
                <option value="no_data">No Data</option>
            </select>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ route('teacher.students.create') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> Add Student
            </a>
            <a href="{{ route('teacher.students.import') }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-file-upload" aria-hidden="true"></i> Import Students
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-file-download"></i> Export Report
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" data-export-status="all" href="{{ route('teacher.students.exportClassPdf', ['status' => 'all']) }}">All Students</a></li>
                    <li><a class="dropdown-item" data-export-status="on_track" href="{{ route('teacher.students.exportClassPdf', ['status' => 'on_track']) }}">On Track Students</a></li>
                    <li><a class="dropdown-item" data-export-status="needs_help" href="{{ route('teacher.students.exportClassPdf', ['status' => 'needs_help']) }}">Needs Help Students</a></li>
                    <li><a class="dropdown-item" data-export-status="struggling" href="{{ route('teacher.students.exportClassPdf', ['status' => 'struggling']) }}">Struggling Students</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-item-text small text-muted">Exports respect all selected filters.</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="table-responsive" role="region" aria-label="Scrollable table" tabindex="0">
<table class="dash-table sm-student-table" id="studentTable">
        <thead>
            <tr>
                <th>Student</th>
                <th>LRN No.</th>
                <th>Age</th>
                <th>Gender</th>
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
                $avg = $student->activity_results_avg_score === null ? null : round($student->activity_results_avg_score, 2);
                $readingStatus = $student->reading_status;
                $status = $readingStatus['label'];
                $bc = $readingStatus['badge'];
                $color = $readingStatus['color'];
                $initials = strtoupper(substr($student->firstname,0,1).substr($student->lastname,0,1));
            @endphp
            <tr data-section="{{ $student->section }}"
                data-level="{{ $student->current_level }}"
                data-status="{{ $readingStatus['key'] }}"
                data-name="{{ mb_strtolower($student->firstname.' '.$student->lastname) }}"
                data-lrn="{{ $student->lrn_no }}">
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
                <td>{{ $student->lrn_no ?? '—' }}</td>
                <td>{{ $student->age ?? '—' }}</td>
                <td>{{ $student->gender ?? '—' }}</td>
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
                <td>{{ $avg === null ? '—' : number_format($avg, 2).'%' }}</td>
                <td>
                    <div class="prog-bg">
                        <div class="prog-fill"
                             style="width:{{ $avg ?? 0 }}%;background:{{ $color }};"></div>
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
                        <a href="{{ route('teacher.students.exportPdf', $student->id) }}"
                           class="btn btn-sm btn-outline-secondary" title="Export PDF" aria-label="Export PDF for {{ $student->firstname }} {{ $student->lastname }}">
                            <i class="ti ti-file-download"></i>
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
            <tr id="emptyStudentsRow">
                <td colspan="10" class="text-center text-muted py-4">
                    No students yet.
                    <a href="{{ route('teacher.students.create') }}">
                        Add your first student →
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

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
    const search  = document.getElementById('searchInput').value.trim().toLowerCase();
    const section = document.getElementById('sectionFilter').value;
    const level   = document.getElementById('levelFilter').value;
    const status  = document.getElementById('statusFilter').value;

    const rows = [];
    document.querySelectorAll('#studentTable tbody tr').forEach(row => {
        if (!row.hasAttribute('data-section')) return;
        const name    = row.dataset.name ?? '';
        const id      = row.dataset.lrn ?? '';
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

function updateExportLinks() {
    document.querySelectorAll('[data-export-status]').forEach(link => {
        const url = new URL(@json(route('teacher.students.exportClassPdf')), window.location.origin);
        url.searchParams.set('status', link.dataset.exportStatus);
        url.searchParams.set('page_status', document.getElementById('statusFilter').value || 'all');
        const filters = {
            search: document.getElementById('searchInput').value.trim(),
            section: document.getElementById('sectionFilter').value,
            level: document.getElementById('levelFilter').value
        };
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '') url.searchParams.set(key, value);
        });
        link.href = url.toString();
    });
}

function filterTable() {
    updateExportLinks();
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
    if (total === 0 && document.getElementById('emptyStudentsRow')) {
        document.getElementById('emptyStudentsRow').style.display = '';
    } else if (total === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="10" class="text-center text-muted py-4">No students match your filters.</td>';
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

// Refresh restored browser form values as well as newly selected filters.
window.addEventListener('pageshow', filterTable);
filterTable();
</script>
@endpush

