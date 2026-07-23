@extends('layouts.teacher')

@section('title', 'Create Read Aloud Activity')
@section('page-title', 'Create Read Aloud Activity')
@section('page-sub', 'Create a reading passage for students to read and record.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('teacher.activities.store.readaloud') }}">
@csrf

<div class="row g-3">

    {{-- LEFT COLUMN --}}
    <div class="col-md-8">

        {{-- Basic Information --}}
        <div class="dash-card mb-3">

            <h6 class="fw-semibold mb-3">
                <i class="ti ti-info-circle text-primary"></i>
                Basic Information
            </h6>

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Activity Title <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="activity_name"
                    class="form-control form-control-sm"
                    placeholder="e.g. Read Aloud — The Brave Little Bird"
                    value="{{ old('activity_name') }}"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">
                    Description
                </label>

                <textarea
                    name="description"
                    rows="2"
                    class="form-control form-control-sm"
                    placeholder="Briefly describe the activity...">{{ old('description') }}</textarea>
            </div>

            <div class="row">

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold">
                        Level
                        <span class="text-danger">*</span>
                    </label>

                    <select name="level"
                            class="form-select form-select-sm"
                            required>

                        <option value="">Select level</option>

                        <option value="1"
                            {{ old('level')==1 ? 'selected' : '' }}>
                            Level 1
                        </option>

                        <option value="2"
                            {{ old('level')==2 ? 'selected' : '' }}>
                            Level 2
                        </option>

                        <option value="3"
                            {{ old('level')==3 ? 'selected' : '' }}>
                            Level 3
                        </option>

                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold">
                        Difficulty
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="difficulty_level"
                        class="form-select form-select-sm"
                        required>

                        <option value="">Select difficulty</option>

                        <option value="Easy"
                            {{ old('difficulty_level')=='Easy' ? 'selected' : '' }}>
                            Easy
                        </option>

                        <option value="Medium"
                            {{ old('difficulty_level')=='Medium' ? 'selected' : '' }}>
                            Medium
                        </option>

                        <option value="Hard"
                            {{ old('difficulty_level')=='Hard' ? 'selected' : '' }}>
                            Hard
                        </option>

                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold">
                        Duration (min)
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="number"
                        name="duration_minutes"
                        class="form-control form-control-sm"
                        value="{{ old('duration_minutes') }}"
                        min="1"
                        placeholder="15"
                        required>
                </div>

            </div>

        </div>

        {{-- Reading Passage --}}
        <div class="dash-card">

            <h6 class="fw-semibold mb-3">
                <i class="ti ti-book text-primary"></i>
                📖 Reading Passage
            </h6>

            <p class="small text-muted mb-2">
                Enter the passage students will read aloud.
                This passage will appear during recording.
            </p>

            <div class="mb-3">

                <label class="form-label small fw-semibold">
                    Passage
                    <span class="text-danger">*</span>
                </label>

                <textarea
                    name="passage"
                    rows="10"
                    class="form-control form-control-sm"
                    placeholder="Type the reading passage here..."
                    required>{{ old('passage') }}</textarea>

            </div>

            <div class="alert alert-info small mt-3 py-2" style="font-size:11px;">

               

            </div>

        </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-md-4">

        {{-- Points --}}
        <div class="dash-card mb-3">

            <h6 class="fw-semibold mb-3">
                <i class="ti ti-star text-warning"></i>
                Points Reward
            </h6>

            <input
                type="number"
                name="points_reward"
                id="points_reward"
                class="form-control form-control-sm mb-2"
                value="{{ old('points_reward',50) }}"
                min="1"
                required
                oninput="document.getElementById('pts_preview').textContent=this.value">

            <div
                class="p-2 rounded text-center"
                style="background:#FFFBF0;border:1px solid #FDE68A;">

                <div style="font-size:10px;color:#92400E;">
                    Students will earn
                </div>

                <div style="font-size:22px;font-weight:700;color:#92400E;">
                    ⭐
                    <span id="pts_preview">
                        {{ old('points_reward',50) }}
                    </span>
                    pts
                </div>

            </div>

        </div>

        {{-- Settings --}}
        <div class="dash-card mb-3">

            <h6 class="fw-semibold mb-3">
                <i class="ti ti-settings text-primary"></i>
                Settings
            </h6>

            <div class="form-check form-switch mb-2">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="is_published"
                    id="is_published"
                    {{ old('is_published') ? 'checked' : '' }}>

                <label class="form-check-label small"
                       for="is_published">
                    Publish immediately
                </label>

            </div>

            <div class="form-check form-switch">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="allow_reattempt"
                    id="allow_reattempt"
                    {{ old('allow_reattempt',true) ? 'checked' : '' }}>

                <label class="form-check-label small"
                       for="allow_reattempt">
                    Allow re-attempts
                </label>

            </div>

        </div>

        {{-- Read Aloud Info --}}
        <div class="dash-card"
             style="background:#FFF7ED;border-color:#FED7AA;">

            <h6 class="fw-semibold mb-2"
                style="color:#9A3412;">

                <i class="ti ti-microphone"></i>

                Read Aloud

            </h6>

            <ul class="list-unstyled mb-0"
                style="font-size:12px;color:#9A3412;">

                <li>🎙️ Students record their reading</li>
                <li>📖 Read the displayed passage aloud</li>
                <li>📝 Teacher reviews pronunciation & fluency</li>
                <li>🏆 Earn points after evaluation</li>

            </ul>

        </div>

    </div>

</div>


{{-- Action Bar --}}
<div class="d-flex justify-content-between align-items-center mt-3 p-3 bg-white rounded border">

    <a href="{{ route('teacher.activities.index') }}"
       class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-x"></i>
        Cancel
    </a>

    <div class="d-flex gap-2">

        <button
            type="submit"
            name="save_draft"
            value="1"
            class="btn btn-sm btn-outline-primary">

            <i class="ti ti-device-floppy"></i>
            Save as Draft

        </button>

        <button
            type="submit"
            name="publish"
            value="1"
            class="btn btn-sm btn-primary">

            <i class="ti ti-send"></i>
            Publish Activity

        </button>

    </div>

</div>

</form>

@endsection