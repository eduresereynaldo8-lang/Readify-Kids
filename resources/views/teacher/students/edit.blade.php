@extends('layouts.teacher')
@section('title', 'Edit Student')
@section('page-title', 'Edit Student')
@section('page-sub', 'Update student information.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-management.css') }}">
@endpush
@section('content')
<div class="sm-form-page">
    <section class="sm-form-card">
        <div class="sm-card-heading">
            <span class="sm-heading-icon"><i class="ti ti-user" aria-hidden="true"></i></span>
            <div><h2>Student Information</h2><p>Update the details for {{ $student->firstname }} {{ $student->lastname }}.</p></div>
        </div>
        @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the highlighted fields.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('teacher.students.update', $student->id) }}" class="sm-student-form" data-today="{{ today()->toDateString() }}">
            @csrf
            @method('PUT')
            @include('teacher.students.form', ['editing' => true])
            <div class="sm-form-actions">
                <a href="{{ route('teacher.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy" aria-hidden="true"></i> Save Changes</button>
            </div>
        </form>
    </section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/student-management.js') }}" defer></script>
@endpush
