@extends('layouts.admin')
@section('title', 'Edit Teacher')
@section('page-title', 'Edit Teacher')
@section('page-sub', 'Update teacher account information.')

@section('content')

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="dash-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:48px;height:48px;border-radius:50%;
                            background:#FEE2E2;color:#991B1B;font-size:16px;
                            font-weight:700;display:flex;align-items:center;justify-content:center;">
                    {{ strtoupper(substr($teacher->firstname,0,1).substr($teacher->lastname,0,1)) }}
                </div>
                <div>
                    <div style="font-size:15px;font-weight:700;color:#111827;">
                        {{ $teacher->firstname }} {{ $teacher->lastname }}
                    </div>
                    <div style="font-size:12px;color:#9CA3AF;">{{ $teacher->school_name }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.teachers.update', $teacher->id) }}">
                @csrf @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                            First Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text" name="firstname" class="form-control form-control-sm"
                               value="{{ old('firstname', $teacher->firstname) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                            Last Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text" name="lastname" class="form-control form-control-sm"
                               value="{{ old('lastname', $teacher->lastname) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                        School Name <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="text" name="school_name" class="form-control form-control-sm"
                           value="{{ old('school_name', $teacher->school_name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                        Email <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="email" name="email" class="form-control form-control-sm"
                           value="{{ old('email', $teacher->user->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                        Username <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="text" name="username" class="form-control form-control-sm"
                           value="{{ old('username', $teacher->user->username) }}" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                            New Password <span style="font-size:10px;color:#9CA3AF;">(leave blank to keep)</span>
                        </label>
                        <input type="password" name="password" class="form-control form-control-sm"
                               placeholder="New password…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:12px;font-weight:700;color:#374151;">
                            Confirm Password
                        </label>
                        <input type="password" name="password_confirmation"
                               class="form-control form-control-sm" placeholder="Confirm…">
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.teachers') }}"
                       class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit"
                            style="padding:8px 22px;border-radius:10px;
                                   background:linear-gradient(135deg,#DC2626,#991B1B);
                                   color:#fff;font-size:13px;font-weight:700;
                                   border:none;cursor:pointer;">
                        <i class="ti ti-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection