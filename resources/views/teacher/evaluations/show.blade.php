@extends('layouts.teacher')
@section('title', 'Evaluate Recording')
@section('page-title', 'Manual Reading Evaluation')
@section('page-sub', 'Listen to the recording and score the student.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="row g-3">

    {{-- Left: recording + passage --}}
    <div class="col-md-7">

        {{-- Student info strip --}}
        <div class="dash-card mb-3 d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;border-radius:50%;background:#DBEAFE;color:#1E40AF;
                        display:flex;align-items:center;justify-content:center;
                        font-size:16px;font-weight:700;flex-shrink:0;">
                {{ strtoupper(substr($recording->student->firstname,0,1).substr($recording->student->lastname,0,1)) }}
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">{{ $recording->student->firstname }} {{ $recording->student->lastname }}</div>
                <div class="text-muted small">
                    {{ $recording->student->student_number }} ·
                    {{ $recording->activity->activity_name }} ·
                    Attempt {{ $recording->attempt_number }}
                </div>
            </div>
            <span class="status-badge {{ $recording->status === 'pending' ? 'badge-amber' : 'badge-green' }}">
                {{ ucfirst($recording->status) }}
            </span>
        </div>

        {{-- Audio player --}}
        <div class="dash-card mb-3">
            <div class="dash-card-title">🎙️ Recording</div>
            <audio controls class="w-100" style="border-radius:8px;">
                <source src="{{ asset('storage/' . $recording->recording_path) }}" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            <div class="text-muted small mt-2">
                Submitted {{ \Carbon\Carbon::parse($recording->created_at)->diffForHumans() }}
            </div>
        </div>

        {{-- Reading passage --}}
        @if($recording->activity->readingMaterial)
        <div class="dash-card">
            <div class="dash-card-title">📄 Reading Passage</div>
            <div style="background:#F8FAFF;border:1px solid #DBEAFE;border-radius:8px;
                        padding:14px;font-size:13px;line-height:2;color:#1E3A5F;">
                {{ $recording->activity->readingMaterial->content }}
            </div>
        </div>
        @endif

    </div>

    {{-- Right: evaluation form --}}
    <div class="col-md-5">
        <div class="dash-card">
            <div class="dash-card-title">📝 Evaluation Form</div>

            <form method="POST" action="{{ route('teacher.evaluations.store') }}">
                @csrf
                <input type="hidden" name="recording_id" value="{{ $recording->id }}">

                {{-- Star ratings --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Pronunciation <span class="text-danger">*</span></label>
                    <div class="d-flex gap-1" id="pronunciation-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="star-btn" data-group="pronunciation" data-value="{{ $i }}"
                              style="font-size:24px;cursor:pointer;color:#D1D5DB;">★</span>
                        @endfor
                    </div>
                    <input type="hidden" name="pronunciation_score" id="pronunciation_score"
                           value="{{ old('pronunciation_score', $recording->evaluation?->pronunciation_score) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Fluency <span class="text-danger">*</span></label>
                    <div class="d-flex gap-1" id="fluency-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="star-btn" data-group="fluency" data-value="{{ $i }}"
                              style="font-size:24px;cursor:pointer;color:#D1D5DB;">★</span>
                        @endfor
                    </div>
                    <input type="hidden" name="fluency_score" id="fluency_score"
                           value="{{ old('fluency_score', $recording->evaluation?->fluency_score) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Accuracy <span class="text-danger">*</span></label>
                    <div class="d-flex gap-1" id="accuracy-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="star-btn" data-group="accuracy" data-value="{{ $i }}"
                              style="font-size:24px;cursor:pointer;color:#D1D5DB;">★</span>
                        @endfor
                    </div>
                    <input type="hidden" name="accuracy_score" id="accuracy_score"
                           value="{{ old('accuracy_score', $recording->evaluation?->accuracy_score) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Comprehension <span class="text-danger">*</span></label>
                    <div class="d-flex gap-1" id="comprehension-stars">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="star-btn" data-group="comprehension" data-value="{{ $i }}"
                              style="font-size:24px;cursor:pointer;color:#D1D5DB;">★</span>
                        @endfor
                    </div>
                    <input type="hidden" name="comprehension_score" id="comprehension_score"
                           value="{{ old('comprehension_score', $recording->evaluation?->comprehension_score) }}" required>
                </div>

                {{-- Proficiency level --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Proficiency Level <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach(['Beginner','Developing','Proficient','Advanced'] as $level)
                        <div class="prof-btn border rounded px-3 py-1"
                             style="cursor:pointer;font-size:12px;
                             {{ (old('proficiency_level', $recording->evaluation?->proficiency_level) == $level) ? 'background:#DBEAFE;border-color:#185FA5;color:#1E40AF;font-weight:600;' : '' }}"
                             onclick="selectProf('{{ $level }}', this)">
                            {{ $level }}
                        </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="proficiency_level" id="proficiency_level"
                           value="{{ old('proficiency_level', $recording->evaluation?->proficiency_level) }}" required>
                </div>

                {{-- Feedback --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Teacher Feedback & Notes</label>
                    <textarea name="feedback" class="form-control form-control-sm" rows="4"
                              placeholder="Write your feedback for this student…">{{ old('feedback', $recording->evaluation?->feedback) }}</textarea>
                </div>

                {{-- Score preview --}}
                <div class="p-2 rounded mb-3 text-center"
                     style="background:#F0FDF4;border:1px solid #BBF7D0;">
                    <div style="font-size:11px;color:#166534;">Computed Score</div>
                    <div style="font-size:22px;font-weight:700;color:#166534;" id="score-preview">—</div>
                </div>

                <div class="d-flex gap-2 justify-content-between">
                    <a href="{{ route('teacher.evaluations.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-circle-check"></i> Save Evaluation
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Star rating logic
const groups = ['pronunciation', 'fluency', 'accuracy', 'comprehension'];

groups.forEach(group => {
    const stars = document.querySelectorAll(`[data-group="${group}"]`);
    const input = document.getElementById(`${group}_score`);

    // Pre-fill if value exists
    if (input.value) highlightStars(stars, parseInt(input.value));

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const val = parseInt(this.dataset.value);
            input.value = val;
            highlightStars(stars, val);
            updateScorePreview();
        });
        star.addEventListener('mouseover', function() {
            highlightStars(stars, parseInt(this.dataset.value));
        });
        star.addEventListener('mouseout', function() {
            highlightStars(stars, parseInt(input.value) || 0);
        });
    });
});

function highlightStars(stars, value) {
    stars.forEach(s => {
        s.style.color = parseInt(s.dataset.value) <= value ? '#F59E0B' : '#D1D5DB';
    });
}

function updateScorePreview() {
    const p = parseInt(document.getElementById('pronunciation_score').value) || 0;
    const f = parseInt(document.getElementById('fluency_score').value) || 0;
    const a = parseInt(document.getElementById('accuracy_score').value) || 0;
    const c = parseInt(document.getElementById('comprehension_score').value) || 0;
    const filled = [p,f,a,c].filter(v => v > 0).length;
    if (filled === 4) {
        const avg = ((p + f + a + c) / 4 * 20).toFixed(1);
        document.getElementById('score-preview').textContent = avg + '%';
    }
}

// Pre-fill score preview
updateScorePreview();

// Proficiency level selector
function selectProf(level, el) {
    document.querySelectorAll('.prof-btn').forEach(b => {
        b.style.background = '';
        b.style.borderColor = '';
        b.style.color = '';
        b.style.fontWeight = '';
    });
    el.style.background = '#DBEAFE';
    el.style.borderColor = '#185FA5';
    el.style.color = '#1E40AF';
    el.style.fontWeight = '600';
    document.getElementById('proficiency_level').value = level;
}
</script>
@endpush