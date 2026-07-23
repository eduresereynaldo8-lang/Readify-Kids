@extends('layouts.student')
@section('title', 'My Activities')
@section('page-greet', 'My Activities 📖')
@section('page-sub', 'Pick an activity and start earning points!')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Tab filter --}}
<div class="d-flex gap-2 mb-4 flex-wrap">
    <button class="tab-filter-btn active-tab" data-type="all"
            style="font-size:12px;padding:6px 18px;border-radius:20px;border:1px solid #185FA5;
                   background:#185FA5;color:#fff;cursor:pointer;font-weight:600;">
        All
    </button>
    @foreach($grouped->keys() as $type)
    <button class="tab-filter-btn" data-type="{{ Str::slug($type) }}"
            style="font-size:12px;padding:6px 18px;border-radius:20px;border:1px solid #E5E7EB;
                   background:#fff;color:#6B7280;cursor:pointer;">
        {{ $type }}
    </button>
    @endforeach
</div>

@php
$typeConfig = [
    'Read Aloud'      => ['emoji'=>'🎙️','bg'=>'#FEF3C7','route'=>'student.readaloud.show'],
    'Phonics'         => ['emoji'=>'🔤','bg'=>'#DBEAFE','route'=>'student.activities.show'],
    'Word Game'       => ['emoji'=>'🧩','bg'=>'#EDE9FE','route'=>'student.activities.show'],
    'Vocabulary'      => ['emoji'=>'📝','bg'=>'#DCFCE7','route'=>'student.activities.show'],
    'Word Recognition'=> ['emoji'=>'👀','bg'=>'#FFE4E6','route'=>'student.activities.show'],
    'Sound Blending'  => ['emoji'=>'🔊','bg'=>'#E0F2FE','route'=>'student.activities.show'],
];
@endphp

@foreach($grouped as $type => $typeActivities)
@php $cfg = $typeConfig[$type] ?? ['emoji'=>'📖','bg'=>'#F3F4F6','route'=>'student.activities.show']; @endphp

