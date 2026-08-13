<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Read Aloud — Readify Kids</title>
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    :root{
        --sky-top:#5FC0FF; --sky-mid:#8FD8FF; --sky-bottom:#FFDD8A;
        --ground:#7BC96F; --ground-dark:#5AA652;
        --panel:#3B2E63; --panel-light:#5B4696;
        --gold:#FFC93C; --gold-dark:#E0A11B;
        --ink:#2B2140; --cream:#FFF7E6; --pink:#FF6FA5; --purple:#7C3AED;
    }
    * { box-sizing:border-box; margin:0; padding:0; }
    html,body{ height:100%; }
    body{
        font-family:'Nunito',sans-serif;
        background:linear-gradient(180deg,var(--sky-top) 0%,var(--sky-mid) 40%,var(--sky-bottom) 82%,#FFEBB0 100%);
        min-height:100vh; overflow-x:hidden; position:relative;
    }
    .sun{
        position:absolute; top:5%; right:8%; width:100px; height:100px; border-radius:50%;
        background:radial-gradient(circle at 35% 35%,#FFF6C9,var(--gold) 60%,var(--gold-dark) 100%);
        box-shadow:0 0 50px 16px rgba(255,201,60,.5);
        animation:sunPulse 4s ease-in-out infinite; z-index:0;
    }
    @keyframes sunPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.06);}}
    .cloud{ position:absolute; opacity:.9; z-index:0; }
    .cloud svg{ display:block; }
    .cloud.c1{ top:8%;  left:-10%; width:170px; animation:drift 44s linear infinite; }
    .cloud.c2{ top:18%; left:-20%; width:120px; animation:drift 58s linear infinite; animation-delay:-12s; }
    .cloud.c3{ top:5%;  left:-15%; width:95px;  animation:drift 34s linear infinite; animation-delay:-24s; }
    @keyframes drift{ from{transform:translateX(0);} to{transform:translateX(140vw);} }
    .mountains{
        position:absolute; bottom:80px; left:0; width:100%; height:18%;
        background:linear-gradient(180deg,#B79CE0,#8F72C4);
        clip-path:polygon(0% 100%,8% 40%,18% 70%,30% 20%,42% 65%,55% 15%,68% 60%,80% 25%,92% 55%,100% 30%,100% 100%);
        opacity:.5; z-index:0;
    }
    .ground{
        position:absolute; bottom:0; left:0; right:0; height:80px;
        background:linear-gradient(180deg,var(--ground) 0%,var(--ground-dark) 100%);
        border-top:4px solid #4E9048; z-index:0;
    }

    /* Quit button */
    .quit-btn{
        position:fixed; top:22px; left:24px; z-index:20;
        display:flex; align-items:center; gap:6px;
        color:#B3261E; font-family:'Baloo 2',sans-serif; font-size:15px; font-weight:700;
        text-decoration:none; padding:9px 20px; border-radius:20px;
        border:2px solid #B3261E; background:#FFE1E1; cursor:pointer; transition:all .2s;
    }
    .quit-btn:hover{ background:#FFC9C9; }

    /* Stage */
    .stage{
        position:relative; z-index:5; min-height:100vh;
        display:flex; flex-direction:column; align-items:center;
        justify-content:center; gap:22px; padding:100px 20px 60px;
    }

    .title-card{
        background:var(--cream); border:4px solid var(--panel); border-radius:18px;
        padding:14px 34px; text-align:center; box-shadow:0 6px 0 rgba(0,0,0,.15);
    }
    .title-card .label{
        font-family:'Baloo 2',sans-serif; font-size:11px; font-weight:700;
        letter-spacing:.08em; text-transform:uppercase;
        color:var(--panel-light); margin-bottom:4px;
    }
    .title-card .value{
        font-family:'Baloo 2',sans-serif; font-size:22px; font-weight:800; color:var(--ink);
    }

    .passage-card{
        background:var(--cream); border:5px solid var(--panel); border-radius:24px;
        padding:26px 40px; max-width:640px; width:100%; text-align:center;
        box-shadow:0 8px 0 rgba(0,0,0,.15);
    }
    .passage-card .label{
        font-family:'Baloo 2',sans-serif; font-size:12px; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase;
        color:var(--panel-light); margin-bottom:12px;
    }
    .passage-card .content{
        font-family:'Baloo 2',sans-serif; font-weight:700; color:var(--ink);
        font-size:clamp(20px,3.2vw,32px); line-height:1.5;
    }
    .passage-card .content.long{
        font-size:16px; line-height:1.9;
        font-family:'Nunito',sans-serif; font-weight:700; text-align:left;
    }

    /* Status text */
    .rec-status-text{
        font-family:'Baloo 2',sans-serif; font-size:14px; font-weight:700;
        color:var(--panel); background:rgba(255,255,255,.6);
        padding:6px 18px; border-radius:20px; text-align:center;
    }

    /* Waveform */
    .waveform-wrap{
        display:none; align-items:center; gap:3px; height:40px;
        background:#fff; border:2px solid var(--panel); border-radius:10px; padding:8px 14px;
    }
    .wv{ width:4px; border-radius:3px; background:#D9D0F2; height:6px; transition:height .08s; }

    /* Mic button */
    .mic-wrap{ display:flex; flex-direction:column; align-items:center; gap:10px; }
    .mic-btn{
        width:90px; height:90px; border-radius:50%; border:none; cursor:pointer;
        background:linear-gradient(180deg,#FF8FB8,var(--pink));
        display:flex; align-items:center; justify-content:center;
        box-shadow:0 0 0 12px rgba(255,111,165,.18), 0 6px 0 rgba(0,0,0,.15);
        animation:micPulse 2s infinite; transition:all .2s;
        position:relative;
    }
    .mic-btn.recording{
        background:linear-gradient(180deg,#8F7AD1,var(--purple));
        box-shadow:0 0 0 12px rgba(124,58,237,.2), 0 6px 0 rgba(0,0,0,.15);
        animation:recPulse 1s infinite;
    }
    .mic-btn.submitting{
        background:linear-gradient(180deg,#FFC57A,var(--gold-dark));
        animation:none; cursor:not-allowed;
    }
    .mic-btn i{ color:#fff; font-size:34px; pointer-events:none; }
    @keyframes micPulse{
        0%,100%{ box-shadow:0 0 0 12px rgba(255,111,165,.18), 0 6px 0 rgba(0,0,0,.15); }
        50%{ box-shadow:0 0 0 20px rgba(255,111,165,.06), 0 6px 0 rgba(0,0,0,.15); }
    }
    @keyframes recPulse{
        0%,100%{ box-shadow:0 0 0 12px rgba(124,58,237,.25), 0 6px 0 rgba(0,0,0,.15); }
        50%{ box-shadow:0 0 0 22px rgba(124,58,237,.06), 0 6px 0 rgba(0,0,0,.15); }
    }
    .mic-timer{
        font-family:'Baloo 2',sans-serif; font-size:24px; font-weight:800;
        color:var(--panel); display:none;
    }
    .mic-hint{
        font-family:'Baloo 2',sans-serif; font-size:12px; font-weight:600;
        color:var(--panel-light); opacity:.7;
    }

    /* ── Success popup overlay ────────────────── */
    #success-overlay{
        display:none; position:fixed; inset:0;
        background:rgba(20,15,40,0.72);
        z-index:9999; align-items:center; justify-content:center;
        animation:fadeIn 0.3s ease;
    }
    .success-card{
        background:var(--cream); border:5px solid var(--gold);
        border-radius:26px; padding:40px 36px; text-align:center;
        max-width:400px; width:90%;
        box-shadow:0 10px 0 rgba(0,0,0,.18);
        animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
    }
    .success-emoji{ font-size:72px; margin-bottom:12px; }
    .success-title{
        font-family:'Baloo 2',sans-serif; font-size:24px; font-weight:800;
        color:var(--panel); margin-bottom:8px;
    }
    .success-sub{
        font-family:'Baloo 2',sans-serif; font-size:14px; font-weight:600;
        color:var(--panel-light); line-height:1.5; margin-bottom:16px;
    }
    .success-countdown{
        font-family:'Baloo 2',sans-serif; font-size:13px; color:var(--panel-light);
        opacity:.7;
    }
    .success-bar-wrap{
        width:100%; background:rgba(0,0,0,.08); border-radius:8px;
        height:8px; margin-top:12px; overflow:hidden;
    }
    .success-bar{
        height:8px; border-radius:8px;
        background:linear-gradient(90deg,var(--gold),var(--pink));
        width:100%;
        transition:width linear;
    }

    /* Uploading spinner overlay */
    #uploading-overlay{
        display:none; position:fixed; inset:0;
        background:rgba(20,15,40,0.6);
        z-index:9998; align-items:center; justify-content:center;
        flex-direction:column; gap:14px;
    }
    .uploading-card{
        background:var(--cream); border:4px solid var(--panel);
        border-radius:20px; padding:28px 36px; text-align:center;
        box-shadow:0 8px 0 rgba(0,0,0,.15);
    }
    .uploading-spinner{
        width:48px; height:48px; border-radius:50%;
        border:5px solid #E6DEFA;
        border-top-color:var(--purple);
        animation:spin 0.8s linear infinite; margin:0 auto 12px;
    }
    @keyframes spin{ to{transform:rotate(360deg);} }
    .uploading-text{
        font-family:'Baloo 2',sans-serif; font-size:15px;
        font-weight:700; color:var(--panel);
    }

    @keyframes fadeIn{ from{opacity:0;} to{opacity:1;} }
    @keyframes popIn{
        0%{transform:scale(0.5);opacity:0;}
        70%{transform:scale(1.05);}
        100%{transform:scale(1);opacity:1;}
    }
</style>
</head>
<body>

<div class="sun"></div>
<div class="cloud c1"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="cloud c2"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="cloud c3"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="mountains"></div>
<div class="ground"></div>

<a href="{{ route('student.readaloud.index') }}" class="quit-btn">
    <i class="ti ti-arrow-left"></i> Back
</a>

{{-- Uploading overlay --}}
<div id="uploading-overlay">
    <div class="uploading-card">
        <div class="uploading-spinner"></div>
        <div class="uploading-text">📤 Submitting your recording…</div>
    </div>
</div>

{{-- Success popup --}}
<div id="success-overlay">
    <div class="success-card">
        <div class="success-emoji">🎉</div>
        <div class="success-title">Nice Reading!</div>
        <div class="success-sub">
            Your recording has been sent to your teacher.<br>
            Wait for their evaluation and feedback!
        </div>
        <div class="success-countdown" id="countdown-text">
            Returning in <strong id="countdown-num">4</strong>…
        </div>
        <div class="success-bar-wrap">
            <div class="success-bar" id="success-bar"></div>
        </div>
    </div>
</div>

<div class="stage">

    {{-- Activity title --}}
    <div class="title-card">
        <div class="label">🎙️ Activity</div>
        <div class="value">{{ $activity->activity_name }}</div>
    </div>

    {{-- Reading passage --}}
    <div class="passage-card">
        <div class="label">📖 Read this aloud:</div>
        @if($activity->readingMaterial && $activity->readingMaterial->content)
            @php $content = $activity->readingMaterial->content; @endphp
            <div class="content {{ str_word_count($content) > 12 ? 'long' : '' }}">
                {{ $content }}
            </div>
        @else
            <div class="content">⚠️ No passage added yet.</div>
        @endif
    </div>

    {{-- Status text --}}
    <div class="rec-status-text" id="rec-status">
        Press and hold the button below to start recording
    </div>

    {{-- Waveform --}}
    <div class="waveform-wrap" id="waveform-wrap">
        @for($i = 0; $i < 18; $i++)<div class="wv"></div>@endfor
    </div>

    {{-- Mic button --}}
    <div class="mic-wrap">
        <button type="button" id="mic-btn" class="mic-btn"
                onmousedown="startRecording()"
                onmouseup="stopRecording()"
                ontouchstart="startRecording(event)"
                ontouchend="stopRecording(event)">
            <i class="ti ti-microphone" id="mic-icon"></i>
        </button>
        <div class="mic-timer" id="mic-timer">0:00</div>
        <div class="mic-hint" id="mic-hint">Hold the button while reading</div>
    </div>

    {{-- Hidden form --}}
    <form id="upload-form" method="POST"
          action="{{ route('student.readaloud.upload', $activity->id) }}"
          enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="file" name="recording" id="recording-file" accept="audio/*">
    </form>

</div>

<script>
let mediaRecorder, audioChunks = [], isRecording = false;
let timerInterval, seconds = 0, waveInterval = null;

const micBtn       = document.getElementById('mic-btn');
const micIcon      = document.getElementById('mic-icon');
const micTimer     = document.getElementById('mic-timer');
const micHint      = document.getElementById('mic-hint');
const recStatus    = document.getElementById('rec-status');
const waveWrap     = document.getElementById('waveform-wrap');
const wvBars       = document.querySelectorAll('.wv');
const recFileInput = document.getElementById('recording-file');

// ── Start recording ────────────────────────────────────────────
function startRecording(e) {
    if (e) e.preventDefault();
    if (isRecording) return;

    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        mediaRecorder = new MediaRecorder(stream);
        audioChunks   = [];

        mediaRecorder.ondataavailable = ev => {
            if (ev.data.size > 0) audioChunks.push(ev.data);
        };

        mediaRecorder.onstop = () => {
            // Auto-submit right away
            submitRecording();
        };

        mediaRecorder.start(100);
        isRecording = true;

        micBtn.classList.add('recording');
        micIcon.className       = 'ti ti-player-stop';
        recStatus.textContent   = '🔴 Recording… release when done!';
        micHint.textContent     = 'Release the button when finished';
        micTimer.style.display  = 'block';
        waveWrap.style.display  = 'flex';

        seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            micTimer.textContent =
                Math.floor(seconds/60).toString().padStart(2,'0') + ':' +
                (seconds%60).toString().padStart(2,'0');
        }, 1000);

        animateWaveform();

    }).catch(() => {
        alert('Microphone access denied! Please allow microphone access.');
    });
}

// ── Stop recording & auto-submit ───────────────────────────────
function stopRecording(e) {
    if (e) e.preventDefault();
    if (!isRecording || !mediaRecorder) return;

    mediaRecorder.stop();
    mediaRecorder.stream.getTracks().forEach(t => t.stop());
    isRecording = false;
    clearInterval(timerInterval);

    micBtn.classList.remove('recording');
    micBtn.classList.add('submitting');
    micIcon.className     = 'ti ti-loader';
    micTimer.style.display = 'none';
    waveWrap.style.display = 'none';
    recStatus.textContent  = '📤 Submitting your recording…';
    micHint.textContent    = 'Please wait…';
    micBtn.disabled        = true;

    stopWaveform();
}

// ── Submit via fetch (no page reload) ─────────────────────────
async function submitRecording() {
    if (!audioChunks.length) return;

    // Show uploading overlay
    document.getElementById('uploading-overlay').style.display = 'flex';

    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    const formData  = new FormData();

    const file = new File([audioBlob], 'recording.webm', { type: 'audio/webm' });
    formData.append('recording', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const res = await fetch('{{ route("student.readaloud.upload", $activity->id) }}', {
            method: 'POST',
            body  : formData,
        });

        // Hide uploading overlay
        document.getElementById('uploading-overlay').style.display = 'none';

        if (res.ok || res.redirected) {
            showSuccessPopup();
        } else {
            recStatus.textContent = '❌ Upload failed. Please try again.';
            micBtn.classList.remove('submitting');
            micBtn.disabled = false;
            micIcon.className = 'ti ti-microphone';
            micHint.textContent = 'Hold the button while reading';
        }

    } catch(err) {
        document.getElementById('uploading-overlay').style.display = 'none';
        recStatus.textContent = '❌ Something went wrong. Please try again.';
        micBtn.classList.remove('submitting');
        micBtn.disabled = false;
        micIcon.className = 'ti ti-microphone';
        micHint.textContent = 'Hold the button while reading';
    }
}

// ── Success popup + countdown ──────────────────────────────────
function showSuccessPopup() {
    const overlay    = document.getElementById('success-overlay');
    const bar        = document.getElementById('success-bar');
    const numEl      = document.getElementById('countdown-num');
    const totalSecs  = 4;
    let   remaining  = totalSecs;

    overlay.style.display = 'flex';

    // Animate bar shrinking
    bar.style.transition = `width ${totalSecs}s linear`;
    // Force reflow before starting animation
    void bar.offsetWidth;
    bar.style.width = '0%';

    // Countdown
    const countInterval = setInterval(() => {
        remaining--;
        numEl.textContent = remaining;
        if (remaining <= 0) {
            clearInterval(countInterval);
            // Redirect to index
            window.location.href = '{{ route("student.readaloud.index") }}';
        }
    }, 1000);
}

// ── Waveform animation ─────────────────────────────────────────
function animateWaveform() {
    let t = 0;
    waveInterval = setInterval(() => {
        wvBars.forEach((b,i) => {
            const h = Math.abs(Math.sin((t+i)*0.35))*28+4;
            b.style.height     = h + 'px';
            b.style.background = '#7C3AED';
        });
        t++;
    }, 80);
}
function stopWaveform() {
    if (waveInterval) clearInterval(waveInterval);
    wvBars.forEach(b => {
        b.style.height     = '6px';
        b.style.background = '#D9D0F2';
    });
}

// Prevent context menu on long press (mobile)
document.getElementById('mic-btn').addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>