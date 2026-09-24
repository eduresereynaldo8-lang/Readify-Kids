@extends('layouts.teacher')
@section('title', 'Manual Reading Evaluation')
@section('page-title', 'Manual Reading Evaluation')
@section('page-sub', 'Listen, observe, and assess your student’s reading.')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/reading-assessment.css') }}">
@endpush

@section('content')
<div class="ra-page">
@if($errors->any())
<div class="alert alert-danger" role="alert"><strong>Please check your evaluation.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if($totalWords === 0)
<div class="alert alert-warning" role="alert">This activity has no readable passage. Add passage text before saving an evaluation.</div>
@endif
@if($recording->evaluation?->is_legacy)
<div class="alert alert-info">This is a legacy evaluation. Complete the new rubric to update it; its original rubric scores remain in history.</div>
@elseif($recording->evaluation && $recording->evaluation->total_words !== $totalWords)
<div class="alert alert-warning">The passage word count has changed since this evaluation. Saving will use the current passage and recalculate the oral reading score.</div>
@endif

<div class="ra-evaluation-grid">
    <div class="ra-stack">
        <section class="rk-card ra-panel">
            <h2 class="ra-panel-title"><i class="ti ti-user" aria-hidden="true"></i> Student Information</h2>
            <div class="ra-student">
                <span class="rk-avatar ra-student-avatar">{{ mb_strtoupper(mb_substr($recording->student->firstname, 0, 1).mb_substr($recording->student->lastname, 0, 1)) }}</span>
                <dl class="ra-student-details">
                    <dt>Student name</dt><dd>{{ $recording->student->firstname }} {{ $recording->student->lastname }}</dd>
                    @if($recording->student->student_number)<dt>LRN:</dt><dd>{{ $recording->student->student_number }}</dd>@endif
                    <dt>Activity</dt><dd>{{ $recording->activity->activity_name }}</dd>
                    <dt>Attempt</dt><dd>{{ $recording->attempt_number }}</dd>
                    <dt>Status</dt><dd><span class="rk-pill {{ $recording->status === 'evaluated' ? 'done' : 'pending' }}">{{ ucfirst($recording->status) }}</span></dd>
                </dl>
            </div>
        </section>
        <section class="rk-card ra-panel">
            <h2 class="ra-panel-title"><i class="ti ti-volume" aria-hidden="true"></i> Recording</h2>
            <audio controls preload="metadata" class="ra-audio" src="{{ asset('storage/' . $recording->recording_path) }}">Your browser does not support audio playback.</audio>
            <p class="rk-footnote">Submitted {{ $recording->created_at->format('M j, Y · g:i a') }}</p>
        </section>
        <section class="rk-card ra-panel ra-passage-panel">
            <h2 class="ra-panel-title"><i class="ti ti-book" aria-hidden="true"></i> Reading Passage</h2>
            <div class="ra-passage">
                <h3>{{ $recording->activity->readingMaterial?->title ?? 'No passage available' }}</h3>
                <p>{{ $passageText ?: 'Add passage text to this activity before evaluating.' }}</p>
                <div class="ra-word-count">Total words: <strong>{{ $totalWords }}</strong></div>
            </div>
        </section>
    </div>

    <section class="rk-card ra-panel">
        <h2 class="ra-panel-title"><i class="ti ti-file-description" aria-hidden="true"></i> Evaluation Form</h2>
        <form method="POST" action="{{ route('teacher.evaluations.store') }}" id="reading-assessment-form">
            @csrf
            <input type="hidden" name="recording_id" value="{{ $recording->id }}">
            <fieldset class="ra-fieldset">
                <legend class="ra-section-title"><span>1</span> Reading Details</legend>
                <div class="ra-detail-grid">
                    <div class="ra-time-input">
                        <label for="reading_minutes">Total reading time</label>
                        <div class="ra-time-fields">
                            <input class="form-control" type="number" id="reading_minutes" name="reading_minutes" min="0" max="71582788" step="1" required value="{{ old('reading_minutes', $recording->evaluation?->reading_minutes) }}" aria-label="Reading minutes"><span>minutes</span>
                            <input class="form-control" type="number" id="reading_seconds" name="reading_seconds" min="0" max="59" step="1" required value="{{ old('reading_seconds', $recording->evaluation?->reading_seconds) }}" aria-label="Reading seconds"><span>seconds</span>
                        </div>
                    </div>
                    <div><label for="total_words">Total words in passage</label><input class="form-control" type="number" id="total_words" value="{{ $totalWords }}" readonly aria-describedby="word-count-note"></div>
                    <div><label for="miscues">Total miscues</label><input class="form-control" type="number" id="miscues" name="miscues" min="0" max="{{ $totalWords }}" step="1" required value="{{ old('miscues', $recording->evaluation?->miscues) }}"></div>
                    <div><label for="correct_answers">Correct answers (optional)</label><input class="form-control" type="number" id="correct_answers" name="correct_answers" min="0" step="1" aria-describedby="comprehension-note" value="{{ old('correct_answers', $recording->evaluation?->correct_answers) }}"></div>
                    <div><label for="total_questions">Total questions (optional)</label><input class="form-control" type="number" id="total_questions" name="total_questions" min="1" max="4294967295" step="1" aria-describedby="comprehension-note" value="{{ old('total_questions', $recording->evaluation?->total_questions) }}"></div>
                </div>
                <p class="rk-footnote" id="word-count-note">Word count comes from the passage above.</p>
                <p class="rk-footnote" id="comprehension-note">No comprehension questions? Leave both fields blank. If there are questions, enter both values, including 0 when no answers are correct.</p>
            </fieldset>

            <section aria-labelledby="computed-scores-title">
                <h3 class="ra-section-title" id="computed-scores-title"><span>2</span> Computed Scores</h3>
                <div class="ra-computed-grid" aria-live="polite">
                    <div class="ra-computed ra-oral"><h4><i class="ti ti-chart-bar" aria-hidden="true"></i> Oral Reading Score</h4><p>(Number of Words − Miscues) / Number of Words × 100</p><strong id="oral-score">—</strong></div>
                    <div class="ra-computed ra-comprehension"><h4><i class="ti ti-brain" aria-hidden="true"></i> Comprehension Score</h4><p>Correct Answers / Total Questions × 100</p><strong id="comprehension-score">—</strong></div>
                </div>
            </section>

            <fieldset class="ra-fieldset">
                <legend class="ra-section-title"><span>3</span> Observation Level</legend>
                <div class="ra-observation-options">
                @foreach($observations as $level => $description)
                    <label class="ra-choice"><input type="radio" name="observation_level" value="{{ $level }}" required @checked(old('observation_level', $recording->evaluation?->observation_level) == $level)><span><strong>Level {{ $level }}</strong><small>{{ $description }}</small></span></label>
                @endforeach
                </div>
            </fieldset>

            <fieldset class="ra-fieldset">
                <legend class="ra-section-title"><span>4</span> Learner Experience</legend>
                <div class="ra-experience-options">
                @foreach([1 => ['😞', 'Did not enjoy'], 2 => ['🙁', 'Enjoyed a little'], 3 => ['😐', 'Neutral'], 4 => ['🙂', 'Enjoyed'], 5 => ['😃', 'Enjoyed very much']] as $rating => [$emoji, $description])
                    <label class="ra-experience-choice"><input type="radio" name="learner_experience" value="{{ $rating }}" required @checked(old('learner_experience', $recording->evaluation?->learner_experience) == $rating)><span class="ra-face" aria-hidden="true">{{ $emoji }}</span><strong>{{ $rating }}</strong><span class="visually-hidden">{{ $description }}</span></label>
                @endforeach
                </div>
            </fieldset>

            <div>
                <label class="ra-section-title" for="feedback"><span>5</span> Teacher Feedback</label>
                <textarea class="form-control" name="feedback" id="feedback" rows="3" maxlength="2000" placeholder="Write your notes and observations here…">{{ old('feedback', $recording->evaluation?->feedback) }}</textarea>
            </div>

            <section aria-labelledby="summary-title">
                <h3 class="ra-section-title" id="summary-title"><span>6</span> Summary</h3>
                <div class="ra-summary" aria-live="polite">
                    <div><small>Oral Reading</small><strong id="summary-oral">—</strong></div>
                    <div><small>Comprehension</small><strong id="summary-comprehension">—</strong></div>
                    <div><small>Observation</small><strong id="summary-observation">—</strong></div>
                    <div><small>Experience</small><strong id="summary-experience">—</strong></div>
                    <div><small>Final Score</small><strong id="summary-final">—</strong></div>
                </div>
                <p class="rk-footnote">Final score averages oral reading and comprehension when questions are provided. Without questions, it uses only the oral reading score. Observation and enjoyment do not affect the score.</p>
            </section>
            <div class="ra-form-actions"><a href="{{ route('teacher.evaluations.index') }}" class="btn btn-outline-primary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back</a><button type="submit" class="btn btn-primary" @disabled($totalWords === 0)><i class="ti ti-device-floppy" aria-hidden="true"></i> Save Evaluation</button></div>
        </form>
    </section>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/reading-assessment.js') }}" defer></script>
@endpush
