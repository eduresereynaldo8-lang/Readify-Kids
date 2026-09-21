@extends('layouts.admin')
@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('page-sub', 'All user activity across the system.')

@section('content')

<div class="dash-card">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.logs') }}">
        <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
            <div style="position:relative;">
                <i class="ti ti-search"
                   style="position:absolute;left:9px;top:50%;
                          transform:translateY(-50%);color:#9CA3AF;font-size:13px;"></i>
                <input type="text" name="search" value="{{ $search }}"
                       class="form-control form-control-sm"
                       placeholder="Search user or description…"
                       style="width:220px;padding-left:30px;">
            </div>

            <select name="role" class="form-select form-select-sm"
                    style="width:120px;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="admin"   {{ $role==='admin'  ?'selected':'' }}>Admin</option>
                <option value="teacher" {{ $role==='teacher'?'selected':'' }}>Teacher</option>
                <option value="student" {{ $role==='student'?'selected':'' }}>Student</option>
            </select>

            <select name="action" class="form-select form-select-sm"
                    style="width:160px;" onchange="this.form.submit()">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                <option value="{{ $act }}" {{ $action===$act?'selected':'' }}>
                    {{ $act }}
                </option>
                @endforeach
            </select>

            <input type="date" name="date" value="{{ $date }}"
                   class="form-control form-control-sm"
                   style="width:150px;" onchange="this.form.submit()">

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-filter"></i> Filter
            </button>

            @if($search || $role || $action || $date)
            <a href="{{ route('admin.logs') }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-x"></i> Clear
            </a>
            @endif

            <div style="margin-left:auto;font-size:11px;color:#9CA3AF;">
                {{ $logs->total() }} log(s) total
            </div>
        </div>
    </form>

    {{-- Table --}}
    <table class="dash-table">
        <thead>
            <tr>
                <th>#</th>
                <th>User</th>
                <th>Role</th>
                <th>Action</th>
                <th>Module</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            @php
                $roleColors = [
                    'admin'   => ['bg'=>'#FEE2E2','color'=>'#991B1B'],
                    'teacher' => ['bg'=>'#DBEAFE','color'=>'#1E40AF'],
                    'student' => ['bg'=>'#DCFCE7','color'=>'#166534'],
                ];
                $rc = $roleColors[$log->role] ?? ['bg'=>'#F3F4F6','color'=>'#374151'];

                $actionColors = [
                    'LOGIN'             => '#10B981',
                    'LOGOUT'            => '#6B7280',
                    'EVALUATE'          => '#8B5CF6',
                    'SUBMIT_RECORDING'  => '#F59E0B',
                    'BATTLE_ROUND'      => '#EF4444',
                    'ADD_STUDENT'       => '#3B82F6',
                    'CREATE_ACTIVITY'   => '#06B6D4',
                ];
                $ac = $actionColors[$log->action] ?? '#9CA3AF';
            @endphp
            <tr>
                <td style="color:#9CA3AF;font-size:11px;">
                    {{ $logs->firstItem() + $loop->index }}
                </td>
                <td>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $log->user->username ?? '—' }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ $log->user->email ?? '' }}
                    </div>
                </td>
                <td>
                    <span style="background:{{ $rc['bg'] }};color:{{ $rc['color'] }};
                                 font-size:10px;font-weight:700;
                                 padding:2px 8px;border-radius:20px;">
                        {{ ucfirst($log->role) }}
                    </span>
                </td>
                <td>
                    <span style="color:{{ $ac }};font-size:11px;font-weight:700;">
                        {{ $log->action }}
                    </span>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $log->module ?? '—' }}
                </td>
                <td style="font-size:11px;color:#374151;max-width:200px;">
                    {{ $log->description ?? '—' }}
                </td>
                <td style="font-size:11px;color:#9CA3AF;font-family:monospace;">
                    {{ $log->ip_address ?? '—' }}
                </td>
                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <div style="font-size:32px;">📋</div>
                    <div class="mt-2">No logs yet.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <div style="font-size:12px;color:#9CA3AF;">
            Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
        </div>
        <div class="d-flex gap-1">
            @if($logs->onFirstPage())
            <span style="padding:6px 12px;border-radius:8px;background:#F3F4F6;color:#D1D5DB;font-size:12px;">← Prev</span>
            @else
            <a href="{{ $logs->previousPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">← Prev</a>
            @endif

            @foreach($logs->getUrlRange(max(1,$logs->currentPage()-2),min($logs->lastPage(),$logs->currentPage()+2)) as $page => $url)
            @if($page == $logs->currentPage())
            <span style="padding:6px 12px;border-radius:8px;background:#DC2626;color:#fff;font-size:12px;font-weight:700;">{{ $page }}</span>
            @else
            <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">{{ $page }}</a>
            @endif
            @endforeach

            @if($logs->hasMorePages())
            <a href="{{ $logs->nextPageUrl() }}"
               style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">Next →</a>
            @else
            <span style="padding:6px 12px;border-radius:8px;background:#F3F4F6;color:#D1D5DB;font-size:12px;">Next →</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection