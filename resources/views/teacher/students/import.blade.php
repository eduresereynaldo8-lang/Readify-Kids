@extends('layouts.teacher')
@section('title', 'Import Students')
@section('page-title', 'Import Students')
@section('page-sub', 'Upload an Excel or CSV file to add multiple students at once.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/student-management.css') }}">
@endpush
@section('content')
<div class="sm-form-page" style="max-width:none">
    <section class="sm-form-card mb-4">
        <div class="sm-card-heading">
            <span class="sm-heading-icon"><i class="ti ti-file-upload" aria-hidden="true"></i></span>
            <div><h2>Import Students</h2><p>Upload an Excel or CSV file to add multiple students at once.</p></div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if($batch && $batch['phase'] === 'result')
            <div class="alert alert-info">Your last import is complete.
                <a href="{{ route('teacher.students.import.result', ['batch' => $batch['id']]) }}">View and download its credentials</a> before previewing another file.
            </div>
        @endif
        <div class="row g-4">
            <div class="col-md-5">
                <h3 class="h6 fw-bold">Step 1 · Download Template</h3>
                <p class="text-muted small">Enter student details on the first sheet. Starting levels are 1–5. Keep LRNs as text so leading zeros are preserved.</p>
                <a href="{{ route('teacher.students.import.template') }}" class="btn btn-outline-primary"><i class="ti ti-download" aria-hidden="true"></i> Download Import Template</a>
            </div>
            <div class="col-md-7">
                <h3 class="h6 fw-bold">Step 2 · Upload File</h3>
                <form method="POST" action="{{ route('teacher.students.import.preview') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="import-file" class="form-label">Choose File</label>
                    <input type="file" class="form-control" id="import-file" name="file" accept=".xlsx,.csv" required aria-describedby="import-file-help">
                    <p id="import-file-help" class="text-muted small mt-2">XLSX or comma-separated UTF-8 CSV · Maximum 5 MB · Up to {{ $maxRows }} students. Uploading only previews the file; no accounts are created yet.</p>
                    <button type="submit" class="btn btn-primary">Preview Students</button>
                </form>
            </div>
        </div>
    </section>

    @if($rows !== null)
        @php
            $validCount = count(array_filter($rows, fn ($row) => !$row['errors']));
            $invalidCount = count($rows) - $validCount;
        @endphp
        <section class="sm-form-card">
            <h3 class="h5 fw-bold">Step 3 · Preview Students</h3>
            <div class="d-flex gap-3 flex-wrap my-3" role="status">
                <span>Total Rows: <strong>{{ count($rows) }}</strong></span>
                <span class="text-success">Valid Records: <strong>{{ $validCount }}</strong></span>
                <span class="text-danger">Invalid Records: <strong>{{ $invalidCount }}</strong></span>
            </div>
            <p class="text-muted small">Only valid rows will be imported. Usernames are checked again at confirmation. This preview expires after 30 minutes.</p>
            <div class="table-responsive" tabindex="0" aria-label="Student import preview">
                <table class="table table-hover align-middle small">
                    <thead><tr><th scope="col">No.</th><th scope="col">Student Name</th><th scope="col">LRN</th><th scope="col">Birthday</th><th scope="col">Age</th><th scope="col">Gender</th><th scope="col">Section</th><th scope="col">Starting Level</th><th scope="col">Generated Username</th><th scope="col">Validation Status</th></tr></thead>
                    <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['row'] }}</td>
                            <td>{{ $row['data']['first_name'] }} {{ $row['data']['last_name'] }}</td>
                            <td class="text-nowrap">{{ $row['data']['lrn_no'] }}</td>
                            <td class="text-nowrap">{{ $row['data']['birthday'] }}</td>
                            <td>{{ $row['age'] ?? '—' }}</td>
                            <td>{{ $row['data']['gender'] }}</td>
                            <td>{{ $row['data']['section'] }}</td>
                            <td>{{ $row['data']['starting_level'] }}</td>
                            <td>{{ $row['username'] ?? '—' }}</td>
                            <td>
                                @if($row['errors'])
                                    <ul class="text-danger mb-0 ps-3">@foreach($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                                @else
                                    <span class="badge bg-success">{{ $row['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($invalidCount)
                <a href="{{ route('teacher.students.import.errors', ['batch' => $batch['id']]) }}" class="btn btn-sm btn-outline-secondary mt-2">Download Invalid Rows</a>
            @endif
            <div class="sm-form-actions">
                <form method="POST" action="{{ route('teacher.students.import.finish') }}">
                    @csrf
                    <input type="hidden" name="batch" value="{{ $batch['id'] }}">
                    <button type="submit" class="btn btn-outline-secondary">Cancel</button>
                </form>
                <form method="POST" action="{{ route('teacher.students.import.confirm') }}" id="confirm-import">
                    @csrf
                    <input type="hidden" name="batch" value="{{ $batch['id'] }}">
                    <button type="submit" class="btn btn-primary" @disabled($validCount === 0)>Import Valid Students</button>
                </form>
            </div>
            <p class="small text-muted mt-3 mb-0" id="import-progress" role="status" aria-live="polite">Step 4 · Confirm to create accounts and generate temporary passwords.</p>
        </section>
    @else
        <a href="{{ route('teacher.students.index') }}" class="btn btn-outline-secondary">Back to Student Management</a>
    @endif
</div>
@endsection
@push('scripts')
<script>
document.getElementById('confirm-import')?.addEventListener('submit', function () {
    this.querySelector('button[type="submit"]').disabled = true;
    document.getElementById('import-progress').textContent = 'Importing students and generating passwords. Please keep this page open.';
});
</script>
@endpush
