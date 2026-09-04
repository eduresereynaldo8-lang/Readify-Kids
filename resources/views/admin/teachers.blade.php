@extends('layouts.admin')
@section('title', 'Teachers')
@section('page-title', 'Teacher Management')
@section('page-sub', 'View and manage all teacher accounts.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.teachers') }}">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center flex-wrap">

                {{-- Search --}}
                <div style="position:relative;">
                    <i class="ti ti-search"
                       style="position:absolute;left:9px;top:50%;
                              transform:translateY(-50%);
                              color:#9CA3AF;font-size:14px;"></i>
                    <input type="text" name="search"
                           value="{{ $search }}"
                           class="form-control form-control-sm"
                           placeholder="Search name, school, email…"
                           style="width:220px;padding-left:30px;">
                </div>

                {{-- Status filter --}}
                <select name="status" class="form-select form-select-sm"
                        style="width:130px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ti ti-search"></i> Search
                </button>

                @if($search || $status)
                <a href="{{ route('admin.teachers') }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x"></i> Clear
                </a>
                @endif
            </div>

            <a href="{{ route('admin.teachers.create') }}"
               style="display:inline-flex;align-items:center;gap:6px;
                      padding:7px 16px;border-radius:10px;
                      background:linear-gradient(135deg,#DC2626,#991B1B);
                      color:#fff;font-size:13px;font-weight:700;
                      text-decoration:none;white-space:nowrap;">
                <i class="ti ti-plus"></i> Add Teacher
            </a>
        </div>
    </form>

    {{-- Results info --}}
    <div style="font-size:11px;color:#9CA3AF;margin-bottom:10px;">
        Showing {{ $teachers->firstItem() ?? 0 }}–{{ $teachers->lastItem() ?? 0 }}
        of {{ $teachers->total() }} teacher(s)
        @if($search) matching "<strong>{{ $search }}</strong>" @endif
    </div>

    {{-- Table --}}
    <table class="dash-table" id="teacherTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Teacher</th>
                <th>Email</th>
                <th>School</th>
                <th>Students</th>
                <th>Activities</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $t)
            <tr>
                <td style="color:#9CA3AF;font-size:11px;">
                    {{ $teachers->firstItem() + $loop->index }}
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:30px;height:30px;border-radius:50%;
                                    background:#FEE2E2;color:#991B1B;font-size:11px;
                                    font-weight:700;display:flex;align-items:center;
                                    justify-content:center;flex-shrink:0;">
                            {{ strtoupper(substr($t->firstname,0,1).substr($t->lastname,0,1)) }}
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;color:#111827;">
                                {{ $t->firstname }} {{ $t->lastname }}
                            </div>
                            <div style="font-size:10px;color:#9CA3AF;">
                                @{{ $t->user->username }}
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $t->user->email }}
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $t->school_name }}
                </td>
                <td>
                    <span class="status-badge badge-blue">
                        {{ $t->students->count() }} students
                    </span>
                </td>
                <td>
                    <span class="status-badge badge-amber">
                        {{ $t->activities->count() }} activities
                    </span>
                </td>
                <td>
                    <span class="status-badge
                        {{ $t->user->email_verified_at ? 'badge-green' : 'badge-red' }}">
                        {{ $t->user->email_verified_at ? '✓ Active' : '✗ Inactive' }}
                    </span>
                </td>
                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($t->created_at)->format('M d, Y') }}
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <form method="POST"
                              action="{{ route('admin.teachers.toggle', $t->id) }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm {{ $t->user->email_verified_at ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $t->user->email_verified_at ? 'Deactivate' : 'Activate' }}">
                                <i class="ti ti-{{ $t->user->email_verified_at ? 'ban' : 'check' }}"></i>
                            </button>
                        </form>
                        <form method="POST"
                              action="{{ route('admin.teachers.delete', $t->id) }}"
                              onsubmit="return confirm('Delete {{ $t->firstname }} {{ $t->lastname }} and all their data?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted py-5">
                    <div style="font-size:32px;">👩‍🏫</div>
                    <div class="mt-2">No teachers found.</div>
                    @if($search || $status)
                    <a href="{{ route('admin.teachers') }}" class="btn btn-sm btn-outline-secondary mt-2">
                        Clear filters
                    </a>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($teachers->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <div style="font-size:12px;color:#9CA3AF;">
            Page {{ $teachers->currentPage() }} of {{ $teachers->lastPage() }}
        </div>
        <div class="d-flex gap-1 align-items-center">

            {{-- Previous --}}
            @if($teachers->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;
                         background:#F3F4F6;color:#D1D5DB;
                         font-size:12px;cursor:not-allowed;">
                ← Prev
            </span>
            @else
            <a href="{{ $teachers->previousPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;
                      background:#fff;border:1px solid #E5E7EB;
                      color:#374151;font-size:12px;text-decoration:none;
                      font-weight:600;transition:all 0.2s;"
               onmouseover="this.style.background='#F3F4F6'"
               onmouseout="this.style.background='#fff'">
                ← Prev
            </a>
            @endif

            {{-- Page numbers --}}
            @foreach($teachers->getUrlRange(
                max(1, $teachers->currentPage() - 2),
                min($teachers->lastPage(), $teachers->currentPage() + 2)
            ) as $page => $url)
            @if($page == $teachers->currentPage())
            <span style="padding:6px 12px;border-radius:8px;
                         background:#DC2626;color:#fff;
                         font-size:12px;font-weight:700;">
                {{ $page }}
            </span>
            @else
            <a href="{{ $url }}"
               style="padding:6px 12px;border-radius:8px;
                      background:#fff;border:1px solid #E5E7EB;
                      color:#374151;font-size:12px;text-decoration:none;
                      font-weight:600;transition:all 0.2s;"
               onmouseover="this.style.background='#F3F4F6'"
               onmouseout="this.style.background='#fff'">
                {{ $page }}
            </a>
            @endif
            @endforeach

            {{-- Next --}}
            @if($teachers->hasMorePages())
            <a href="{{ $teachers->nextPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;
                      background:#fff;border:1px solid #E5E7EB;
                      color:#374151;font-size:12px;text-decoration:none;
                      font-weight:600;transition:all 0.2s;"
               onmouseover="this.style.background='#F3F4F6'"
               onmouseout="this.style.background='#fff'">
                Next →
            </a>
            @else
            <span style="padding:6px 12px;border-radius:8px;
                         background:#F3F4F6;color:#D1D5DB;
                         font-size:12px;cursor:not-allowed;">
                Next →
            </span>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection