(() => {
'use strict';

const reader = document.getElementById('read-aloud-recorder');
if (!reader) return;

let mediaRecorder, audioChunks = [], isRecording = false;
let timerInterval = null, deadline = 0, waveInterval = null, successInterval = null;
let requestingMic = false, holdActive = false, submitting = false, recordingFailed = false;
let finalizing = false, timerExpired = false, completed = false, pageLeaving = false;
let pendingBlob = null, warnedAboutTime = false;
let touchRecording = window.matchMedia('(pointer: coarse)').matches;

const micBtn = document.getElementById('mic-btn');
const micIcon = document.getElementById('mic-icon');
const micTimer = document.getElementById('mic-timer');
const timerLabel = document.getElementById('timer-label');
const micHint = document.getElementById('mic-hint');
const recStatus = document.getElementById('rec-status');
const waveWrap = document.getElementById('waveform-wrap');
const wvBars = reader.querySelectorAll('.wv');
const retryBtn = document.getElementById('retry-upload');
const uploadForm = document.getElementById('upload-form');
const uploadUrl = uploadForm.getAttribute('action');
const indexUrl = reader.dataset.indexUrl;
const debugUploads = reader.dataset.debug === 'true';
const durationSeconds = Number(reader.dataset.durationSeconds);
const validDuration = Number.isSafeInteger(durationSeconds) && durationSeconds > 0;
const canRecord = reader.dataset.canRecord === 'true' && validDuration;
const maxRecordingBytes = 20 * 1024 * 1024;
let remainingSeconds = validDuration ? durationSeconds : 0;

function formatTime(seconds) {
    return String(Math.floor(seconds / 60)).padStart(2, '0') + ':' +
        String(seconds % 60).padStart(2, '0');
}

function renderTimer() {
    micTimer.textContent = validDuration ? formatTime(remainingSeconds) : '--:--';
    micTimer.classList.toggle('time-low', isRecording && remainingSeconds <= 10);
}

function clearCountdown() {
    clearInterval(timerInterval);
    timerInterval = null;
}

function updateCountdown() {
    if (!isRecording) return;
    // Use elapsed time, so delayed callbacks never extend the displayed allowance.
    remainingSeconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
    renderTimer();
    if (remainingSeconds === 0) {
        timerExpired = true;
        stopRecording();
    } else if (remainingSeconds <= 10 && !warnedAboutTime) {
        warnedAboutTime = true;
        recStatus.textContent = '10 seconds remaining. Finish your reading when you are ready.';
    }
}

function startCountdown() {
    clearCountdown();
    remainingSeconds = durationSeconds;
    deadline = performance.now() + durationSeconds * 1000;
    warnedAboutTime = false;
    timerLabel.textContent = 'Time Remaining';
    renderTimer();
    timerInterval = setInterval(updateCountdown, 250);
}

function idleRecordingHint() {
    return touchRecording ? 'Hold the button while reading' : 'Click to record, then click again to submit';
}

function debugRecording(label, details) {
    if (debugUploads) console.log('[ReadAloud] ' + label, details);
}

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
    clearCountdown();
    stopWaveform();
    micBtn.classList.remove('recording', 'submitting');
    micBtn.disabled = !canRecord || completed || pageLeaving || !!pendingBlob;
    micIcon.className = 'ti ti-microphone';
    waveWrap.style.display = 'none';
    micHint.textContent = pendingBlob
        ? 'Your audio is kept on this page. Retry before leaving or refreshing.'
        : idleRecordingHint();
    micBtn.setAttribute('aria-label', 'Start recording');
    retryBtn.hidden = !pendingBlob || completed || pageLeaving;
    retryBtn.disabled = false;
    document.getElementById('uploading-overlay').style.display = 'none';
    if (!pendingBlob) {
        remainingSeconds = durationSeconds;
        timerLabel.textContent = 'Reading Time';
    }
    renderTimer();
}

function showRecordingError(message) {
    resetRecordingUi();
    recStatus.textContent = message;
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


function toggleRecording() {
    if (requestingMic || submitting || finalizing || completed || pageLeaving || micBtn.disabled) return;
    if (isRecording) stopRecording();
    else startRecording();
}

// Keep the existing touch-hold and click-to-toggle recording controls.
async function startRecording(event) {
    if (event) event.preventDefault();
    if (!canRecord || isRecording || requestingMic || submitting || finalizing ||
        completed || pageLeaving || pendingBlob) return;
    touchRecording = event?.type === 'touchstart';
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
    micBtn.disabled = true;
    recStatus.textContent = 'Getting your microphone ready...';
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        if (pageLeaving || (touchRecording && !holdActive)) {
            stream.getTracks().forEach(track => track.stop());
            if (!pageLeaving) {
                resetRecordingUi();
                recStatus.textContent = 'Microphone is ready. Hold the button again while reading.';
            }
            return;
        }

        const mimeType = getSupportedMimeType();
        const recorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
        mediaRecorder = recorder;
        audioChunks = [];
        recordingFailed = false;
        timerExpired = false;
        recorder.ondataavailable = event => {
            if (recorder === mediaRecorder && event.data.size > 0) audioChunks.push(event.data);
        };
        recorder.onerror = event => {
            if (recorder !== mediaRecorder || pageLeaving) return;
            recordingFailed = true;
            isRecording = false;
            holdActive = false;
            finalizing = true;
            clearCountdown();
            stopWaveform();
            micBtn.disabled = true;
            recStatus.textContent = 'Recording was interrupted. Please record your reading again.';
            console.error('[ReadAloud] MediaRecorder failed:', event.error || event);
            // The recorder's error sequence finishes with dataavailable and stop.
            stream.getTracks().forEach(track => track.stop());
        };
        recorder.onstop = () => {
            stream.getTracks().forEach(track => track.stop());
            if (recorder !== mediaRecorder || pageLeaving || completed || pendingBlob || submitting) return;
            isRecording = false;
            holdActive = false;
            finalizing = false;
            clearCountdown();
            stopWaveform();
            if (recordingFailed) {
                showRecordingError('Recording was interrupted. Please record your reading again.');
                return;
            }
            // onstop follows the final dataavailable event; all chunks are now present.
            const mime = recorder.mimeType || audioChunks.find(chunk => chunk.type)?.type || '';
            const blob = new Blob(audioChunks, { type: mime });
            if (!blob.size || blob.size > maxRecordingBytes) {
                showRecordingError(!blob.size
                    ? 'No audio was recorded. Please record your reading again.'
                    : 'The recording is larger than 20 MB. Please make a shorter recording.');
                return;
            }
            pendingBlob = blob;
            submitRecording();
        };
        recorder.start(100);
        isRecording = true;
        startCountdown();
        micBtn.disabled = false;
        micBtn.classList.add('recording');
        micIcon.className = 'ti ti-player-stop';
        recStatus.textContent = 'Recording started. ' + (touchRecording ? 'Release when done!' : 'Click again when done!');
        micHint.textContent = touchRecording ? 'Release the button when finished' : 'Click the button to stop and submit';
        micBtn.setAttribute('aria-label', touchRecording ? 'Release to submit recording' : 'Stop and submit recording');
        waveWrap.style.display = 'flex';
        animateWaveform();
        debugRecording('recorder MIME', recorder.mimeType);
    } catch (error) {
        recordingFailed = true;
        const recorder = mediaRecorder;
        mediaRecorder = null;
        if (recorder?.state === 'recording') recorder.stop();
        if (stream) stream.getTracks().forEach(track => track.stop());
        holdActive = false;
        isRecording = false;
        finalizing = false;
        console.error('[ReadAloud] Recording startup failed:', error);
        if (!pageLeaving) showRecordingError(microphoneErrorMessage(error));
    } finally {
        requestingMic = false;
    }
}

function stopRecording(event) {
    if (event) event.preventDefault();
    holdActive = false;
    if (!isRecording || finalizing || !mediaRecorder || mediaRecorder.state !== 'recording') return;

    remainingSeconds = Math.max(0, Math.ceil((deadline - performance.now()) / 1000));
    timerExpired = timerExpired || remainingSeconds === 0;
    isRecording = false;
    finalizing = true;
    clearCountdown();
    stopWaveform();
    renderTimer();
    micBtn.classList.remove('recording');
    micBtn.classList.add('submitting');
    micBtn.disabled = true;
    micIcon.className = 'ti ti-loader';
    waveWrap.style.display = 'none';
    recStatus.textContent = timerExpired
        ? "Time's up! Submitting your reading..."
        : 'Finishing your recording...';
    micHint.textContent = 'Please wait...';
    try {
        mediaRecorder.stop();
    } catch (error) {
        recordingFailed = true;
        finalizing = false;
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        console.error('[ReadAloud] Could not stop recording:', error);
        showRecordingError('Could not finish the recording. Please try again.');
    }
}

function uploadErrorMessage(status, data) {
    if (status === 419) return 'Your session expired. Refresh this page and sign in again before recording.';
    if (status === 401) return 'Please sign in again, then reopen this activity.';
    if (status === 403 || status === 404) return 'This activity is no longer available to you. Return to your activities.';
    if (status === 409) return data?.message || 'You have already submitted this activity. Return to your activities.';
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
    if (submitting || isRecording || finalizing || completed || pageLeaving || !pendingBlob) return;
    submitting = true;
    micBtn.disabled = true;
    retryBtn.disabled = true;
    retryBtn.hidden = true;
    recStatus.textContent = timerExpired ? "Time's up! Submitting your reading..." : 'Submitting your reading...';
    document.getElementById('uploading-overlay').style.display = 'flex';
    try {
        const formData = new FormData(uploadForm);
        formData.set('recording', pendingBlob, 'recording.' + recordingExtension(pendingBlob.type));
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
            try { data = JSON.parse(rawText); } catch (_) { /* Treat as an unconfirmed upload. */ }
        }
        if (response.redirected || !response.ok || data?.status !== 'pending' || !data?.recording_id) {
            if (response.redirected) throw new Error('The upload was redirected. Your recording is still on this page. Sign in in another tab, then retry.');
            if (!response.ok) throw new Error(uploadErrorMessage(response.status, data));
            throw new Error('The server did not confirm your recording. Please retry the upload.');
        }

        completed = true;
        pendingBlob = null;
        audioChunks = [];
        recStatus.textContent = 'Completed! Your reading was sent to your teacher.';
        document.getElementById('uploading-overlay').style.display = 'none';
        showSuccessPopup();
    } catch (error) {
        console.error('[ReadAloud] Upload failed:', error);
        if (!pageLeaving) showRecordingError(error instanceof TypeError
            ? 'Could not reach the upload server. Your audio is kept here. Check your connection and retry the upload.'
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
    successInterval = setInterval(() => {
        remaining--;
        numEl.textContent = remaining;
        if (remaining <= 0) {
            clearInterval(successInterval);
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


function finishTouchRecording() {
    if (touchRecording && holdActive) stopRecording();
}

function cleanupRecording() {
    pageLeaving = true;
    recordingFailed = true;
    isRecording = false;
    holdActive = false;
    clearCountdown();
    clearInterval(successInterval);
    stopWaveform();
    if (mediaRecorder) {
        if (mediaRecorder.state === 'recording') {
            try { mediaRecorder.stop(); } catch (_) { /* The page is leaving. */ }
        }
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
}

micBtn.addEventListener('click', toggleRecording);
micBtn.addEventListener('touchstart', startRecording, { passive: false });
micBtn.addEventListener('touchend', stopRecording, { passive: false });
micBtn.addEventListener('touchcancel', stopRecording, { passive: false });
micBtn.addEventListener('contextmenu', event => event.preventDefault());
retryBtn.addEventListener('click', submitRecording);
window.addEventListener('touchend', finishTouchRecording);
window.addEventListener('touchcancel', finishTouchRecording);
window.addEventListener('blur', finishTouchRecording);
window.addEventListener('focus', updateCountdown);
document.addEventListener('visibilitychange', updateCountdown);
window.addEventListener('pagehide', cleanupRecording);
window.addEventListener('beforeunload', cleanupRecording);

resetRecordingUi();
if (canRecord) recStatus.textContent = 'Ready to Read. ' + idleRecordingHint() + '.';
})();
