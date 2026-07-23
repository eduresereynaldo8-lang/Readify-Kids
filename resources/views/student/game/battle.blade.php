@extends('layouts.student')
@section('title', 'Battle!')
@section('page-greet', '⚔️ Battle in progress!')
@section('page-sub', 'Read the word aloud to attack the enemy!')

@section('content')

<div class="row g-3">

    {{-- LEFT: Battle arena --}}
    <div class="col-md-8">

        {{-- Enemy display --}}
        <div id="enemy-arena"
             style="background:linear-gradient(135deg,#1E1B4B,#312E81);border-radius:16px;
                    padding:20px;margin-bottom:14px;position:relative;overflow:hidden;">

            {{-- Stars background decoration --}}
            <div style="position:absolute;inset:0;opacity:0.15;font-size:10px;
                        color:#fff;pointer-events:none;overflow:hidden;">
                @for($i = 0; $i < 30; $i++)
                <span style="position:absolute;
                             top:{{ rand(0,100) }}%;
                             left:{{ rand(0,100) }}%;">✦</span>
                @endfor
            </div>

            {{-- Round & session info --}}
            <div style="display:flex;justify-content:space-between;
                        align-items:center;margin-bottom:16px;position:relative;">
                <div style="background:rgba(255,255,255,0.15);border-radius:20px;
                            padding:4px 12px;font-size:11px;color:#fff;">
                    ⚔️ Round <span id="round-num">{{ $session->rounds_played + 1 }}</span>
                </div>
                <div style="background:rgba(255,255,255,0.15);border-radius:20px;
                            padding:4px 12px;font-size:11px;color:#fff;">
                    💥 Total damage: <span id="total-dmg">{{ number_format($session->total_damage) }}</span>
                </div>
            </div>

            {{-- Enemy name + HP --}}
            <div style="position:relative;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;
                            align-items:center;margin-bottom:6px;">
                    <span style="font-size:14px;font-weight:700;color:#fff;">
                        {{ $session->enemy->name }}
                    </span>
                    <span style="font-size:12px;font-weight:700;color:#FCA5A5;" id="hp-display">
                        ❤️ <span id="hp-current">{{ number_format($session->enemy_current_hp) }}</span>
                        / {{ number_format($session->enemy_max_hp) }}
                    </span>
                </div>

                {{-- HP bar --}}
                <div style="background:rgba(255,255,255,0.2);border-radius:8px;height:16px;
                            overflow:hidden;">
                    <div id="hp-bar"
                         style="height:16px;border-radius:8px;transition:width 1s ease;
                                background:linear-gradient(90deg,#EF4444,#FCA5A5);
                                width:{{ $hpPercent }}%;position:relative;">
                        <div style="position:absolute;inset:0;display:flex;align-items:center;
                                    justify-content:center;font-size:10px;font-weight:700;color:#fff;">
                            {{ $hpPercent }}%
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enemy sprite --}}
            <div style="text-align:center;position:relative;">
                <div id="enemy-sprite"
                     style="font-size:100px;line-height:1;display:inline-block;
                            transition:transform 0.3s,filter 0.3s;
                            filter:drop-shadow(0 0 20px rgba(255,255,255,0.3));">
                    {{ $session->enemy->sprite }}
                </div>

                {{-- Damage popup --}}
                <div id="damage-popup"
                     style="display:none;position:absolute;top:0;left:50%;
                            transform:translateX(-50%);
                            font-size:32px;font-weight:900;color:#FBBF24;
                            text-shadow:2px 2px 8px rgba(0,0,0,0.8);
                            pointer-events:none;white-space:nowrap;">
                </div>

                {{-- Score popup --}}
                <div id="score-popup"
                     style="display:none;position:absolute;bottom:0;right:10%;
                            font-size:14px;font-weight:700;
                            background:rgba(0,0,0,0.6);color:#4ADE80;
                            padding:4px 12px;border-radius:20px;
                            pointer-events:none;">
                </div>
            </div>

            {{-- Battle status message --}}
            <div id="battle-msg"
                 style="margin-top:14px;font-size:13px;color:rgba(255,255,255,0.9);
                        background:rgba(255,255,255,0.1);border-radius:10px;
                        padding:10px 14px;text-align:center;min-height:42px;
                        display:flex;align-items:center;justify-content:center;
                        flex-direction:column;gap:4px;">
                🎙️ Read the word below clearly and press Record to attack!
            </div>
        </div>

        {{-- Word progress dots --}}
        <div style="display:flex;align-items:center;justify-content:space-between;
                    background:#fff;border:1px solid #E5E7EB;border-radius:10px;
                    padding:10px 16px;margin-bottom:12px;">
            <span style="font-size:11px;color:#6B7280;">
                📖 Word <span id="word-index">{{ $roundIndex + 1 }}</span> of {{ $totalWords }}
            </span>
            <div style="display:flex;gap:5px;align-items:center;">
                @foreach($allWords as $i => $word)
                <div id="dot-{{ $i }}"
                     style="width:12px;height:12px;border-radius:50%;transition:all 0.3s;
                            background:{{ $i < $roundIndex ? '#22C55E' : ($i == $roundIndex ? '#7C3AED' : '#E5E7EB') }};
                            {{ $i == $roundIndex ? 'box-shadow:0 0 0 3px rgba(124,58,237,0.25);' : '' }}">
                </div>
                @endforeach
            </div>
            <span style="font-size:11px;color:#6B7280;">
                {{ $session->rounds_played }} rounds played
            </span>
        </div>

        {{-- Current word display --}}
        <div id="word-card"
             style="background:#fff;border:2px solid #7C3AED;border-radius:14px;
                    padding:24px;text-align:center;margin-bottom:14px;
                    box-shadow:0 4px 20px rgba(124,58,237,0.15);">
            <div style="font-size:11px;color:#7C3AED;font-weight:700;
                        text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px;">
                📖 Read this aloud:
            </div>
            <div id="current-word"
                 style="font-size:{{ $session->activity->level == 3 ? '18' : '32' }}px;
                        font-weight:700;color:#1E1B4B;line-height:1.5;
                        animation:wordPumpIn 0.5s ease;">
                {{ $currentWord }}
            </div>
        </div>

        {{-- Recording section --}}
        <div style="background:#fff;border:1px solid #E5E7EB;border-radius:14px;padding:16px;">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">
                🎙️ Your Attack
            </div>

            {{-- Waveform --}}
            <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;
                        padding:14px;display:flex;flex-direction:column;
                        align-items:center;gap:10px;margin-bottom:14px;">
                <div id="waveform"
                     style="display:flex;align-items:center;gap:3px;height:44px;">
                    @for($i = 0; $i < 28; $i++)
                    <div class="wv"
                         style="width:5px;border-radius:3px;background:#E5E7EB;
                                height:8px;transition:height 0.08s;"></div>
                    @endfor
                </div>
                <div id="rec-timer"
                     style="font-size:26px;font-weight:700;color:#111827;
                            font-variant-numeric:tabular-nums;">
                    0:00
                </div>
                <div id="rec-status" style="font-size:12px;color:#9CA3AF;">
                    Press the button below to start recording
                </div>
            </div>

            {{-- Record button --}}
            <div class="text-center mb-3">
                <button id="rec-btn" type="button"
                        style="width:80px;height:80px;border-radius:50%;
                               background:#7C3AED;border:none;cursor:pointer;
                               box-shadow:0 0 0 12px rgba(124,58,237,0.15);
                               display:flex;align-items:center;
                               justify-content:center;margin:0 auto;
                               transition:all 0.2s;">
                    <i class="ti ti-microphone"
                       style="color:#fff;font-size:34px;"></i>
                </button>
                <div style="font-size:11px;color:#9CA3AF;margin-top:10px;"
                     id="rec-btn-label">
                    Tap to record your attack!
                </div>
            </div>

            {{-- Playback --}}
            <div id="playback-section" style="display:none;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">
                    🎧 Your recording — listen before attacking:
                </div>
                <audio id="playback-audio" controls
                       style="width:100%;border-radius:8px;"></audio>
            </div>

            {{-- Action buttons --}}
            <div class="d-flex gap-2 justify-content-center">
                <button id="re-record-btn" style="display:none;"
                        class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-refresh"></i> Re-record
                </button>
                <button id="attack-btn" style="display:none;background:linear-gradient(135deg,#7C3AED,#4F46E5);
                        color:#fff;font-weight:700;padding:10px 28px;
                        border-radius:10px;border:none;font-size:14px;cursor:pointer;">
                    ⚔️ Attack!
                </button>
            </div>

            {{-- Loading state --}}
            <div id="loading-state"
                 style="display:none;text-align:center;padding:20px;">
                <div style="display:flex;align-items:center;justify-content:center;gap:10px;">
                    <div class="spinner-border text-primary"
                         style="width:22px;height:22px;"></div>
                    <div style="font-size:13px;color:#6B7280;">
                        🤖 Analyzing your reading with AI...
                    </div>
                </div>
                <div style="font-size:11px;color:#9CA3AF;margin-top:6px;">
                    This may take a few seconds
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Stats + Round history --}}
    <div class="col-md-4">

        {{-- Battle stats --}}
        <div style="background:#F5F3FF;border:1px solid #DDD6FE;
                    border-radius:14px;padding:14px;margin-bottom:12px;">
            <div style="font-size:13px;font-weight:700;color:#5B21B6;margin-bottom:12px;">
                ⚔️ Battle Stats
            </div>
            <div class="d-flex flex-column gap-2">
                <div style="display:flex;justify-content:space-between;
                            align-items:center;font-size:12px;">
                    <span style="color:#6B7280;">Enemy HP</span>
                    <strong style="color:#EF4444;" id="stat-hp">
                        {{ number_format($session->enemy_current_hp) }} / {{ number_format($session->enemy_max_hp) }}
                    </strong>
                </div>
                <div style="display:flex;justify-content:space-between;
                            align-items:center;font-size:12px;">
                    <span style="color:#6B7280;">Total damage dealt</span>
                    <strong style="color:#7C3AED;" id="stat-damage">
                        {{ number_format($session->total_damage) }}
                    </strong>
                </div>
                <div style="display:flex;justify-content:space-between;
                            align-items:center;font-size:12px;">
                    <span style="color:#6B7280;">Rounds played</span>
                    <strong id="stat-rounds">{{ $session->rounds_played }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;
                            align-items:center;font-size:12px;">
                    <span style="color:#6B7280;">Points to earn</span>
                    <strong style="color:#F59E0B;">
                        ⭐ {{ $session->activity->points_reward }}
                    </strong>
                </div>
            </div>
        </div>

        {{-- Tips --}}
        <div style="background:#FFFBF0;border:1px solid #FDE68A;
                    border-radius:14px;padding:14px;margin-bottom:12px;">
            <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:10px;">
                💡 Tips
            </div>
            <div class="d-flex flex-column gap-2">
                @foreach([
                    ['🐢','Read slowly and clearly'],
                    ['🔊','Speak loud enough'],
                    ['👁️','Read every word carefully'],
                    ['🔁','Re-record if you made a mistake'],
                ] as [$icon,$tip])
                <div style="display:flex;gap:7px;align-items:flex-start;
                            font-size:11px;color:#92400E;">
                    <span>{{ $icon }}</span><span>{{ $tip }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Round history --}}
        <div style="background:#fff;border:1px solid #E5E7EB;
                    border-radius:14px;padding:14px;">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:10px;">
                📋 Round History
            </div>
            <div id="round-history"
                 style="max-height:320px;overflow-y:auto;
                        display:flex;flex-direction:column;gap:6px;">
                @forelse($session->rounds->sortByDesc('created_at') as $round)
                <div style="background:#F9FAFB;border:1px solid #E5E7EB;
                            border-radius:8px;padding:8px 10px;font-size:11px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                        <span style="color:#6B7280;font-weight:600;">
                            "{{ Str::limit($round->word_or_passage, 18) }}"
                        </span>
                        <span style="color:#EF4444;font-weight:700;">
                            -{{ $round->damage_dealt }} HP 💥
                        </span>
                    </div>
                    @if($round->ml_score !== null)
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                        <span style="background:#EDE9FE;color:#5B21B6;
                                     padding:1px 7px;border-radius:10px;font-weight:600;">
                            Score: {{ $round->ml_score }}%
                        </span>
                        @if($round->final_score >= 90)
                            <span style="color:#22C55E;">🔥 Excellent!</span>
                        @elseif($round->final_score >= 70)
                            <span style="color:#3B82F6;">⚔️ Good!</span>
                        @elseif($round->final_score >= 50)
                            <span style="color:#F59E0B;">👍 OK</span>
                        @else
                            <span style="color:#EF4444;">💪 Try harder!</span>
                        @endif
                    </div>
                    @else
                    <span style="color:#9CA3AF;">Pending ML score...</span>
                    @endif
                </div>
                @empty
                <div style="text-align:center;color:#9CA3AF;font-size:12px;padding:16px;">
                    No rounds yet. Start attacking! ⚔️
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Win overlay --}}
<div id="win-overlay"
     style="display:none;position:fixed;inset:0;
            background:rgba(0,0,0,0.75);z-index:9999;
            align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;padding:40px 32px;
                text-align:center;max-width:420px;width:90%;
                animation:popIn 0.4s ease;">
        <div style="font-size:80px;margin-bottom:10px;">🏆</div>
        <div style="font-size:24px;font-weight:700;color:#111827;margin-bottom:6px;">
            Victory!
        </div>
        <div style="font-size:14px;color:#6B7280;margin-bottom:6px;"
             id="win-message"></div>
        <div style="font-size:13px;color:#6B7280;margin-bottom:6px;"
             id="win-transcript"></div>
        <div style="font-size:32px;font-weight:700;color:#F59E0B;margin-bottom:24px;"
             id="win-points"></div>
        <a href="{{ route('student.game.index') }}"
           style="display:inline-block;padding:12px 32px;
                  background:linear-gradient(135deg,#7C3AED,#4F46E5);
                  color:#fff;border-radius:12px;font-size:14px;
                  font-weight:700;text-decoration:none;">
            🏰 Back to Arena
        </a>
    </div>
</div>

{{-- Hidden inputs --}}
<input type="hidden" id="session-id" value="{{ $session->id }}">
<input type="hidden" id="current-word-value" value="{{ $currentWord }}">
<input type="hidden" id="current-round-index" value="{{ $roundIndex }}">
<input type="hidden" id="total-words" value="{{ $totalWords }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@push('scripts')
<script>
// ── State ─────────────────────────────────────────────────────
let mediaRecorder;
let audioChunks  = [];
let isRecording  = false;
let timerInterval;
let seconds      = 0;
let audioBlob    = null;
let waveInterval = null;

// ── DOM refs ──────────────────────────────────────────────────
const recBtn        = document.getElementById('rec-btn');
const recStatus     = document.getElementById('rec-status');
const recTimer      = document.getElementById('rec-timer');
const recBtnLabel   = document.getElementById('rec-btn-label');
const playbackSec   = document.getElementById('playback-section');
const playbackAudio = document.getElementById('playback-audio');
const reRecordBtn   = document.getElementById('re-record-btn');
const attackBtn     = document.getElementById('attack-btn');
const loadingState  = document.getElementById('loading-state');
const wvBars        = document.querySelectorAll('.wv');
const sessionId     = document.getElementById('session-id').value;
const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;

// ── Record button ─────────────────────────────────────────────
recBtn.addEventListener('click', async () => {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks   = [];

            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const url = URL.createObjectURL(audioBlob);
                playbackAudio.src = url;
                playbackSec.style.display   = 'block';
                reRecordBtn.style.display   = 'inline-flex';
                attackBtn.style.display     = 'inline-flex';
                stopWaveform();
            };

            mediaRecorder.start(100);
            isRecording = true;

            // UI update
            recBtn.style.background = '#EF4444';
            recBtn.style.boxShadow  = '0 0 0 12px rgba(239,68,68,0.2)';
            recBtn.innerHTML = '<i class="ti ti-player-stop" style="color:#fff;font-size:30px;"></i>';
            recBtnLabel.textContent   = 'Tap to stop recording';
            recStatus.textContent     = '🔴 Recording your attack...';
            playbackSec.style.display = 'none';
            reRecordBtn.style.display = 'none';
            attackBtn.style.display   = 'none';

            // Start timer
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                recTimer.textContent = `${m}:${s}`;
            }, 1000);

            animateWaveform();

        } catch(err) {
            alert('Microphone access denied! Please allow microphone access to play.');
        }

    } else {
        // Stop recording
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop());
        isRecording = false;
        clearInterval(timerInterval);

        recBtn.style.background = '#7C3AED';
        recBtn.style.boxShadow  = '0 0 0 12px rgba(124,58,237,0.15)';
        recBtn.innerHTML = '<i class="ti ti-microphone" style="color:#fff;font-size:34px;"></i>';
        recBtnLabel.textContent = 'Tap to record again';
        recStatus.textContent   = '✅ Done! Listen back or press Attack!';
    }
});

