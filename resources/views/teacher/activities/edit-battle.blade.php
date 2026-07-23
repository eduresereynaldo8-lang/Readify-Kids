@extends('layouts.teacher')
@section('title', 'Edit Battle Activity')
@section('page-title', 'Edit Battle Activity')
@section('page-sub', 'Update battle words and settings.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('teacher.activities.update.battle', $activity->id) }}">
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
            <h6 class="fw-semibold mb-3"><i class="ti ti-sword text-primary"></i> ⚔️ Battle Words / Paragraphs</h6>
            <div id="battle-words-list">
                @forelse($activity->wordBank->sortBy('order') as $wb)
                <div class="battle-word-item d-flex gap-2 mb-2 align-items-start">
                    <div style="flex:1;">
                        <textarea name="battle_words[]" class="form-control form-control-sm" rows="2">{{ $wb->word }}</textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-word-btn"
                            onclick="removeWord(this)">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                @empty
                <div class="battle-word-item d-flex gap-2 mb-2 align-items-start">
                    <div style="flex:1;">
                        <textarea name="battle_words[]" class="form-control form-control-sm" rows="2"
                                  placeholder="Word, phrase or paragraph..."></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-word-btn"
                            onclick="removeWord(this)" style="display:none;">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
                @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addBattleWord()">
                <i class="ti ti-plus"></i> Add Another
            </button>
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

@push('scripts')
<script>
function addBattleWord() {
    const list = document.getElementById('battle-words-list');
    const div = document.createElement('div');
    div.className = 'battle-word-item d-flex gap-2 mb-2 align-items-start';
    div.innerHTML = `
        <div style="flex:1;">
            <textarea name="battle_words[]" class="form-control form-control-sm" rows="2"
                      placeholder="Word, phrase or paragraph..."></textarea>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-word-btn"
                onclick="removeWord(this)">
            <i class="ti ti-trash"></i>
        </button>
    `;
    list.appendChild(div);
    updateRemoveButtons();
}

function removeWord(btn) {
    btn.closest('.battle-word-item').remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.battle-word-item');
    items.forEach(item => {
        const btn = item.querySelector('.remove-word-btn');
        if (btn) btn.style.display = items.length > 1 ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateRemoveButtons();
});
</script>
@endpush
