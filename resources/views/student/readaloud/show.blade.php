@extends('layouts.student')
@section('title', 'Read Aloud')
@section('page-greet', 'Read Aloud')
@section('page-sub', 'Read the passage and share your recording with your teacher.')
@push('styles')
<style>
.rk-reader {
        --sky-top:#5FC0FF; --sky-mid:#8FD8FF; --sky-bottom:#FFDD8A;
        --ground:#7BC96F; --ground-dark:#5AA652;
        --panel:#3B2E63; --panel-light:#5B4696;
        --gold:#FFC93C; --gold-dark:#E0A11B;
        --ink:#2B2140; --cream:#FFF7E6; --pink:#FF6FA5; --purple:#7C3AED;
    }
.rk-reader, .rk-reader * { box-sizing:border-box; margin:0; padding:0; }
.rk-reader {
        font-family:'Nunito',sans-serif;
        background:linear-gradient(180deg,var(--sky-top) 0%,var(--sky-mid) 40%,var(--sky-bottom) 82%,#FFEBB0 100%);
        min-height:620px; overflow:hidden; position:relative; border-radius:18px;
    }
.rk-reader .sun {
        position:absolute; top:5%; right:8%; width:100px; height:100px; border-radius:50%;
        background:radial-gradient(circle at 35% 35%,#FFF6C9,var(--gold) 60%,var(--gold-dark) 100%);
        box-shadow:0 0 50px 16px rgba(255,201,60,.5);
        animation:sunPulse 4s ease-in-out infinite; z-index:0;
    }
@keyframes sunPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.06);}}
.rk-reader .cloud { position:absolute; opacity:.9; z-index:0; }
.rk-reader .cloud svg { display:block; }
.rk-reader .cloud.c1 { top:8%;  left:-10%; width:170px; animation:drift 44s linear infinite; }
.rk-reader .cloud.c2 { top:18%; left:-20%; width:120px; animation:drift 58s linear infinite; animation-delay:-12s; }
.rk-reader .cloud.c3 { top:5%;  left:-15%; width:95px;  animation:drift 34s linear infinite; animation-delay:-24s; }
@keyframes drift{ from{transform:translateX(0);} to{transform:translateX(140vw);} }
.rk-reader .mountains {
        position:absolute; bottom:80px; left:0; width:100%; height:18%;
        background:linear-gradient(180deg,#B79CE0,#8F72C4);
        clip-path:polygon(0% 100%,8% 40%,18% 70%,30% 20%,42% 65%,55% 15%,68% 60%,80% 25%,92% 55%,100% 30%,100% 100%);
        opacity:.5; z-index:0;
    }
.rk-reader .ground {
        position:absolute; bottom:0; left:0; right:0; height:80px;
        background:linear-gradient(180deg,var(--ground) 0%,var(--ground-dark) 100%);
        border-top:4px solid #4E9048; z-index:0;
    }
.rk-reader .quit-btn {
        position:absolute; top:22px; left:24px; z-index:20;
        display:flex; align-items:center; gap:6px;
        color:#B3261E; font-family:'Baloo 2',sans-serif; font-size:15px; font-weight:700;
        text-decoration:none; padding:9px 20px; border-radius:20px;
        border:2px solid #B3261E; background:#FFE1E1; cursor:pointer; transition:all .2s;
    }
.rk-reader .quit-btn:hover { background:#FFC9C9; }
.rk-reader .stage {
        position:relative; z-index:5; min-height:620px;
        display:flex; flex-direction:column; align-items:center;
        justify-content:center; gap:22px; padding:100px 20px 60px;
    }
.rk-reader .title-card {
        background:var(--cream); border:4px solid var(--panel); border-radius:18px;
        padding:14px 34px; text-align:center; box-shadow:0 6px 0 rgba(0,0,0,.15);
    }
.rk-reader .title-card .label {
        font-family:'Baloo 2',sans-serif; font-size:11px; font-weight:700;
        letter-spacing:.08em; text-transform:uppercase;
        color:var(--panel-light); margin-bottom:4px;
    }
.rk-reader .title-card .value {
        font-family:'Baloo 2',sans-serif; font-size:22px; font-weight:800; color:var(--ink);
    }
.rk-reader .passage-card {
        background:var(--cream); border:5px solid var(--panel); border-radius:24px;
        padding:26px 40px; max-width:640px; width:100%; text-align:center;
        box-shadow:0 8px 0 rgba(0,0,0,.15);
    }
.rk-reader .passage-card .label {
        font-family:'Baloo 2',sans-serif; font-size:12px; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase;
        color:var(--panel-light); margin-bottom:12px;
    }
.rk-reader .passage-card .content {
        font-family:'Baloo 2',sans-serif; font-weight:700; color:var(--ink);
        font-size:clamp(20px,3.2vw,32px); line-height:1.5;
    }
.rk-reader .passage-card .content.long {
        font-size:16px; line-height:1.9;
        font-family:'Nunito',sans-serif; font-weight:700; text-align:left;
    }
.rk-reader .rec-status-text {
        font-family:'Baloo 2',sans-serif; font-size:14px; font-weight:700;
        color:var(--panel); background:rgba(255,255,255,.6);
        padding:6px 18px; border-radius:20px; text-align:center;
    }
.rk-reader .waveform-wrap {
        display:none; align-items:center; gap:3px; height:40px;
        background:#fff; border:2px solid var(--panel); border-radius:10px; padding:8px 14px;
    }
.rk-reader .wv { width:4px; border-radius:3px; background:#D9D0F2; height:6px; transition:height .08s; }
.rk-reader .mic-wrap { display:flex; flex-direction:column; align-items:center; gap:10px; }
.rk-reader .mic-btn {
        width:90px; height:90px; border-radius:50%; border:none; cursor:pointer;
        background:linear-gradient(180deg,#FF8FB8,var(--pink));
        display:flex; align-items:center; justify-content:center;
        box-shadow:0 0 0 12px rgba(255,111,165,.18), 0 6px 0 rgba(0,0,0,.15);
        animation:micPulse 2s infinite; transition:all .2s;
        position:relative; touch-action:none; user-select:none; -webkit-user-select:none;
    }
.rk-reader .mic-btn.recording {
        background:linear-gradient(180deg,#8F7AD1,var(--purple));
        box-shadow:0 0 0 12px rgba(124,58,237,.2), 0 6px 0 rgba(0,0,0,.15);
        animation:recPulse 1s infinite;
    }
.rk-reader .mic-btn.submitting {
        background:linear-gradient(180deg,#FFC57A,var(--gold-dark));
        animation:none; cursor:not-allowed;
    }
.rk-reader .mic-btn i { color:#fff; font-size:34px; pointer-events:none; }
@keyframes micPulse{
        0%,100%{ box-shadow:0 0 0 12px rgba(255,111,165,.18), 0 6px 0 rgba(0,0,0,.15); }
        50%{ box-shadow:0 0 0 20px rgba(255,111,165,.06), 0 6px 0 rgba(0,0,0,.15); }
    }
@keyframes recPulse{
        0%,100%{ box-shadow:0 0 0 12px rgba(124,58,237,.25), 0 6px 0 rgba(0,0,0,.15); }
        50%{ box-shadow:0 0 0 22px rgba(124,58,237,.06), 0 6px 0 rgba(0,0,0,.15); }
    }
.rk-reader .mic-timer {
        font-family:'Baloo 2',sans-serif; font-size:24px; font-weight:800;
        color:var(--panel); display:none;
    }
.rk-reader .mic-hint {
        font-family:'Baloo 2',sans-serif; font-size:12px; font-weight:600;
        color:var(--panel-light); opacity:.7;
    }
.rk-reader #success-overlay {
        display:none; position:fixed; inset:0;
        background:rgba(20,15,40,0.72);
        z-index:9999; align-items:center; justify-content:center;
        animation:fadeIn 0.3s ease;
    }
.rk-reader .success-card {
        background:var(--cream); border:5px solid var(--gold);
        border-radius:26px; padding:40px 36px; text-align:center;
        max-width:400px; width:90%;
        box-shadow:0 10px 0 rgba(0,0,0,.18);
        animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
    }
.rk-reader .success-emoji { font-size:72px; margin-bottom:12px; }
.rk-reader .success-title {
        font-family:'Baloo 2',sans-serif; font-size:24px; font-weight:800;
        color:var(--panel); margin-bottom:8px;
    }
.rk-reader .success-sub {
        font-family:'Baloo 2',sans-serif; font-size:14px; font-weight:600;
        color:var(--panel-light); line-height:1.5; margin-bottom:16px;
    }
.rk-reader .success-countdown {
        font-family:'Baloo 2',sans-serif; font-size:13px; color:var(--panel-light);
        opacity:.7;
    }
.rk-reader .success-bar-wrap {
        width:100%; background:rgba(0,0,0,.08); border-radius:8px;
        height:8px; margin-top:12px; overflow:hidden;
    }
.rk-reader .success-bar {
        height:8px; border-radius:8px;
        background:linear-gradient(90deg,var(--gold),var(--pink));
        width:100%;
        transition:width linear;
    }
.rk-reader #uploading-overlay {
        display:none; position:fixed; inset:0;
        background:rgba(20,15,40,0.6);
        z-index:9998; align-items:center; justify-content:center;
        flex-direction:column; gap:14px;
    }
.rk-reader .uploading-card {
        background:var(--cream); border:4px solid var(--panel);
        border-radius:20px; padding:28px 36px; text-align:center;
        box-shadow:0 8px 0 rgba(0,0,0,.15);
    }
.rk-reader .uploading-spinner {
        width:48px; height:48px; border-radius:50%;
        border:5px solid #E6DEFA;
        border-top-color:var(--purple);
        animation:spin 0.8s linear infinite; margin:0 auto 12px;
    }
@keyframes spin{ to{transform:rotate(360deg);} }
.rk-reader .uploading-text {
        font-family:'Baloo 2',sans-serif; font-size:15px;
        font-weight:700; color:var(--panel);
    }
@keyframes fadeIn{ from{opacity:0;} to{opacity:1;} }
@keyframes popIn{
        0%{transform:scale(0.5);opacity:0;}
        70%{transform:scale(1.05);}
        100%{transform:scale(1);opacity:1;}
    }

.rk-reader .title-card, .rk-reader .passage-card { overflow-wrap:anywhere; }
.rk-reader .title-card { max-width:100%; }
@media(max-width:479.98px) {
 .rk-reader .stage { padding:90px 12px 40px; }
 .rk-reader .passage-card { padding:20px 16px; }
 .rk-reader .title-card { padding:12px 18px; }
}
</style>
@endpush
@section('content')
<div class="rk-reader">


<div class="sun"></div>
<div class="cloud c1"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="cloud c2"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="cloud c3"><svg viewBox="0 0 200 90"><path d="M20 70 Q0 70 0 50 Q0 30 25 32 Q28 8 58 12 Q80 -5 100 15 Q130 5 138 30 Q170 28 170 55 Q170 70 150 70 Z" fill="#fff"/></svg></div>
<div class="mountains"></div>
<div class="ground"></div>

<a href="{{ route('student.readaloud.index', [], false) }}" class="quit-btn">
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
    <div class="rec-status-text" id="rec-status" role="status" aria-live="polite">
        Click the button below to start recording
    </div>

    {{-- Waveform --}}
    <div class="waveform-wrap" id="waveform-wrap">
        @for($i = 0; $i < 18; $i++)<div class="wv"></div>@endfor
    </div>

    {{-- Mic button --}}
    <div class="mic-wrap">
        <button type="button" id="mic-btn" class="mic-btn"
                aria-label="Start recording" aria-describedby="mic-hint"
                onclick="toggleRecording()"
                ontouchstart="startRecording(event)"
                ontouchend="stopRecording(event)"
                ontouchcancel="stopRecording(event)">
            <i class="ti ti-microphone" id="mic-icon"></i>
        </button>
        <div class="mic-timer" id="mic-timer">0:00</div>
        <div class="mic-hint" id="mic-hint">Click to record, then click again to submit</div>
    </div>

    {{-- Hidden form --}}
    <form id="upload-form" method="POST"
          action="{{ route('student.readaloud.upload', $activity->id, false) }}"
          enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="file" name="recording" id="recording-file" accept="audio/*">
    </form>

</div>


</div>
@endsection
@push('scripts')
<script>
let mediaRecorder, audioChunks = [], isRecording = false;
let timerInterval, seconds = 0, waveInterval = null;
let requestingMic = false, holdActive = false, submitting = false, recordingFailed = false;
let touchRecording = window.matchMedia('(pointer: coarse)').matches;

const micBtn       = document.getElementById('mic-btn');
const micIcon      = document.getElementById('mic-icon');
const micTimer     = document.getElementById('mic-timer');
const micHint      = document.getElementById('mic-hint');
const recStatus    = document.getElementById('rec-status');
const waveWrap     = document.getElementById('waveform-wrap');
const wvBars       = document.querySelectorAll('.wv');
const uploadUrl    = @json(route('student.readaloud.upload', $activity->id, false));
const indexUrl     = @json(route('student.readaloud.index', [], false));
const debugUploads = @json(config('app.debug'));
const maxRecordingBytes = 20 * 1024 * 1024;

function idleRecordingHint() {
    return touchRecording ? 'Hold the button while reading' : 'Click to record, then click again to submit';
}

micHint.textContent = idleRecordingHint();
recStatus.textContent = touchRecording
    ? 'Press and hold the button below to start recording'
    : 'Click the button below to start recording';

function toggleRecording() {
    if (requestingMic || submitting || micBtn.disabled) return;
    if (isRecording) {
        stopRecording();
    } else {
        startRecording();
    }
}

function debugRecording(label, details) {
    if (debugUploads) console.log('[ReadAloud] ' + label, details);
}

debugRecording('environment', {
    origin: window.location.origin,
    secure: window.isSecureContext,
    mediaDevices: !!navigator.mediaDevices,
    getUserMedia: !!navigator.mediaDevices?.getUserMedia,
    mediaRecorder: typeof MediaRecorder !== 'undefined',
    uploadUrl,
});

function getSupportedMimeType() {
    if (typeof MediaRecorder.isTypeSupported !== 'function') return '';
    return ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']
        .find(type => MediaRecorder.isTypeSupported(type)) || '';
}

function recordingExtension(mime) {
    switch (mime.split(';')[0].toLowerCase()) {
        case 'audio/webm': case 'video/webm': return 'webm';
        case 'audio/ogg': case 'application/ogg': return 'ogg';
        case 'audio/mp4': case 'video/mp4': case 'audio/x-m4a': return 'mp4';
        case 'audio/wav': case 'audio/x-wav': return 'wav';
        case 'audio/mpeg': return 'mp3';
        default: throw new Error('This browser produced an unsupported recording format. Try another browser.');
    }
}

function resetRecordingUi() {
    clearInterval(timerInterval);
    stopWaveform();
    micBtn.classList.remove('recording', 'submitting');
    micBtn.disabled = false;
    micIcon.className = 'ti ti-microphone';
    micTimer.style.display = 'none';
    waveWrap.style.display = 'none';
    micHint.textContent = idleRecordingHint();
    micBtn.setAttribute('aria-label', 'Start recording');
    document.getElementById('uploading-overlay').style.display = 'none';
}

function showRecordingError(message) {
    resetRecordingUi();
    recStatus.textContent = '❌ ' + message;
}

function microphoneErrorMessage(error) {
    switch (error.name) {
        case 'NotAllowedError': return 'Microphone permission was denied. Allow it for this site in your browser settings, then try again.';
        case 'NotFoundError': return 'No microphone was found. Connect a microphone and try again.';
        case 'NotReadableError': return 'Your microphone is unavailable or in use by another app. Close that app and try again.';
        case 'SecurityError': return 'Your browser blocked microphone access. Open this page over HTTPS and check its permissions.';
        case 'AbortError': return 'Microphone startup was interrupted. Please try again.';
        default: return 'Could not start recording. Try again or use another browser.';
    }
}

// Touch holds to record; mouse and keyboard clicks toggle recording.
async function startRecording(e) {
    if (e) e.preventDefault();
    if (isRecording || requestingMic || submitting || micBtn.disabled) return;
    touchRecording = !!e && e.type === 'touchstart';
    if (!window.isSecureContext) {
        showRecordingError('Recording requires HTTPS or localhost. Open the secure link supplied by your teacher.');
        return;
    }
    if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
        showRecordingError('Audio recording is not supported in this browser. Try an up-to-date browser.');
        return;
    }

    requestingMic = true;
    holdActive = true;
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        // Releasing while the permission prompt is open must not start a stuck recording.
        if (touchRecording && !holdActive) {
            stream.getTracks().forEach(track => track.stop());
            recStatus.textContent = 'Microphone is ready. Hold the button again while reading.';
            return;
        }

        const mimeType = getSupportedMimeType();
        const recorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
        mediaRecorder = recorder;
        audioChunks = [];
        recordingFailed = false;
        recorder.ondataavailable = event => {
            if (event.data.size > 0) audioChunks.push(event.data);
        };
        recorder.onerror = event => {
            recordingFailed = true;
            isRecording = false;
            holdActive = false;
            console.error('[ReadAloud] MediaRecorder failed:', event.error || event);
            stream.getTracks().forEach(track => track.stop());
            showRecordingError('Recording was interrupted. Please record your reading again.');
        };
        recorder.onstop = () => {
            stream.getTracks().forEach(track => track.stop());
            isRecording = false;
            holdActive = false;
            clearInterval(timerInterval);
            stopWaveform();
            if (!recordingFailed) submitRecording();
        };
        recorder.start(100);
        isRecording = true;
        micBtn.classList.add('recording');
        micIcon.className = 'ti ti-player-stop';
        recStatus.textContent = '🔴 Recording… ' + (touchRecording ? 'release when done!' : 'click again to submit!');
        micHint.textContent = touchRecording ? 'Release the button when finished' : 'Click the button to stop and submit';
        micBtn.setAttribute('aria-label', touchRecording ? 'Release to submit recording' : 'Stop and submit recording');
        micTimer.textContent = '0:00';
        micTimer.style.display = 'block';
        waveWrap.style.display = 'flex';
        seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            micTimer.textContent = Math.floor(seconds / 60).toString().padStart(2, '0') +
                ':' + (seconds % 60).toString().padStart(2, '0');
        }, 1000);
        animateWaveform();
        debugRecording('recorder MIME', recorder.mimeType);
    } catch (error) {
        if (stream) stream.getTracks().forEach(track => track.stop());
        holdActive = false;
        isRecording = false;
        console.error('[ReadAloud] Recording startup failed:', error);
        showRecordingError(microphoneErrorMessage(error));
    } finally {
        requestingMic = false;
    }
}

function stopRecording(e) {
    if (e) e.preventDefault();
    holdActive = false;
    if (!isRecording || !mediaRecorder || mediaRecorder.state !== 'recording') return;

    isRecording = false;
    clearInterval(timerInterval);
    stopWaveform();
    micBtn.classList.remove('recording');
    micBtn.classList.add('submitting');
    micBtn.disabled = true;
    micIcon.className = 'ti ti-loader';
    micTimer.style.display = 'none';
    waveWrap.style.display = 'none';
    recStatus.textContent = '📤 Submitting your recording…';
    micHint.textContent = 'Please wait…';
    try {
        mediaRecorder.stop();
    } catch (error) {
        recordingFailed = true;
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        console.error('[ReadAloud] Could not stop recording:', error);
        showRecordingError('Could not finish the recording. Please try again.');
    }
}

function uploadErrorMessage(status, data) {
    if (status === 419) return 'Your session expired. Refresh this page and sign in again before recording.';
    if (status === 401) return 'Please sign in again, then reopen this activity.';
    if (status === 403 || status === 404) return 'This activity is no longer available to you. Return to your activities.';
    if (status === 413) return 'The recording is too large. Please make a shorter recording.';
    if (status === 422) {
        const errors = data?.errors?.recording;
        return Array.isArray(errors) && errors.length
            ? errors.join(' ') : 'The recording could not be accepted. Please record again.';
    }
    if (status >= 500) return 'The server could not save your recording. Please try again shortly.';
    return 'Upload failed. Please try again.';
}

async function submitRecording() {
    if (submitting) return;
    submitting = true;
    micBtn.disabled = true;
    document.getElementById('uploading-overlay').style.display = 'flex';
    try {
        const mime = mediaRecorder?.mimeType || audioChunks.find(chunk => chunk.type)?.type || '';
        const audioBlob = new Blob(audioChunks, { type: mime });
        debugRecording('recording', { mime: audioBlob.type, size: audioBlob.size, uploadUrl });
        if (!audioBlob.size) throw new Error('No audio was recorded. ' + (touchRecording
            ? 'Hold the button while reading, then release it.'
            : 'Click to start recording, read aloud, then click again to submit.'));
        if (audioBlob.size > maxRecordingBytes) throw new Error('The recording is larger than 20 MB. Please make a shorter recording.');

        const formData = new FormData();
        formData.append('recording', audioBlob, 'recording.' + recordingExtension(audioBlob.type));
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        const response = await fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const rawText = await response.text();
        const contentType = response.headers.get('content-type') || '';
        let data = null;
        if (contentType.includes('json')) {
            try { data = JSON.parse(rawText); } catch (_) { /* Report as an invalid response below. */ }
        }
        if (response.redirected || !response.ok || data?.status !== 'pending' || !data?.recording_id) {
            console.error('[ReadAloud] Upload response rejected:', {
                status: response.status, url: response.url, redirected: response.redirected, contentType,
            });
            if (debugUploads) console.error('[ReadAloud] Response body:', rawText);
            if (response.redirected) throw new Error('The upload was redirected. Refresh the page and sign in again before recording.');
            if (!response.ok) throw new Error(uploadErrorMessage(response.status, data));
            throw new Error('The server did not confirm your recording. Refresh the page and try again.');
        }

        document.getElementById('uploading-overlay').style.display = 'none';
        showSuccessPopup();
    } catch (error) {
        console.error('[ReadAloud] Upload failed:', error);
        showRecordingError(error instanceof TypeError
            ? 'Could not reach the upload server. Check your connection and try again.'
            : error.message);
    } finally {
        submitting = false;
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
            window.location.href = indexUrl;
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

// A touch release outside the button still finishes a held recording.
// Desktop recording stays active until the next click, including during permission prompts.
function finishTouchRecording() {
    if (touchRecording && holdActive) stopRecording();
}
window.addEventListener('touchend', finishTouchRecording);
window.addEventListener('touchcancel', finishTouchRecording);
window.addEventListener('blur', finishTouchRecording);

// Prevent context menu on long press (mobile)
document.getElementById('mic-btn').addEventListener('contextmenu', e => e.preventDefault());
</script>
@endpush