// ── Re-record ─────────────────────────────────────────────────
reRecordBtn.addEventListener('click', () => {
    audioBlob = null;
    playbackSec.style.display = 'none';
    reRecordBtn.style.display = 'none';
    attackBtn.style.display   = 'none';
    recTimer.textContent      = '0:00';
    recStatus.textContent     = 'Press the button to start recording';
    recBtnLabel.textContent   = 'Tap to record your attack!';
    recBtn.innerHTML = '<i class="ti ti-microphone" style="color:#fff;font-size:34px;"></i>';
    recBtn.style.background = '#7C3AED';
    recBtn.style.boxShadow  = '0 0 0 12px rgba(124,58,237,0.15)';
    stopWaveform();
});

// ── Attack button ─────────────────────────────────────────────
attackBtn.addEventListener('click', async () => {
    if (!audioBlob) { alert('Please record first!'); return; }

    // Disable buttons and show loading
    attackBtn.style.display    = 'none';
    reRecordBtn.style.display  = 'none';
    loadingState.style.display = 'block';

    const word     = document.getElementById('current-word-value').value;
    const formData = new FormData();
    formData.append('recording', new File([audioBlob], 'attack.webm', { type: 'audio/webm' }));
    formData.append('word_or_passage', word);
    formData.append('_token', csrfToken);

    try {
        const res  = await fetch(`/student/game/battle/${sessionId}/round`, {
            method: 'POST',
            body  : formData,
        });

        if (!res.ok) {
            const err = await res.text();
            throw new Error(`Server error ${res.status}: ${err}`);
        }

        const data = await res.json();
        loadingState.style.display = 'none';

        if (data.status === 'won') {
            // Show damage then win screen
            showDamagePopup(data.damage, data.ml_score);
            updateHpBar(0, 0);

            setTimeout(() => {
                showWinOverlay(data);
            }, 1500);

        } else if (data.status === 'ongoing') {
            // Show results
            showDamagePopup(data.damage, data.ml_score);
            updateHpBar(data.enemy_hp, data.hp_percent);
            updateBattleStats(data);
            addRoundToHistory(data, word);
            updateBattleMessage(data);

            // Move to next word after delay
            setTimeout(() => {
                moveToNextRound();
            }, 2000);

        } else {
            // ML pending
            document.getElementById('battle-msg').innerHTML =
                '⏳ Recording submitted! Waiting for AI scoring...';
            setTimeout(() => resetRecorder(), 1500);
        }

    } catch(err) {
        loadingState.style.display = 'none';
        console.error('Attack error:', err);
        document.getElementById('battle-msg').textContent =
            '❌ Something went wrong. Please try again!';
        setTimeout(() => resetRecorder(), 2000);
    }
});

