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
        color:#173E70; display:block; font-variant-numeric:tabular-nums;
        background:#EFF6FF; border:2px solid #B9D5F6; border-radius:12px; padding:6px 20px;
    }
.rk-reader .mic-timer.time-low { background:#FFF4D6; border-color:#B7791F; color:#704609; }
.rk-reader .reading-time { text-align:center; color:#173E70; font-weight:800; }
.rk-reader .time-limit { font-size:14px; margin-bottom:4px; }
.rk-reader .timer-label { font-size:13px; margin-bottom:3px; }
.rk-reader .mic-btn:disabled { animation:none; cursor:not-allowed; opacity:.65; }
.rk-reader .retry-upload { background:#173E70; color:white; border:0; border-radius:10px; padding:10px 18px; font-weight:800; }
.rk-reader .retry-upload[hidden] { display:none; }
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
<div class="rk-reader" id="read-aloud-recorder"
     data-duration-seconds="{{ $durationSeconds ?? 0 }}"
     data-can-record="{{ $canRecord ? 'true' : 'false' }}"
     data-index-url="{{ route('student.readaloud.index', [], false) }}"
     data-debug="{{ config('app.debug') ? 'true' : 'false' }}">


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
        @if($durationSeconds === null)
            This activity does not have a valid reading duration. Please contact your teacher.
        @elseif(!$canRecord)
            Your reading has already been submitted. Your teacher has not allowed another attempt.
        @else
            Ready to Read. Click the button below to start recording.
        @endif
    </div>

    {{-- Waveform --}}
    <div class="waveform-wrap" id="waveform-wrap">
        @for($i = 0; $i < 18; $i++)<div class="wv"></div>@endfor
    </div>

    {{-- Mic button --}}
    <div class="mic-wrap">
        <div class="reading-time">
            @if($durationSeconds !== null)
                <div class="time-limit">Time Limit: {{ $activity->duration_minutes }} {{ (int) $activity->duration_minutes === 1 ? 'minute' : 'minutes' }}</div>
            @endif
            <div class="timer-label" id="timer-label">Reading Time</div>
            <div class="mic-timer" id="mic-timer" role="timer" aria-live="off" aria-labelledby="timer-label">{{ $durationSeconds === null ? '--:--' : sprintf('%02d:%02d', intdiv($durationSeconds, 60), $durationSeconds % 60) }}</div>
        </div>
        <button type="button" id="mic-btn" class="mic-btn"
                aria-label="Start recording" aria-describedby="mic-hint" @disabled(!$canRecord)>
            <i class="ti ti-microphone" id="mic-icon"></i>
        </button>
        <button type="button" id="retry-upload" class="retry-upload" hidden>Retry Upload</button>
        <div class="mic-hint" id="mic-hint">Click to record, then click again to submit</div>
    </div>

    {{-- Hidden form --}}
    <form id="upload-form" method="POST"
          action="{{ route('student.readaloud.upload', $activity->id, false) }}"
          enctype="multipart/form-data" style="display:none;">
        @csrf
        <input type="hidden" name="recording_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    </form>

</div>


</div>
@endsection
@push('scripts')
<script src="{{ asset('js/read-aloud.js') }}" defer></script>
@endpush
