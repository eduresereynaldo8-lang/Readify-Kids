@extends('layouts.admin')
@section('title', 'Teachers')
@section('page-title', 'Teacher Management')
@section('page-sub', 'View and manage all teacher accounts.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div style="font-size:13px;font-weight:600;color:#111827;">
            {{ $teachers->count() }} teachers registered
        </div>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search teacher…" style="width:200px;"
               onkeyup="filterTable()">

               <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div style="font-size:13px;font-weight:600;color:#111827;">
        {{ $teachers->count() }} teachers registered
    </div>
    <div class="d-flex gap-2 align-items-center">
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search teacher…" style="width:200px;"
               onkeyup="filterTable()">
        <a href="{{ route('admin.teachers.create') }}"
           style="display:inline-flex;align-items:center;gap:6px;
                  padding:7px 16px;border-radius:10px;
                  background:linear-gradient(135deg,#DC2626,#991B1B);
                  color:#fff;font-size:13px;font-weight:700;text-decoration:none;
                  box-shadow:0 2px 0 rgba(0,0,0,.15);">
            <i class="ti ti-plus"></i> Add Teacher
        </a>
    </div>
</div>
    </div>

    <table class="dash-table" id="teacherTable">
        <thead>
            <tr>
                <th>Teacher</th>
                <th>Email</th>
                <th>School</th>
                <th>Students</th>
                <th>Activities</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $t)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:50%;
                                    background:#FEE2E2;color:#991B1B;font-size:11px;
                                    font-weight:700;display:flex;align-items:center;
                                    justify-content:center;flex-shrink:0;">
                            {{ strtoupper(substr($t->firstname,0,1).substr($t->lastname,0,1)) }}
                        </div>
                        {{ $t->firstname }} {{ $t->lastname }}
                    </div>
                </td>
                <td style="color:#9CA3AF;">{{ $t->user->email }}</td>
                <td style="font-size:11px;">{{ $t->school_name }}</td>
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
                    <span class="status-badge {{ $t->user->email_verified_at ? 'badge-green' : 'badge-red' }}">
                        {{ $t->user->email_verified_at ? '✓ Active' : '✗ Inactive' }}
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        {{-- Toggle active --}}
                        <form method="POST"
                              action="{{ route('admin.teachers.toggle', $t->id) }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm {{ $t->user->email_verified_at ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                    title="{{ $t->user->email_verified_at ? 'Deactivate' : 'Activate' }}">
                                <i class="ti ti-{{ $t->user->email_verified_at ? 'ban' : 'check' }}"></i>
                            </button>
                        </form>
                        {{-- Delete --}}
                        <form method="POST"
                              action="{{ route('admin.teachers.delete', $t->id) }}"
                              onsubmit="return confirm('Delete this teacher and all their data?')">
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
                <td colspan="7" class="text-center text-muted py-4">No teachers yet.</td>
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
    const rows = document.querySelectorAll('#teacherTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush