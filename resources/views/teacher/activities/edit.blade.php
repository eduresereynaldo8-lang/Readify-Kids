@extends('layouts.teacher')
@section('title', 'Edit Activity')
@section('page-title', 'Edit Activity')
@section('page-sub', 'Update activity details.')

@section('content')

@if($errors->any())
<div class="alert alert-danger small mb-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('teacher.activities.update', $activity->id) }}">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-8">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3">Basic Information</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Activity Title <span class="text-danger">*</span></label>
                <input type="text" name="activity_name" class="form-control form-control-sm"
                       value="{{ old('activity_name', $activity->activity_name) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Description</label>
                <textarea name="description" class="form-control form-control-sm" rows="2">{{ old('description', $activity->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Activity Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 flex-wrap" id="typeGroup">
                    @foreach(['Phonics','Word Game','Read Aloud','Vocabulary','Word Recognition','Sound Blending'] as $type)
                    <div class="type-option border rounded px-3 py-2 text-center"
                         style="cursor:pointer;min-width:90px;{{ $activity->activity_type == $type ? 'background:#DBEAFE;border-color:#185FA5;color:#1E40AF;' : '' }}"
                         onclick="selectType('{{ $type }}', this)">
                        <div style="font-size:11px;">{{ $type }}</div>
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="activity_type" id="activity_type" value="{{ old('activity_type', $activity->activity_type) }}" required>
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
            <h6 class="fw-semibold mb-3">Activity Content</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Reading Passage / Instructions</label>
                <textarea name="passage" class="form-control form-control-sm" rows="5"
                          placeholder="Reading passage or instructions…">{{ old('passage') }}</textarea>
            </div>
            {{-- Battle Mode Toggle --}}
<div class="form-check form-switch mb-2 mt-2">
    <input class="form-check-input"
           type="checkbox"
           name="battle_mode"
           id="battle_mode"
           onchange="toggleBattleWords()"
           {{ $activity->battle_mode ? 'checked' : '' }}>

    <label class="form-check-label small fw-semibold" for="battle_mode">
        ⚔️ Enable Battle Mode
    </label>
</div>

<div id="battle-words-section"
     style="display:{{ $activity->battle_mode ? 'block' : 'none' }};">

    <label class="form-label small fw-semibold mt-2">
        ⚔️ Battle Words / Paragraphs
    </label>

    <div id="battle-words-list">

        @forelse($activity->wordBank->sortBy('order') as $wb)

        <div class="battle-word-item d-flex gap-2 mb-2 align-items-start">

            <div style="flex:1;">
                <textarea
                    name="battle_words[]"
                    class="form-control form-control-sm"
                    rows="2">{{ $wb->word }}</textarea>
            </div>

            <button
                type="button"
                class="btn btn-sm btn-outline-danger remove-word-btn"
                onclick="removeWord(this)">
                <i class="ti ti-trash"></i>
            </button>

        </div>

        @empty

        <div class="battle-word-item d-flex gap-2 mb-2 align-items-start">

            <div style="flex:1;">
                <textarea
                    name="battle_words[]"
                    class="form-control form-control-sm"
                    rows="2"
                    placeholder="Word, phrase or paragraph..."></textarea>
            </div>

            <button
                type="button"
                class="btn btn-sm btn-outline-danger remove-word-btn"
                onclick="removeWord(this)"
                style="display:none;">
                <i class="ti ti-trash"></i>
            </button>

        </div>

        @endforelse

    </div>

    <button
        type="button"
        class="btn btn-sm btn-outline-primary mt-2"
        onclick="addBattleWord()">

        <i class="ti ti-plus"></i>

        Add Another

    </button>

</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3">Points Reward</h6>
            <input type="number" name="points_reward" class="form-control form-control-sm mb-2"
                   value="{{ old('points_reward', $activity->points_reward) }}" min="1" required>
        </div>
        <div class="dash-card mb-3">
            <h6 class="fw-semibold mb-3">Settings</h6>
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

function selectType(type, el){

    document.querySelectorAll('.type-option').forEach(o=>{

        o.style.background='';
        o.style.borderColor='';
        o.style.color='';

    });

    el.style.background='#DBEAFE';
    el.style.borderColor='#185FA5';
    el.style.color='#1E40AF';

    document.getElementById('activity_type').value=type;

}

//==================================
// Battle Mode
//==================================

function toggleBattleWords(){

    const checked=document.getElementById('battle_mode').checked;

    document.getElementById('battle-words-section').style.display=
        checked ? 'block' : 'none';

}

function addBattleWord(){

    const list=document.getElementById('battle-words-list');

    const div=document.createElement('div');

    div.className='battle-word-item d-flex gap-2 mb-2 align-items-start';

    div.innerHTML=`
        <div style="flex:1;">
            <textarea
                name="battle_words[]"
                class="form-control form-control-sm"
                rows="2"
                placeholder="Word, phrase or paragraph..."></textarea>
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

function removeWord(btn){

    btn.closest('.battle-word-item').remove();

    updateRemoveButtons();

}

function updateRemoveButtons(){

    const items=document.querySelectorAll('.battle-word-item');

    items.forEach(item=>{

        const btn=item.querySelector('.remove-word-btn');

        if(btn){

            btn.style.display=items.length>1?'block':'none';

        }

    });

}

document.addEventListener('DOMContentLoaded',function(){

    updateRemoveButtons();

});

</script>
@endpush