// ── UI update functions ───────────────────────────────────────
function showDamagePopup(damage, score) {
    const popup  = document.getElementById('damage-popup');
    const sprite = document.getElementById('enemy-sprite');
    const scorePopup = document.getElementById('score-popup');

    // Damage number
    popup.textContent    = `-${damage} 💥`;
    popup.style.display  = 'block';
    popup.style.opacity  = '1';
    popup.style.transform= 'translateX(-50%) translateY(0)';
    popup.style.transition = 'all 1.2s ease';

    // Score badge
    if (score !== null) {
        const color = score >= 90 ? '#4ADE80' : score >= 70 ? '#60A5FA' : score >= 50 ? '#FBBF24' : '#F87171';
        scorePopup.textContent   = `${score}% 🎯`;
        scorePopup.style.color   = color;
        scorePopup.style.display = 'block';
    }

    // Shake enemy
    sprite.style.transform = 'scale(1.15) rotate(-8deg)';
    sprite.style.filter    = 'brightness(3) saturate(0)';

    setTimeout(() => {
        popup.style.opacity   = '0';
        popup.style.transform = 'translateX(-50%) translateY(-50px)';
        sprite.style.transform= '';
        sprite.style.filter   = '';
    }, 800);

    setTimeout(() => {
        popup.style.display      = 'none';
        popup.style.opacity      = '1';
        popup.style.transform    = 'translateX(-50%) translateY(0)';
        popup.style.transition   = '';
        scorePopup.style.display = 'none';
    }, 1300);
}

function updateHpBar(newHp, hpPercent) {
    const bar = document.getElementById('hp-bar');
    bar.style.width = hpPercent + '%';
    bar.querySelector('div').textContent = hpPercent + '%';
    document.getElementById('hp-current').textContent = newHp.toLocaleString();

    // Color based on HP
    if (hpPercent <= 25) {
        bar.style.background = '#EF4444';
    } else if (hpPercent <= 50) {
        bar.style.background = 'linear-gradient(90deg,#F59E0B,#FCD34D)';
    } else {
        bar.style.background = 'linear-gradient(90deg,#EF4444,#FCA5A5)';
    }

    document.getElementById('stat-hp').textContent =
        newHp.toLocaleString() + ' / {{ $session->enemy_max_hp }}';
}

function updateBattleStats(data) {
    const rounds = parseInt(document.getElementById('stat-rounds').textContent) + 1;
    const oldDmg = parseInt(document.getElementById('stat-damage').textContent.replace(/,/g, ''));
    document.getElementById('stat-rounds').textContent  = rounds;
    document.getElementById('stat-damage').textContent  = (oldDmg + data.damage).toLocaleString();
    document.getElementById('total-dmg').textContent    = (oldDmg + data.damage).toLocaleString();
    document.getElementById('round-num').textContent    = rounds + 1;
}

function updateBattleMessage(data) {
    const msg = document.getElementById('battle-msg');
    let html  = `<span>${data.message}</span>`;
    if (data.transcript) {
        html += `<span style="font-size:11px;color:rgba(255,255,255,0.7);">
                    🎙️ AI heard: "<em>${data.transcript}</em>"
                 </span>`;
    }
    msg.innerHTML = html;
}

function addRoundToHistory(data, word) {
    const history = document.getElementById('round-history');
    const noRounds = history.querySelector('[style*="No rounds"]');
    if (noRounds) noRounds.remove();

    const scoreColor = data.ml_score >= 90 ? '#22C55E'
                     : data.ml_score >= 70 ? '#3B82F6'
                     : data.ml_score >= 50 ? '#F59E0B' : '#EF4444';

    const rating = data.ml_score >= 90 ? '🔥 Excellent!'
                 : data.ml_score >= 70 ? '⚔️ Good!'
                 : data.ml_score >= 50 ? '👍 OK' : '💪 Try harder!';

    const div = document.createElement('div');
    div.style.cssText = 'background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:8px 10px;font-size:11px;';
    div.innerHTML = `
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="color:#6B7280;font-weight:600;">"${word.substring(0,18)}${word.length>18?'...':''}"</span>
            <span style="color:#EF4444;font-weight:700;">-${data.damage} HP 💥</span>
        </div>
        ${data.transcript ? `<div style="color:#9CA3AF;margin-bottom:4px;">🎙️ "<em>${data.transcript}</em>"</div>` : ''}
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <span style="background:#EDE9FE;color:#5B21B6;padding:1px 7px;border-radius:10px;font-weight:600;">
                Score: ${data.ml_score !== null ? data.ml_score + '%' : '—'}
            </span>
            <span style="color:${scoreColor};">${rating}</span>
        </div>
    `;
    history.insertBefore(div, history.firstChild);
}

