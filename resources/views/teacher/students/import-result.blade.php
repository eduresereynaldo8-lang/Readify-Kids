@extends('layouts.teacher')
@section('title', 'Import Complete')
@section('page-title', 'Import Complete')
@section('page-sub', 'Save the new student login credentials.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-management.css') }}">
@endpush
@section('content')
<div class="sm-form-page" style="max-width:none">
    <section class="sm-form-card">
        <div class="sm-card-heading">
            <span class="sm-heading-icon"><i class="ti ti-user-check" aria-hidden="true"></i></span>
            <div><h2>Import Complete</h2><p>New students start with No Data until they have scored activity results.</p></div>
        </div>
        <div class="d-flex flex-wrap gap-4 mb-4" role="status">
            <span>Successfully Imported: <strong>{{ count($result['imported']) }}</strong></span>
            <span>Skipped: <strong>{{ count($result['skipped']) }}</strong></span>
            <span>Total Processed: <strong>{{ $result['total'] }}</strong></span>
        </div>
        @if($result['imported'])
            <div class="alert alert-info">Download these credentials before finishing. They are available for 30 minutes in this signed-in session, and are cleared when you finish, log out, or preview another import.</div>
            <a href="{{ route('teacher.students.import.credentials', ['batch' => $batch['id']]) }}" class="btn btn-primary mb-3"><i class="ti ti-download" aria-hidden="true"></i> Download Student Credentials</a>
            <div class="table-responsive" tabindex="0" aria-label="New student login credentials">
                <table class="table table-hover align-middle">
                    <thead><tr><th scope="col">Student Name</th><th scope="col">LRN</th><th scope="col">Username</th><th scope="col">Temporary Password</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                    @foreach($result['imported'] as $credential)
                        <tr>
                            <td>{{ $credential['name'] }}</td>
                            <td>{{ $credential['lrn_no'] }}</td>
                            <td>{{ $credential['username'] }}</td>
                            <td><code class="text-dark">{{ $credential['password'] }}</code></td>
                            <td><span class="badge bg-success">Imported</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($result['skipped'])
            <h3 class="h6 fw-bold mt-4">Skipped Students</h3>
            <p class="text-muted small">Fix these rows in your original template and preview them again.</p>
            <a href="{{ route('teacher.students.import.errors', ['batch' => $batch['id']]) }}" class="btn btn-sm btn-outline-secondary mb-3">Download Skipped Rows</a>
            <div class="table-responsive" tabindex="0" aria-label="Skipped import rows">
                <table class="table small"><thead><tr><th scope="col">No.</th><th scope="col">Student Name</th><th scope="col">LRN</th><th scope="col">Reason</th></tr></thead>
                    <tbody>@foreach($result['skipped'] as $row)<tr><td>{{ $row['row'] }}</td><td>{{ $row['data']['first_name'] }} {{ $row['data']['last_name'] }}</td><td>{{ $row['data']['lrn_no'] }}</td><td>{{ implode(' ', $row['errors']) }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        @endif
        <form method="POST" action="{{ route('teacher.students.import.finish') }}" class="sm-form-actions">
            @csrf
            <input type="hidden" name="batch" value="{{ $batch['id'] }}">
            <button type="submit" class="btn btn-outline-secondary">Finish and Clear Credentials</button>
        </form>
    </section>
</div>
@endsection
