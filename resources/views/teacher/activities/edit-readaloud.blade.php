@extends('layouts.teacher')
@section('title', 'Edit Read Aloud')
@section('page-title', 'Edit Read Aloud Activity')
@section('page-sub', 'Update the reading passage and settings.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('teacher.activities.update.readaloud', $activity->id) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-8">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-info-circle text-primary"></i> Basic Information</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Activity Title <span class="text-danger">*</span></label>
                <input type="text" name="activity_name" class="form-control form-control-sm"
                       value="{{ old('activity_name', $activity->activity_name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $activity->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Level</label>
                    <select name="level" class="form-select form-select-sm" required>
                        @foreach([1,2,3] as $l)
                        <option value="{{ $l }}" {{ $activity->level == $l ? 'selected' : '' }}>Level {{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Difficulty</label>
                    <select name="difficulty_level" class="form-select form-select-sm" required>
                        @foreach(['Easy','Medium','Hard'] as $d)
                        <option value="{{ $d }}" {{ $activity->difficulty_level == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Duration (min)</label>
                    <input type="number" name="duration_minutes" class="form-control form-control-sm"
                           value="{{ old('duration_minutes', $activity->duration_minutes) }}" min="1" required>
                </div>
            </div>
        </div>

        <div class="dash-card">
            <h6 class="fw-semibold mb-3"><i class="ti ti-file-text text-primary"></i> 📖 Reading Passage</h6>
            <div class="mb-2">
                <label class="form-label small fw-semibold">Passage Content <span class="text-danger">*</span></label>
                <textarea name="passage" class="form-control form-control-sm" rows="8"
                          placeholder="Type the full reading passage here.">{{ old('passage', $activity->readingMaterial->content ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-star text-warning"></i> Points Reward</h6>
            <input type="number" name="points_reward" class="form-control form-control-sm mb-2"
                   value="{{ old('points_reward', $activity->points_reward) }}" min="1" required>
        </div>
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-settings text-primary"></i> Settings</h6>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published"
                       {{ $activity->is_published ? 'checked' : '' }}>
                <label class="form-check-label small" for="is_published">Published</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="allow_reattempt" id="allow_reattempt"
                       {{ $activity->allow_reattempt ? 'checked' : '' }}>
                <label class="form-check-label small" for="allow_reattempt">Allow re-attempts</label>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-3 p-3 bg-white rounded border">
    <a href="{{ route('teacher.activities.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="ti ti-device-floppy"></i> Save Changes
    </button>
</div>
</form>
@endsection