function moveToNextRound() {
    // Update word progress dots
    const currentIndex = parseInt(document.getElementById('current-round-index').value);
    const totalWords   = parseInt(document.getElementById('total-words').value);
    const nextIndex    = (currentIndex + 1) % totalWords;

    // Update current dot
    const currentDot = document.getElementById(`dot-${currentIndex}`);
    if (currentDot) {
        currentDot.style.background = '#22C55E';
        currentDot.style.boxShadow  = 'none';
    }

    // Reload page to get next word from server
    window.location.reload();
}

function showWinOverlay(data) {
    document.getElementById('win-message').textContent  = data.message;
    document.getElementById('win-points').textContent   = `+${data.points} ⭐ Points earned!`;
    if (data.transcript) {
        document.getElementById('win-transcript').textContent =
            `🎙️ Last read: "${data.transcript}"`;
    }
    const overlay = document.getElementById('win-overlay');
    overlay.style.display    = 'flex';
    overlay.style.animation  = 'fadeIn 0.3s ease';
}

function resetRecorder() {
    audioBlob = null;
    playbackSec.style.display = 'none';
    reRecordBtn.style.display = 'none';
    attackBtn.style.display   = 'none';
    recTimer.textContent      = '0:00';
    recStatus.textContent     = 'Press the button to start recording';
    recBtnLabel.textContent   = 'Tap to record your attack!';
    recBtn.innerHTML = '<i class="ti ti-microphone" style="color:#fff;font-size:34px;"></i>';
    recBtn.style.background = '#7C3AED';
    recBtn.style.boxShadow  = '0 0 0 12px rgba(124,58,237,0.15)';
    stopWaveform();
}

// ── Waveform animation ────────────────────────────────────────
function animateWaveform() {
    let t = 0;
    waveInterval = setInterval(() => {
        wvBars.forEach((bar, i) => {
            const h = Math.abs(Math.sin((t + i) * 0.35)) * 36 + 6;
            bar.style.height     = h + 'px';
            bar.style.background = '#7C3AED';
        });
        t++;
    }, 80);
}

function stopWaveform() {
    if (waveInterval) clearInterval(waveInterval);
    wvBars.forEach(bar => {
        bar.style.height     = '8px';
        bar.style.background = '#E5E7EB';
    });
}
</script>

<style>
@keyframes wordPumpIn {
    0%   { opacity: 0; transform: scale(0.7) translateY(20px); }
    70%  { transform: scale(1.05) translateY(-4px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes popIn {
    0%   { transform: scale(0.5); opacity: 0; }
    70%  { transform: scale(1.05); }
    100% { transform: scale(1);    opacity: 1; }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
</style>
@endpush