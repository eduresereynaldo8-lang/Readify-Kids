@extends('layouts.admin')
@section('title', 'Activities')
@section('page-title', 'Activity Management')
@section('page-sub', 'View all activities across all teachers.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.activities') }}">
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
                           placeholder="Search activity name…"
                           style="width:210px;padding-left:30px;">
                </div>

                <select name="type" class="form-select form-select-sm"
                        style="width:150px;" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>
                        {{ $t }}
                    </option>
                    @endforeach
                </select>

                <select name="status" class="form-select form-select-sm"
                        style="width:130px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>
                        Published
                    </option>
                    <option value="draft"     {{ $status === 'draft'     ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="battle"    {{ $status === 'battle'    ? 'selected' : '' }}>
                        ⚔️ Battle Mode
                    </option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="ti ti-search"></i> Search
                </button>

                @if($search || $type || $status)
                <a href="{{ route('admin.activities') }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x"></i> Clear
                </a>
                @endif
            </div>

            <div style="font-size:12px;color:#9CA3AF;">
                {{ $activities->total() }} activit{{ $activities->total() == 1 ? 'y' : 'ies' }} total
            </div>
        </div>
    </form>

    {{-- Results info --}}
    <div style="font-size:11px;color:#9CA3AF;margin-bottom:10px;">
        Showing {{ $activities->firstItem() ?? 0 }}–{{ $activities->lastItem() ?? 0 }}
        of {{ $activities->total() }} activit{{ $activities->total() == 1 ? 'y' : 'ies' }}
        @if($search) matching "<strong>{{ $search }}</strong>" @endif
    </div>

    {{-- Table --}}
    <table class="dash-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Activity</th>
                <th>Type</th>
                <th>Teacher</th>
                <th>Level</th>
                <th>Difficulty</th>
                <th>Points</th>
                <th>Status</th>
                <th>Battle</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $a)
            <tr>
                <td style="color:#9CA3AF;font-size:11px;">
                    {{ $activities->firstItem() + $loop->index }}
                </td>
                <td>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $a->activity_name }}
                    </div>
                    @if($a->description)
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ Str::limit($a->description, 40) }}
                    </div>
                    @endif
                </td>
                <td>
                    <span class="status-badge badge-blue">
                        {{ $a->activity_type }}
                    </span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $a->teacher?->firstname }}
                    {{ $a->teacher?->lastname }}
                </td>
                <td>
                    <span class="status-badge badge-amber">
                        L{{ $a->level }}
                    </span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $a->difficulty_level }}
                </td>
                <td style="font-size:12px;font-weight:700;color:#F59E0B;">
                    ⭐ {{ $a->points_reward }}
                </td>
                <td>
                    <span class="status-badge
                        {{ $a->is_published ? 'badge-green' : 'badge-red' }}">
                        {{ $a->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td>
                    @if($a->battle_mode)
                    <span class="status-badge"
                          style="background:#EDE9FE;color:#5B21B6;">
                        ⚔️ Yes
                    </span>
                    @else
                    <span style="font-size:11px;color:#D1D5DB;">—</span>
                    @endif
                </td>
                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($a->created_at)->format('M d, Y') }}
                </td>
                <td>
                    <form method="POST"
                          action="{{ route('admin.activities.delete', $a->id) }}"
                          onsubmit="return confirm('Delete {{ $a->activity_name }}?')">
                        @csrf @method('DELETE')
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
                <td colspan="11" class="text-center text-muted py-5">
                    <div style="font-size:32px;">📖</div>
                    <div class="mt-2">No activities found.</div>
                    @if($search || $type || $status)
                    <a href="{{ route('admin.activities') }}"
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
    @if($activities->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <div style="font-size:12px;color:#9CA3AF;">
            Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }}
        </div>
        <div class="d-flex gap-1 align-items-center">

            @if($activities->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;background:#F3F4F6;
                         color:#D1D5DB;font-size:12px;cursor:not-allowed;">← Prev</span>
            @else
            <a href="{{ $activities->previousPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;
                      border:1px solid #E5E7EB;color:#374151;font-size:12px;
                      text-decoration:none;font-weight:600;">← Prev</a>
            @endif

            @foreach($activities->getUrlRange(
                max(1, $activities->currentPage() - 2),
                min($activities->lastPage(), $activities->currentPage() + 2)
            ) as $page => $url)
            @if($page == $activities->currentPage())
            <span style="padding:6px 12px;border-radius:8px;background:#DC2626;
                         color:#fff;font-size:12px;font-weight:700;">{{ $page }}</span>
            @else
            <a href="{{ $url }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;
                      border:1px solid #E5E7EB;color:#374151;font-size:12px;
                      text-decoration:none;font-weight:600;">{{ $page }}</a>
            @endif
            @endforeach

            @if($activities->hasMorePages())
            <a href="{{ $activities->nextPageUrl() }}"
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