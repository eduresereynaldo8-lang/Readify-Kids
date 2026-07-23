@extends('layouts.teacher')
@section('title', 'Add Student')
@section('page-title', 'Add New Student')
@section('page-sub', 'Create a student account.')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="dash-card">
            <h6 class="fw-semibold mb-3">Student Information</h6>

            @if($errors->any())
            <div class="alert alert-danger small">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('teacher.students.store') }}">
                @csrf
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="firstname" class="form-control form-control-sm"
                               placeholder="First name" value="{{ old('firstname') }}" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="lastname" class="form-control form-control-sm"
                               placeholder="Last name" value="{{ old('lastname') }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Student ID <span class="text-danger">*</span></label>
                    <input type="text" name="student_number" class="form-control form-control-sm"
                           placeholder="e.g. STU-001" value="{{ old('student_number') }}" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Section <span class="text-danger">*</span></label>
                        <select name="section" class="form-select form-select-sm" required>
                            <option value="">Select section</option>
                            <option value="Section A" {{ old('section')=='Section A'?'selected':'' }}>Section A</option>
                            <option value="Section B" {{ old('section')=='Section B'?'selected':'' }}>Section B</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Starting Level <span class="text-danger">*</span></label>
                        <select name="current_level" class="form-select form-select-sm" required>
                            <option value="1" {{ old('current_level')==1?'selected':'' }}>Level 1</option>
                            <option value="2" {{ old('current_level')==2?'selected':'' }}>Level 2</option>
                            <option value="3" {{ old('current_level')==3?'selected':'' }}>Level 3</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control form-control-sm"
                           placeholder="Student login username" value="{{ old('username') }}" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm"
                               placeholder="Password" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm"
                               placeholder="Confirm password" required>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-2">
                    <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection