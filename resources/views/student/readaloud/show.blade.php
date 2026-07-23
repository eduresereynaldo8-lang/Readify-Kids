@extends('layouts.student')
@section('title', 'Read Aloud')
@section('page-greet', '🎙️ ' . $activity->activity_name)
@section('page-sub', 'Read the passage clearly and record yourself!')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Step indicator --}}
<div class="dash-card mb-3">
    <div class="d-flex align-items-center gap-0">
        @php
            $steps = ['Read Passage', 'Record Yourself', 'Submit', 'Wait for Feedback'];
            $current = $recordings->count() > 0 ? 3 : 1;
        @endphp
        @foreach($steps as $i => $step)
        <div class="d-flex align-items-center" style="flex:1;">
            <div class="d-flex flex-column align-items-center" style="min-width:60px;">
                <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;
                            justify-content:center;font-size:11px;font-weight:700;
                            background:{{ $i < $current ? '#185FA5' : ($i == $current ? '#DBEAFE' : '#F3F4F6') }};
                            color:{{ $i < $current ? '#fff' : ($i == $current ? '#185FA5' : '#9CA3AF') }};">
                    {{ $i < $current ? '✓' : $i + 1 }}
                </div>
                <div style="font-size:10px;color:{{ $i == $current ? '#185FA5' : '#9CA3AF' }};
                            font-weight:{{ $i == $current ? '600' : '400' }};margin-top:4px;text-align:center;">
                    {{ $step }}
                </div>
            </div>
            @if(!$loop->last)
            <div style="flex:1;height:2px;background:{{ $i < $current ? '#185FA5' : '#E5E7EB' }};margin-bottom:18px;"></div>
            @endif
        </div>
        @endforeach
    </div>
</div>

<div class="row g-3">

    {{-- Left: passage + recording --}}
    <div class="col-md-7">

        {{-- Activity info --}}
        <div style="background:linear-gradient(135deg,#185FA5,#2563EB);border-radius:14px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;gap:14px;">
            <div style="font-size:40px;">🎙️</div>
            <div>
                <div style="font-size:14px;font-weight:700;color:#fff;margin-bottom:3px;">{{ $activity->activity_name }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.8);margin-bottom:8px;">{{ $activity->description }}</div>
                <div class="d-flex gap-2 flex-wrap">
                    <span style="font-size:10px;padding:2px 10px;border-radius:20px;background:rgba(255,255,255,0.2);color:#fff;">Level {{ $activity->level }}</span>
                    <span style="font-size:10px;padding:2px 10px;border-radius:20px;background:rgba(255,255,255,0.2);color:#fff;">⏱ {{ $activity->duration_minutes }} min</span>
                    <span style="font-size:10px;padding:2px 10px;border-radius:20px;background:#F59E0B;color:#fff;font-weight:600;">⭐ +{{ $activity->points_reward }} pts</span>
                </div>
            </div>
        </div>

        {{-- Reading passage --}}
        <div class="dash-card mb-3">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:10px;">
                📄 Reading Passage
            </div>

            @if($activity->readingMaterial && $activity->readingMaterial->content)
            <div style="background:#F8FAFF;border:1px solid #DBEAFE;border-left:4px solid #185FA5;
                        border-radius:8px;padding:16px;font-size:14px;line-height:2.2;
                        color:#1E3A5F;letter-spacing:0.01em;">
                {{ $activity->readingMaterial->content }}
            </div>
            <div class="d-flex gap-3 mt-2" style="font-size:11px;color:#9CA3AF;">
                <span>📝 {{ str_word_count($activity->readingMaterial->content) }} words</span>
                <span>⏱ Read slowly and clearly</span>
                <span>🔊 Speak loud enough to be heard</span>
            </div>
            @else
            <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:8px;padding:14px;font-size:13px;color:#92400E;">
                ⚠️ No reading passage has been added to this activity yet. Ask your teacher to add one!
            </div>
            @endif
        </div>

        {{-- Recording section --}}
        <div class="dash-card">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">
                🎙️ Record Your Voice
            </div>

            {{-- Waveform display --}}
            <div id="waveform-display" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;
                                              padding:16px;display:flex;flex-direction:column;
                                              align-items:center;gap:12px;margin-bottom:14px;">
                <div id="waveform-bars" style="display:flex;align-items:center;gap:3px;height:48px;">
                    @for($i = 0; $i < 20; $i++)
                    <div class="wv-bar" style="width:5px;border-radius:3px;background:#E5E7EB;height:10px;transition:height 0.1s;"></div>
                    @endfor
                </div>
                <div id="rec-timer" style="font-size:28px;font-weight:700;color:#111827;font-variant-numeric:tabular-nums;">0:00</div>
                <div id="rec-status" style="font-size:12px;color:#9CA3AF;">Tap the button below to start recording</div>
            </div>

            {{-- Record button --}}
            <div class="text-center mb-3">
                <button id="rec-btn" type="button"
                        style="width:72px;height:72px;border-radius:50%;background:#EF4444;
                               border:none;cursor:pointer;box-shadow:0 0 0 10px rgba(239,68,68,0.15);
                               display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="ti ti-microphone" style="color:#fff;font-size:30px;"></i>
                </button>
                <div style="font-size:11px;color:#9CA3AF;margin-top:8px;" id="rec-btn-label">Tap to record</div>
            </div>

            {{-- Playback --}}
            <div id="playback-section" style="display:none;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Your recording</div>
                <audio id="playback-audio" controls style="width:100%;border-radius:8px;"></audio>
            </div>

            {{-- Submit form --}}
            <form id="upload-form" method="POST"
                  action="{{ route('student.readaloud.upload', $activity->id) }}"
                  enctype="multipart/form-data">
                @csrf
                <input type="file" name="recording" id="recording-file" style="display:none;" accept="audio/*">

                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" id="re-record-btn"
                            style="display:none;"
                            class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-refresh"></i> Re-record
                    </button>
                    <button type="submit" id="submit-btn"
                            style="display:none;"
                            class="btn btn-sm btn-primary">
                        <i class="ti ti-send"></i> Submit to Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Right: tips + previous attempts --}}
    <div class="col-md-5">

        {{-- Reading tips --}}
        <div class="dash-card mb-3">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:10px;">💡 Reading Tips</div>
            <div class="d-flex flex-column gap-2">
                @foreach([
                    ['🐢', 'Read slowly — don\'t rush through the words.'],
                    ['🔊', 'Speak clearly and loudly so your teacher can hear you.'],
                    ['✋', 'Pause at commas and full stops.'],
                    ['👁️', 'Look at each word carefully before saying it.'],
                    ['🔁', 'You can re-record as many times as you need before submitting!'],
                ] as [$icon, $tip])
                <div class="d-flex gap-2 align-items-start" style="font-size:12px;color:#6B7280;">
                    <span style="font-size:16px;flex-shrink:0;">{{ $icon }}</span>
                    <span>{{ $tip }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- What you can earn --}}
        <div class="dash-card mb-3" style="background:#FFFBF0;border-color:#FDE68A;">
            <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:10px;">🏆 What you can earn</div>
            <div class="d-flex flex-column gap-2">
                <div class="d-flex align-items-center gap-2" style="font-size:12px;color:#6B7280;">
                    <span style="font-size:18px;">⭐</span> +{{ $activity->points_reward }} points on completion
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size:12px;color:#6B7280;">
                    <span style="font-size:18px;">🎙️</span> Badge for completing Read Aloud
                </div>
                <div class="d-flex align-items-center gap-2" style="font-size:12px;color:#6B7280;">
                    <span style="font-size:18px;">📝</span> Personal feedback from your teacher
                </div>
            </div>
        </div>

        {{-- Previous attempts --}}
        <div class="dash-card">
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:10px;">
                📋 Previous Attempts ({{ $recordings->count() }})
            </div>
            @forelse($recordings as $i => $rec)
            <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;
                        padding:10px 12px;margin-bottom:8px;">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span style="font-size:11px;color:#6B7280;">Attempt {{ $rec->attempt_number }}</span>
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;font-weight:600;
                                 {{ $rec->status === 'evaluated' ? 'background:#DCFCE7;color:#166534;' : 'background:#FEF3C7;color:#92400E;' }}">
                        {{ $rec->status === 'evaluated' ? 'Evaluated ✓' : 'Pending…' }}
                    </span>
                </div>
                <div style="font-size:11px;color:#9CA3AF;margin-bottom:8px;">
                    {{ \Carbon\Carbon::parse($rec->created_at)->format('M d, Y — g:i A') }}
                </div>
                <audio controls style="width:100%;height:32px;">
                    <source src="{{ asset('storage/' . $rec->recording_path) }}" type="audio/webm">
                </audio>

                {{-- Teacher feedback --}}
                @if($rec->evaluation)
                <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;
                            padding:10px;margin-top:8px;">
                    <div style="font-size:11px;font-weight:700;color:#1E40AF;margin-bottom:4px;">
                        📝 Teacher's Feedback
                    </div>
                    <div class="d-flex gap-3 mb-2 flex-wrap" style="font-size:10px;color:#6B7280;">
                        <span>Pronunciation: {{ $rec->evaluation->pronunciation_score }}/5 ⭐</span>
                        <span>Fluency: {{ $rec->evaluation->fluency_score }}/5 ⭐</span>
                        <span>Accuracy: {{ $rec->evaluation->accuracy_score }}/5 ⭐</span>
                        <span>Comprehension: {{ $rec->evaluation->comprehension_score }}/5 ⭐</span>
                    </div>
                    <div style="font-size:11px;color:#1E40AF;font-weight:600;margin-bottom:2px;">
                        Level: {{ $rec->evaluation->proficiency_level }}
                    </div>
                    @if($rec->evaluation->feedback)
                    <div style="font-size:12px;color:#374151;line-height:1.5;margin-top:4px;">
                        "{{ $rec->evaluation->feedback }}"
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @empty
            <div class="text-center text-muted small py-3">No recordings yet. Record your first attempt!</div>
            @endforelse
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
let mediaRecorder;
let audioChunks = [];
let isRecording = false;
let timerInterval;
let seconds = 0;
let audioBlob;

const recBtn      = document.getElementById('rec-btn');
const recStatus   = document.getElementById('rec-status');
const recTimer    = document.getElementById('rec-timer');
const recBtnLabel = document.getElementById('rec-btn-label');
const playbackSec = document.getElementById('playback-section');
const playbackAudio = document.getElementById('playback-audio');
const reRecordBtn = document.getElementById('re-record-btn');
const submitBtn   = document.getElementById('submit-btn');
const recFileInput= document.getElementById('recording-file');
const wvBars      = document.querySelectorAll('.wv-bar');

recBtn.addEventListener('click', async () => {
    if (!isRecording) {
        // Start recording
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

            mediaRecorder.onstop = () => {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const url = URL.createObjectURL(audioBlob);
                playbackAudio.src = url;
                playbackSec.style.display = 'block';
                reRecordBtn.style.display = 'inline-flex';
                submitBtn.style.display   = 'inline-flex';

                // Attach blob to file input
                const file = new File([audioBlob], 'recording.webm', { type: 'audio/webm' });
                const dt   = new DataTransfer();
                dt.items.add(file);
                recFileInput.files = dt.files;

                stopWaveform();
            };

            mediaRecorder.start();
            isRecording = true;

            // Update UI
            recBtn.style.background    = '#1E40AF';
            recBtn.style.boxShadow     = '0 0 0 10px rgba(30,64,175,0.15)';
            recBtn.innerHTML           = '<i class="ti ti-player-stop" style="color:#fff;font-size:28px;"></i>';
            recBtnLabel.textContent    = 'Tap to stop';
            recStatus.textContent      = '🔴 Recording…';
            playbackSec.style.display  = 'none';
            reRecordBtn.style.display  = 'none';
            submitBtn.style.display    = 'none';

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
            alert('Microphone access denied. Please allow microphone access to record.');
        }
    } else {
        // Stop recording
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(t => t.stop());
        isRecording = false;
        clearInterval(timerInterval);

        recBtn.style.background  = '#EF4444';
        recBtn.style.boxShadow   = '0 0 0 10px rgba(239,68,68,0.15)';
        recBtn.innerHTML         = '<i class="ti ti-microphone" style="color:#fff;font-size:30px;"></i>';
        recBtnLabel.textContent  = 'Tap to record again';
        recStatus.textContent    = '✅ Recording complete! Listen back or submit.';
    }
});

// Re-record button
reRecordBtn.addEventListener('click', () => {
    audioBlob = null;
    playbackSec.style.display = 'none';
    reRecordBtn.style.display = 'none';
    submitBtn.style.display   = 'none';
    recTimer.textContent      = '0:00';
    recStatus.textContent     = 'Tap the button below to start recording';
    recBtnLabel.textContent   = 'Tap to record';
    recBtn.innerHTML          = '<i class="ti ti-microphone" style="color:#fff;font-size:30px;"></i>';
    stopWaveform();
});

// Waveform animation
let waveInterval;
function animateWaveform() {
    const heights = [10,18,28,36,24,14,32,20,28,16,24,30,12,26,20,34,16,22,28,10];
    let t = 0;
    waveInterval = setInterval(() => {
        wvBars.forEach((bar, i) => {
            const h = Math.abs(Math.sin((t + i) * 0.4)) * 36 + 8;
            bar.style.height = h + 'px';
            bar.style.background = '#185FA5';
        });
        t++;
    }, 100);
}

function stopWaveform() {
    clearInterval(waveInterval);
    wvBars.forEach(bar => {
        bar.style.height = '10px';
        bar.style.background = '#E5E7EB';
    });
}
</script>
@endpush