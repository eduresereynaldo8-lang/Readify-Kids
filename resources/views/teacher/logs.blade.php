@extends('layouts.teacher')
@section('title', 'My Activity Logs')
@section('page-title', 'My Activity Logs')
@section('page-sub', 'Your personal activity history.')

@section('content')

<div class="dash-card">

    <form method="GET" action="{{ route('teacher.logs') }}">
        <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
            <select name="action" class="form-select form-select-sm"
                    style="width:180px;" onchange="this.form.submit()">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                <option value="{{ $act }}" {{ $action===$act?'selected':'' }}>{{ $act }}</option>
                @endforeach
            </select>

            <input type="date" name="date" value="{{ $date }}"
                   class="form-control form-control-sm"
                   style="width:150px;" onchange="this.form.submit()">

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-filter"></i> Filter
            </button>

            @if($action || $date)
            <a href="{{ route('teacher.logs') }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-x"></i> Clear
            </a>
            @endif

            <div style="margin-left:auto;font-size:11px;color:#9CA3AF;">
                {{ $logs->total() }} log(s)
            </div>
        </div>
    </form>

    <table class="dash-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Action</th>
                <th>Module</th>
                <th>Description</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            @php
                $actionColors = [
                    'LOGIN'            => '#10B981',
                    'LOGOUT'           => '#6B7280',
                    'EVALUATE'         => '#8B5CF6',
                    'ADD_STUDENT'      => '#3B82F6',
                    'CREATE_ACTIVITY'  => '#06B6D4',
                ];
                $ac = $actionColors[$log->action] ?? '#9CA3AF';
            @endphp
            <tr>
                <td style="font-size:11px;color:#9CA3AF;">
                    {{ $logs->firstItem() + $loop->index }}
                </td>
                <td>
                    <span style="color:{{ $ac }};font-size:12px;font-weight:700;">
                        {{ $log->action }}
                    </span>
                </td>
                <td style="font-size:12px;color:#6B7280;">{{ $log->module ?? '—' }}</td>
                <td style="font-size:12px;color:#374151;">{{ $log->description ?? '—' }}</td>
                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <div style="font-size:32px;">📋</div>
                    <div class="mt-2">No activity logs yet.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
    <div class="d-flex justify-content-end mt-3 gap-1">
        @if(!$logs->onFirstPage())
        <a href="{{ $logs->previousPageUrl() }}" style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">← Prev</a>
        @endif
        @foreach($logs->getUrlRange(max(1,$logs->currentPage()-2),min($logs->lastPage(),$logs->currentPage()+2)) as $page => $url)
        @if($page == $logs->currentPage())
        <span style="padding:6px 12px;border-radius:8px;background:#185FA5;color:#fff;font-size:12px;font-weight:700;">{{ $page }}</span>
        @else
        <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">{{ $page }}</a>
        @endif
        @endforeach
        @if($logs->hasMorePages())
        <a href="{{ $logs->nextPageUrl() }}" style="padding:6px 12px;border-radius:8px;background:#fff;border:1px solid #E5E7EB;color:#374151;font-size:12px;text-decoration:none;font-weight:600;">Next →</a>
        @endif
    </div>
    @endif
</div>
@endsection