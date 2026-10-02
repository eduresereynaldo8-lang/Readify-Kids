@extends('layouts.admin')
@section('title', 'Teacher Profile')
@section('page-title', 'Teacher Profile')
@section('page-sub', 'Teacher information and class summary.')

@section('content')
<div class="profile-page">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.teachers') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to Teachers</a>
        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-primary"><i class="ti ti-edit" aria-hidden="true"></i> Edit Teacher</a>
    </div>
    <section class="dash-card mb-4">
        <h2 class="dash-card-title">Teacher Information</h2>
        <dl class="profile-details">
            <div><dt>Full Name</dt><dd>{{ $teacher->firstname }} {{ $teacher->lastname }}</dd></div>
            <div><dt>Username</dt><dd>{{ $teacher->user->username }}</dd></div>
            <div><dt>Email</dt><dd>{{ $teacher->user->email ?: 'Not provided' }}</dd></div>
            <div><dt>School</dt><dd>{{ $teacher->school_name ?: 'Not provided' }}</dd></div>
            <div><dt>Account Status</dt><dd>{{ ucfirst($teacher->user->status ?? 'Not available') }}</dd></div>
            <div><dt>Date Joined</dt><dd>{{ $teacher->user->created_at?->format('F j, Y') ?? 'Not available' }}</dd></div>
        </dl>
    </section>
    <section class="dash-card mb-4">
        <h2 class="dash-card-title">Class Summary</h2>
        <dl class="profile-details">
            <div><dt>Students</dt><dd>{{ number_format($teacher->students_count) }}</dd></div>
            <div><dt>Activities</dt><dd>{{ number_format($teacher->activities_count) }}</dd></div>
            <div><dt>Published Activities</dt><dd>{{ number_format($teacher->published_activities_count) }}</dd></div>
            <div><dt>Evaluations Completed</dt><dd>{{ number_format($teacher->evaluations_count) }}</dd></div>
        </dl>
    </section>
    <section class="dash-card">
        <h2 class="dash-card-title">Assigned Students</h2>
        <div class="table-responsive" role="region" aria-label="Assigned Students" tabindex="0">
            <table class="dash-table">
                <thead><tr><th>Student</th><th>LRN</th><th>Section</th><th>Current Level</th><th>Reading Status</th></tr></thead>
                <tbody>
                @forelse($assignedStudents as $learner)
                    <tr>
                        <td>{{ $learner->firstname }} {{ $learner->lastname }}</td>
                        <td>{{ $learner->lrn_no ?: 'Not provided' }}</td>
                        <td>{{ $learner->section ?: 'Not provided' }}</td>
                        <td>Level {{ $learner->current_level }}</td>
                        <td><span class="status-badge {{ $learner->reading_status['badge'] }}">{{ $learner->reading_status['label'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No students assigned yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($assignedStudents->hasPages())
        <nav class="d-flex align-items-center justify-content-between mt-3" aria-label="Assigned students pages">
            @if($assignedStudents->onFirstPage())<span class="text-muted">Previous</span>@else<a href="{{ $assignedStudents->previousPageUrl() }}">Previous</a>@endif
            <span>Page {{ $assignedStudents->currentPage() }} of {{ $assignedStudents->lastPage() }}</span>
            @if($assignedStudents->hasMorePages())<a href="{{ $assignedStudents->nextPageUrl() }}">Next</a>@else<span class="text-muted">Next</span>@endif
        </nav>
        @endif
    </section>
</div>
@endsection
