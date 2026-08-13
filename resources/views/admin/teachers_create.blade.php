@extends('layouts.admin')
@section('title', 'Add Teacher')
@section('page-title', 'Add New Teacher')
@section('page-sub', 'Create a new teacher account.')

@section('content')

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="dash-card">
            <div class="dash-card-title">👩‍🏫 New Teacher Account</div>

            <form method="POST" action="{{ route('admin.teachers.store') }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label"
                               style="font-size:12px;font-weight:700;color:#374151;">
                            First Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text" name="firstname"
                               class="form-control form-control-sm"
                               value="{{ old('firstname') }}"
                               placeholder="e.g. Maria" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"
                               style="font-size:12px;font-weight:700;color:#374151;">
                            Last Name <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="text" name="lastname"
                               class="form-control form-control-sm"
                               value="{{ old('lastname') }}"
                               placeholder="e.g. Santos" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"
                           style="font-size:12px;font-weight:700;color:#374151;">
                        School Name <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="text" name="school_name"
                           class="form-control form-control-sm"
                           value="{{ old('school_name') }}"
                           placeholder="e.g. Sagana Elementary School" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"
                           style="font-size:12px;font-weight:700;color:#374151;">
                        Email Address <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="email" name="email"
                           class="form-control form-control-sm"
                           value="{{ old('email') }}"
                           placeholder="teacher@school.edu.ph" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"
                           style="font-size:12px;font-weight:700;color:#374151;">
                        Username <span style="color:#DC2626;">*</span>
                    </label>
                    <input type="text" name="username"
                           class="form-control form-control-sm"
                           value="{{ old('username') }}"
                           placeholder="e.g. teacher_maria" required>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:3px;">
                        This is what the teacher uses to log in.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label"
                               style="font-size:12px;font-weight:700;color:#374151;">
                            Password <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="password" name="password"
                               class="form-control form-control-sm"
                               placeholder="Min. 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"
                               style="font-size:12px;font-weight:700;color:#374151;">
                            Confirm Password <span style="color:#DC2626;">*</span>
                        </label>
                        <input type="password" name="password_confirmation"
                               class="form-control form-control-sm"
                               placeholder="Re-enter password" required>
                    </div>
                </div>

                {{-- Info box --}}
                <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;
                            padding:12px 16px;margin-bottom:20px;font-size:12px;color:#92400E;">
                    ℹ️ The teacher account will be <strong>active immediately</strong>
                    after creation. You can deactivate it later from the teacher list.
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.teachers') }}"
                       class="btn btn-sm btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit"
                            style="padding:8px 22px;border-radius:10px;
                                   background:linear-gradient(135deg,#DC2626,#991B1B);
                                   color:#fff;font-size:13px;font-weight:700;
                                   border:none;cursor:pointer;
                                   box-shadow:0 2px 0 rgba(0,0,0,.15);">
                        <i class="ti ti-plus"></i> Create Teacher Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection