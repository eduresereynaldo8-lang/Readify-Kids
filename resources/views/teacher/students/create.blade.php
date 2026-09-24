@extends('layouts.teacher')
@section('title', 'Add Student')
@section('page-title', 'Add New Student')
@section('page-sub', 'Create a student account.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-management.css') }}">
@endpush
@section('content')
<div class="sm-form-page">
    <section class="sm-form-card">
        <div class="sm-card-heading">
            <span class="sm-heading-icon"><i class="ti ti-user" aria-hidden="true"></i></span>
            <div><h2>Student Information</h2><p>Fill in the details below to create a new student account.</p></div>
        </div>
        @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the highlighted fields.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('teacher.students.store') }}" class="sm-student-form" data-today="{{ today()->toDateString() }}">
            @csrf
            
            @include('teacher.students.form', ['editing' => false])
            <div class="sm-form-actions">
                <a href="{{ route('teacher.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i> Add Student</button>
            </div>
        </form>
    </section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/student-management.js') }}" defer></script>
@endpush
