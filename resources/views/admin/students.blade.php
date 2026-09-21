@extends('layouts.admin')
@section('title', 'Students')
@section('page-title', 'Student Management')
@section('page-sub', 'View all students across all teachers.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.students') }}">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center flex-wrap">

                <div style="position:relative;">
                    <i class="ti ti-search"
                       style="position:absolute;left:9px;top:50%;
                              transform:translateY(-50%);
                              color:#9CA3AF;font-size:14px;"></i>
                    <input type="text" name="search"
                           value="{{ $search }}"
                           class="form-control form-control-sm"
                           placeholder="Name, ID, section…"
                           style="width:220px;padding-left:30px;">
                </div>

                <select name="level" class="form-select form-select-sm"
                        style="width:120px;" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    @for($l = 1; $l <= 5; $l++)
                    <option value="{{ $l }}" {{ $level == $l ? 'selected' : '' }}>
                        Level {{ $l }}
                    </option>
                    @endfor
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ti ti-search"></i> Search
                </button>

                @if($search || $level)
                <a href="{{ route('admin.students') }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x"></i> Clear
                </a>
                @endif
            </div>

            <div style="font-size:12px;color:#9CA3AF;">
                {{ $students->total() }} student(s) total
            </div>
        </div>
    </form>

    {{-- Results info --}}
    <div style="font-size:11px;color:#9CA3AF;margin-bottom:10px;">
        Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }}
        of {{ $students->total() }} student(s)
        @if($search) matching "<strong>{{ $search }}</strong>" @endif
    </div>

    {{-- Table --}}
    <table class="dash-table">
        <thead>
            <tr>
                <th>#</th>
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
                <td style="color:#9CA3AF;font-size:11px;">
                    {{ $students->firstItem() + $loop->index }}
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:50%;
                                    background:#DCFCE7;color:#166534;font-size:11px;
                                    font-weight:700;display:flex;align-items:center;
                                    justify-content:center;flex-shrink:0;">
                            {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;color:#111827;">
                                {{ $s->firstname }} {{ $s->lastname }}
                            </div>
                            <div style="font-size:10px;color:#9CA3AF;">
                                @{{ $s->user->username ?? '—' }}
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:11px;color:#9CA3AF;">
                    {{ $s->student_number }}
                </td>
                <td>
                    <span style="font-size:11px;padding:2px 8px;
                                 border-radius:20px;background:#F3F4F6;color:#374151;">
                        {{ $s->section ?? '—' }}
                    </span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $s->teacher?->firstname }} {{ $s->teacher?->lastname }}
                </td>
                <td>
                    <span class="status-badge badge-blue">
                        Level {{ $s->current_level }}
                    </span>
                </td>
                <td style="font-size:12px;font-weight:700;color:#F59E0B;">
                    ⭐ {{ number_format($s->total_points) }}
                </td>
                <td>
                    @php
                        $c = $avg >= 75 ? '#166534' : ($avg >= 50 ? '#92400E' : '#991B1B');
                        $b = $avg >= 75 ? '#DCFCE7' : ($avg >= 50 ? '#FEF3C7' : '#FEE2E2');
                    @endphp
                    <span style="background:{{ $b }};color:{{ $c }};
                                 font-size:11px;padding:2px 10px;
                                 border-radius:20px;font-weight:600;">
                        {{ $avg }}%
                    </span>
                </td>
                <td>
                    <form method="POST"
                          action="{{ route('admin.students.delete', $s->id) }}"
                          onsubmit="return confirm('Delete {{ $s->firstname }} {{ $s->lastname }}?')">
                        @csrf @method('DELETE')

                        <div class="d-flex gap-1">
    {{-- View --}}
    <a href="{{ route('admin.students.view', $s->id) }}"
       class="btn btn-sm btn-outline-primary" title="View">
        <i class="ti ti-eye"></i>
    </a>
    {{-- Delete same as before --}}
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-5">
                    <div style="font-size:32px;">👦</div>
                    <div class="mt-2">No students found.</div>
                    @if($search || $level)
                    <a href="{{ route('admin.students') }}"
                       class="btn btn-sm btn-outline-secondary mt-2">
                        Clear filters
                    </a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($students->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <div style="font-size:12px;color:#9CA3AF;">
            Page {{ $students->currentPage() }} of {{ $students->lastPage() }}
        </div>
        <div class="d-flex gap-1 align-items-center">
            @if($students->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;background:#F3F4F6;
                         color:#D1D5DB;font-size:12px;cursor:not-allowed;">← Prev</span>
            @else
            <a href="{{ $students->previousPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;
                      border:1px solid #E5E7EB;color:#374151;font-size:12px;
                      text-decoration:none;font-weight:600;">← Prev</a>
            @endif

            @foreach($students->getUrlRange(
                max(1, $students->currentPage() - 2),
                min($students->lastPage(), $students->currentPage() + 2)
            ) as $page => $url)
            @if($page == $students->currentPage())
            <span style="padding:6px 12px;border-radius:8px;background:#DC2626;
                         color:#fff;font-size:12px;font-weight:700;">{{ $page }}</span>
            @else
            <a href="{{ $url }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;
                      border:1px solid #E5E7EB;color:#374151;font-size:12px;
                      text-decoration:none;font-weight:600;">{{ $page }}</a>
            @endif
            @endforeach

            @if($students->hasMorePages())
            <a href="{{ $students->nextPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;
                      border:1px solid #E5E7EB;color:#374151;font-size:12px;
                      text-decoration:none;font-weight:600;">Next →</a>
            @else
            <span style="padding:6px 12px;border-radius:8px;background:#F3F4F6;
                         color:#D1D5DB;font-size:12px;cursor:not-allowed;">Next →</span>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection