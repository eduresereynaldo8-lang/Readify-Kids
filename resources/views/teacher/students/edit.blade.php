@extends('layouts.teacher')
@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('page-sub', 'Update student information.')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="dash-card">
            <h6 class="fw-semibold mb-3">Edit: {{ $student->firstname }} {{ $student->lastname }}</h6>

            @if($errors->any())
            <div class="alert alert-danger small">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('teacher.students.update', $student->id) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">First Name</label>
                        <input type="text" name="firstname" class="form-control form-control-sm"
                               value="{{ old('firstname', $student->firstname) }}" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Last Name</label>
                        <input type="text" name="lastname" class="form-control form-control-sm"
                               value="{{ old('lastname', $student->lastname) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Section</label>
                        <select name="section" class="form-select form-select-sm" required>
                            <option value="Section A" {{ $student->section=='Section A'?'selected':'' }}>Section A</option>
                            <option value="Section B" {{ $student->section=='Section B'?'selected':'' }}>Section B</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label small fw-semibold">Level</label>
                        <select name="current_level" class="form-select form-select-sm" required>
                            <option value="1" {{ $student->current_level==1?'selected':'' }}>Level 1</option>
                            <option value="2" {{ $student->current_level==2?'selected':'' }}>Level 2</option>
                            <option value="3" {{ $student->current_level==3?'selected':'' }}>Level 3</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-2">
                    <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection