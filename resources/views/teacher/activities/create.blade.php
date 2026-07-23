@extends('layouts.teacher')
@section('title', 'Add Activity')
@section('page-title', 'Add New Activity')
@section('page-sub', 'Create a reading activity for your students.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('teacher.activities.store') }}">
@csrf
<div class="row g-3">

    {{-- Left column --}}
    <div class="col-md-8">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-info-circle text-primary"></i> Basic Information</h6>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Activity Title <span class="text-danger">*</span></label>
                <input type="text" name="activity_name" class="form-control form-control-sm"
                       placeholder="e.g. Phonics Level 1 — Letter Sounds"
                       value="{{ old('activity_name') }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2"
                          placeholder="Briefly describe what students will do…">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Activity Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 flex-wrap" id="typeGroup">
                    @foreach(['Phonics','Word Game','Read Aloud','Vocabulary','Word Recognition','Sound Blending'] as $type)
                    <div class="type-option border rounded px-3 py-2 text-center" style="cursor:pointer;min-width:90px;"
                         onclick="selectType('{{ $type }}', this)">
                        <div style="font-size:11px;">{{ $type }}</div>
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="activity_type" id="activity_type" value="{{ old('activity_type') }}" required>
            </div>

            <div class="row">
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Level <span class="text-danger">*</span></label>
                    <select name="level" class="form-select form-select-sm" required>
                        <option value="">Select level</option>
                        <option value="1" {{ old('level')==1?'selected':'' }}>Level 1</option>
                        <option value="2" {{ old('level')==2?'selected':'' }}>Level 2</option>
                        <option value="3" {{ old('level')==3?'selected':'' }}>Level 3</option>
                    </select>
                </div>
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Difficulty <span class="text-danger">*</span></label>
                    <select name="difficulty_level" class="form-select form-select-sm" required>
                        <option value="">Select difficulty</option>
                        <option value="Easy" {{ old('difficulty_level')=='Easy'?'selected':'' }}>Easy</option>
                        <option value="Medium" {{ old('difficulty_level')=='Medium'?'selected':'' }}>Medium</option>
                        <option value="Hard" {{ old('difficulty_level')=='Hard'?'selected':'' }}>Hard</option>
                    </select>
                </div>
                <div class="col-4 mb-3">
                    <label class="form-label small fw-semibold">Duration (min) <span class="text-danger">*</span></label>
                    <input type="number" name="duration_minutes" class="form-control form-control-sm"
                           placeholder="e.g. 15" value="{{ old('duration_minutes') }}" min="1" required>
                </div>
            </div>
        </div>

        <div class="dash-card">
            <h6 class="fw-semibold mb-3"><i class="ti ti-file-text text-primary"></i> Activity Content</h6>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Reading Passage / Instructions</label>
                <textarea name="passage" class="form-control form-control-sm" rows="5"
                          placeholder="Type the reading passage or activity instructions students will see…">{{ old('passage') }}</textarea>
            </div>

           {{-- Battle Mode Toggle --}}
<div class="form-check form-switch mb-2">
    <input class="form-check-input" type="checkbox" name="battle_mode"
           id="battle_mode" onchange="toggleBattleWords()"
           {{ old('battle_mode') ? 'checked' : '' }}>
    <label class="form-check-label small fw-semibold" for="battle_mode">
        ⚔️ Enable Battle Mode (words/paragraphs appear in Battle Arena)
    </label>
</div>

{{-- Battle words/paragraphs --}}
<div id="battle-words-section" style="display:none;">
    <label class="form-label small fw-semibold mt-2">
        ⚔️ Battle Words / Paragraphs
        <span class="text-muted fw-normal">
            (Add multiple — they appear one by one during battle)
        </span>
    </label>

    <div id="battle-words-list">
        <div class="battle-word-item d-flex gap-2 mb-2 align-items-start">
            <div style="flex:1;">
                <textarea name="battle_words[]" class="form-control form-control-sm"
                          rows="2"
                          placeholder="Word, phrase or paragraph students will read..."></textarea>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-word-btn"
                    onclick="removeWord(this)" style="display:none;">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary"
            onclick="addBattleWord()">
        <i class="ti ti-plus"></i> Add Another Word/Paragraph
    </button>

    <div class="alert alert-info small mt-2 py-2" style="font-size:11px;">
        💡 <strong>Tips:</strong>
        Level 1 → Single words (e.g. "cat") |
        Level 2 → Short phrases (e.g. "big red dog") |
        Level 3 → Paragraphs. Add at least 5 items so the battle lasts long enough!
    </div>
</div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-md-4">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-star text-warning"></i> Points Reward</h6>
            <input type="number" name="points_reward" id="points_reward"
                   class="form-control form-control-sm mb-2"
                   placeholder="e.g. 50" value="{{ old('points_reward', 50) }}" min="1" required
                   oninput="document.getElementById('pts_preview').textContent = this.value">
            <div class="p-2 rounded text-center" style="background:#FFFBF0;border:1px solid #FDE68A;">
                <div style="font-size:10px;color:#92400E;">Students will earn</div>
                <div style="font-size:22px;font-weight:700;color:#92400E;">
                    ⭐ <span id="pts_preview">{{ old('points_reward', 50) }}</span> pts
                </div>
            </div>
        </div>

        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3"><i class="ti ti-settings text-primary"></i> Settings</h6>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published"
                       {{ old('is_published') ? 'checked' : '' }}>
                <label class="form-check-label small" for="is_published">Publish immediately</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="allow_reattempt" id="allow_reattempt"
                       {{ old('allow_reattempt', true) ? 'checked' : '' }}>
                <label class="form-check-label small" for="allow_reattempt">Allow re-attempts</label>
            </div>
            <div class="form-check form-switch mb-2">
    <input class="form-check-input" type="checkbox" name="battle_mode"
           id="battle_mode_right"
           {{ old('battle_mode') ? 'checked' : '' }}>
    <label class="form-check-label small" for="battle_mode_right">
        ⚔️ Battle mode
    </label>
</div>
            
        </div>

        <div class="dash-card" style="background:#F0FDF4;border-color:#BBF7D0;">
            <h6 class="fw-semibold mb-2" style="color:#166534;">
                <i class="ti ti-checklist"></i> Before you publish
            </h6>
            <ul class="list-unstyled mb-0" style="font-size:12px;color:#166534;">
                <li>✅ Activity title</li>
                <li>✅ Activity type</li>
                <li>✅ Level & difficulty</li>
                <li class="text-muted">⬜ Passage / content</li>
                <li class="text-muted">⬜ Points reward</li>
            </ul>
        </div>
    </div>
</div>

{{-- Action bar --}}
<div class="d-flex justify-content-between align-items-center mt-3 p-3 bg-white rounded border">
    <a href="{{ route('teacher.activities.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-x"></i> Cancel
    </a>
    <div class="d-flex gap-2">
        <button type="submit" name="is_published" value="0" class="btn btn-sm btn-outline-primary">
            <i class="ti ti-device-floppy"></i> Save as Draft
        </button>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="ti ti-send"></i> Publish Activity
        </button>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>

//========================
// Activity Type
//========================
function selectType(type, el) {

    document.querySelectorAll('.type-option').forEach(o => {
        o.style.background = '';
        o.style.borderColor = '';
        o.style.color = '';
    });

    el.style.background = '#DBEAFE';
    el.style.borderColor = '#185FA5';
    el.style.color = '#1E40AF';

    document.getElementById('activity_type').value = type;
}

//========================
// Battle Mode
//========================
function toggleBattleWords() {

    const checked = document.getElementById('battle_mode').checked;

    document.getElementById('battle-words-section').style.display =
        checked ? 'block' : 'none';

    // Sync right switch
    const right = document.getElementById('battle_mode_right');

    if(right){
        right.checked = checked;
    }

}

document.getElementById('battle_mode_right')?.addEventListener('change', function(){

    document.getElementById('battle_mode').checked = this.checked;

    toggleBattleWords();

});

function addBattleWord() {

    const list = document.getElementById('battle-words-list');

    const div = document.createElement('div');

    div.className = 'battle-word-item d-flex gap-2 mb-2 align-items-start';

    div.innerHTML = `
        <div style="flex:1;">
            <textarea
                name="battle_words[]"
                class="form-control form-control-sm"
                rows="2"
                placeholder="Word, phrase or paragraph students will read..."></textarea>
        </div>

        <button
            type="button"
            class="btn btn-sm btn-outline-danger remove-word-btn"
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

        const removeBtn = item.querySelector('.remove-word-btn');

        if(removeBtn){

            removeBtn.style.display = items.length > 1 ? 'block' : 'none';

        }

    });

}

//========================
// Page Load
//========================
document.addEventListener('DOMContentLoaded', function(){

    // Restore activity type
    const oldType = document.getElementById('activity_type').value;

    if(oldType){

        document.querySelectorAll('.type-option').forEach(o => {

            if(o.textContent.trim() === oldType){

                selectType(oldType, o);

            }

        });

    }

    // Restore battle mode
    toggleBattleWords();

    updateRemoveButtons();

});
</script>
@endpush