<div class="activity-section mb-4" data-section="{{ Str::slug($type) }}">

    {{-- Section header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <div style="width:32px;height:32px;border-radius:8px;background:{{ $cfg['bg'] }};
                    display:flex;align-items:center;justify-content:center;font-size:16px;">
            {{ $cfg['emoji'] }}
        </div>
        <div>
            <div style="font-size:14px;font-weight:700;color:#111827;">{{ $type }}</div>
            <div style="font-size:11px;color:#9CA3AF;">{{ $typeActivities->count() }} {{ Str::plural('activity', $typeActivities->count()) }}</div>
        </div>
    </div>

    {{-- Activity cards --}}
    <div class="row g-3">
        @foreach($typeActivities as $activity)
        @php
            $result  = $activity->results->first();
            $isDone  = $result && $result->status === 'completed';
            $inProg  = $result && $result->status === 'in_progress';
            $diffColor = match($activity->difficulty_level) {
                'Easy'   => '#22C55E',
                'Medium' => '#F59E0B',
                'Hard'   => '#EF4444',
                default  => '#9CA3AF'
            };
            $routeName = $type === 'Read Aloud'
                ? 'student.readaloud.show'
                : 'student.activities.show';
        @endphp
        <div class="col-md-6 col-lg-4 activity-card-wrap" data-type="{{ Str::slug($type) }}">
            <div class="dash-card h-100"
                 style="{{ $isDone ? 'border-color:#BBF7D0;' : ($inProg ? 'border-color:#FDE68A;' : '') }}">

                {{-- Card top --}}
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:{{ $cfg['bg'] }};
                                display:flex;align-items:center;justify-content:center;
                                font-size:24px;flex-shrink:0;">
                        {{ $cfg['emoji'] }}
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-size:13px;font-weight:700;color:#111827;line-height:1.3;">
                            {{ $activity->activity_name }}
                        </div>
                        @if($activity->description)
                        <div style="font-size:11px;color:#9CA3AF;margin-top:3px;line-height:1.4;">
                            {{ Str::limit($activity->description, 60) }}
                        </div>
                        @endif
                    </div>
                    {{-- Status badge --}}
                    @if($isDone)
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                                 background:#DCFCE7;color:#166534;font-weight:700;
                                 white-space:nowrap;align-self:flex-start;flex-shrink:0;">Done ✓</span>
                    @elseif($inProg)
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                                 background:#FEF3C7;color:#92400E;font-weight:700;
                                 white-space:nowrap;align-self:flex-start;flex-shrink:0;">In Progress</span>
                    @else
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                                 background:#DBEAFE;color:#1E40AF;font-weight:700;
                                 white-space:nowrap;align-self:flex-start;flex-shrink:0;">New</span>
                    @endif
                </div>

                {{-- Progress bar (if in progress) --}}
                @if($inProg && $result->score)
                <div style="margin-bottom:10px;">
                    <div class="d-flex justify-content-between" style="font-size:10px;color:#9CA3AF;margin-bottom:3px;">
                        <span>Progress</span><span>{{ $result->score }}%</span>
                    </div>
                    <div style="background:#E5E7EB;border-radius:4px;height:6px;">
                        <div style="height:6px;border-radius:4px;background:#F59E0B;width:{{ $result->score }}%;"></div>
                    </div>
                </div>
                @endif

                {{-- Done score --}}
                @if($isDone && $result->score)
                <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;
                            padding:6px 10px;margin-bottom:10px;display:flex;
                            align-items:center;justify-content:space-between;">
                    <span style="font-size:11px;color:#166534;font-weight:600;">Your Score</span>
                    <span style="font-size:14px;font-weight:700;color:#166534;">{{ $result->score }}%</span>
                </div>
                @endif

                {{-- Tags --}}
                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;background:#F3F4F6;color:#374151;display:flex;align-items:center;gap:3px;">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $diffColor }};display:inline-block;"></span>
                        {{ $activity->difficulty_level }}
                    </span>
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;background:#F3F4F6;color:#374151;">
                        ⏱ {{ $activity->duration_minutes }} min
                    </span>
                    <span style="font-size:10px;padding:2px 8px;border-radius:20px;background:#FEF9C3;color:#854D0E;font-weight:600;">
                        ⭐ {{ $activity->points_reward }} pts
                    </span>
                </div>

                {{-- Action button --}}
                <a href="{{ route($routeName, $activity->id) }}"
                   class="btn btn-sm w-100 {{ $isDone ? 'btn-outline-success' : 'btn-primary' }}">
                    @if($isDone)
                        <i class="ti ti-repeat"></i> Try Again
                    @elseif($inProg)
                        <i class="ti ti-player-play"></i> Continue
                    @else
                        <i class="ti ti-player-play"></i>
                        {{ $type === 'Read Aloud' ? 'Start Reading' : 'Start Activity' }}
                    @endif
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

@if($activities->isEmpty())
<div class="dash-card text-center py-5">
    <div style="font-size:48px;">📭</div>
    <div class="text-muted mt-2 fw-semibold">No activities available yet.</div>
    <div class="text-muted small">Ask your teacher to add some activities!</div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.querySelectorAll('.tab-filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update button styles
        document.querySelectorAll('.tab-filter-btn').forEach(b => {
            b.style.background = '#fff';
            b.style.color      = '#6B7280';
            b.style.borderColor= '#E5E7EB';
            b.classList.remove('active-tab');
        });
        this.style.background  = '#185FA5';
        this.style.color       = '#fff';
        this.style.borderColor = '#185FA5';
        this.classList.add('active-tab');

        const type = this.dataset.type;

        // Show/hide sections
        document.querySelectorAll('.activity-section').forEach(sec => {
            if (type === 'all' || sec.dataset.section === type) {
                sec.style.display = '';
            } else {
                sec.style.display = 'none';
            }
        });
    });
});
</script>
@endpush