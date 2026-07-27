@extends('layouts.student')
@section('title', 'Reading Battle')
@section('page-greet', '⚔️ Reading Battle')
@section('page-sub', 'Choose an activity and defeat the enemy with your reading skills!')

@section('content')

{{-- Banner --}}
<div style="background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:14px;
            padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;
            justify-content:space-between;">
    <div>
        <div style="font-size:18px;font-weight:700;color:#fff;margin-bottom:6px;">
            ⚔️ Reading Battle Arena
        </div>
        <div style="font-size:13px;color:rgba(255,255,255,0.85);max-width:400px;">
            Read words and passages aloud to attack enemies!
            The better you read, the more damage you deal!
        </div>
    </div>
    <div style="font-size:64px;">🏰</div>
</div>

{{-- How to play --}}
<div class="dash-card mb-4">
    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">📖 How to Play</div>
    <div class="row g-3 text-center">
        @foreach([
            ['⚔️','Choose a battle','Pick an activity to face its enemy'],
            ['🎙️','Read aloud','Read the word or passage shown on screen'],
            ['💥','Deal damage','Your reading score becomes your attack damage'],
            ['🏆','Win the battle','Reduce enemy HP to 0 to win points and badges!'],
        ] as [$icon,$title,$desc])
        <div class="col-6 col-md-3">
            <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px;">
                <div style="font-size:28px;margin-bottom:6px;">{{ $icon }}</div>
                <div style="font-size:12px;font-weight:700;color:#111827;">{{ $title }}</div>
                <div style="font-size:10px;color:#9CA3AF;margin-top:3px;">{{ $desc }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Activity battle cards --}}
<div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:14px;">
    ⚔️ Choose Your Battle
</div>

<div class="row g-3">
    @forelse($activities as $activity)
    @php
        $enemy    = $enemies->get($activity->level);
        $levelColors = [1=>'#22C55E', 2=>'#F59E0B', 3=>'#EF4444'];
        $levelBgs    = [1=>'#DCFCE7', 2=>'#FEF3C7', 3=>'#FEE2E2'];
        $lc = $levelColors[$activity->level] ?? '#9CA3AF';
        $lb = $levelBgs[$activity->level]    ?? '#F3F4F6';
    @endphp
    <div class="col-md-6 col-lg-4">
        <div class="dash-card h-100" style="border:2px solid #E5E7EB;transition:all 0.2s;"
             onmouseover="this.style.borderColor='#7C3AED';this.style.boxShadow='0 4px 20px rgba(124,58,237,0.15)'"
             onmouseout="this.style.borderColor='#E5E7EB';this.style.boxShadow=''">

            {{-- Enemy display --}}
            <div style="background:linear-gradient(135deg, #1E1B4B, #312E81);
                        border-radius:10px;padding:16px;text-align:center;margin-bottom:14px;">
                <div style="font-size:56px;margin-bottom:6px;">{{ $enemy?->sprite ?? '👾' }}</div>
                <div style="font-size:14px;font-weight:700;color:#fff;">{{ $enemy?->name ?? 'Enemy' }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.7);margin-bottom:10px;">
                    {{ $enemy?->description }}
                </div>

                {{-- Enemy HP bar --}}
                <div style="background:rgba(255,255,255,0.2);border-radius:6px;height:10px;margin-bottom:4px;">
                    <div style="background:#EF4444;height:10px;border-radius:6px;width:100%;"></div>
                </div>
                <div style="font-size:11px;color:rgba(255,255,255,0.8);font-weight:700;">
                    ❤️ {{ number_format($enemy?->max_hp ?? 500) }} HP
                </div>
            </div>

            {{-- Activity info --}}
            <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:4px;">
                {{ $activity->activity_name }}
            </div>
            <div style="font-size:11px;color:#9CA3AF;margin-bottom:10px;">
                {{ $activity->description }}
            </div>

            {{-- Tags --}}
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                             background:{{ $lb }};color:{{ $lc }};font-weight:700;">
                    Level {{ $activity->level }}
                </span>
                <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                             background:#EDE9FE;color:#5B21B6;font-weight:600;">
                    {{ $activity->activity_type }}
                </span>
                <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                             background:#FEF9C3;color:#854D0E;font-weight:600;">
                    ⭐ {{ $activity->points_reward }} pts
                </span>
            </div>

           {{-- What you'll read --}}
<div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:8px;
            padding:8px 10px;margin-bottom:12px;font-size:11px;color:#5B21B6;">
    📖 <strong>{{ $activity->wordBank->count() }}</strong> words/paragraphs to read ·
    <strong>
        @if($activity->level == 1) Single words
        @elseif($activity->level == 2) Short phrases
        @else Paragraphs
        @endif
    </strong>
</div>

           {{-- Session status + battle button --}}
@php
    $sess       = $sessionStatuses->get($activity->id);
    $sessStatus = $sess?->status ?? null;
@endphp

@if($sessStatus === 'won')
<div style="text-align:center;padding:8px;background:#DCFCE7;
            border-radius:10px;margin-bottom:8px;">
    <div style="font-size:12px;font-weight:700;color:#166534;">
        🏆 Defeated! You won this battle!
    </div>
    <div style="font-size:10px;color:#166534;">
        +{{ $sess->points_earned }} pts earned
    </div>
</div>
<a href="{{ route('student.game.start', $activity->id) }}"
   style="display:block;text-align:center;padding:9px;border-radius:10px;
          background:#DCFCE7;color:#166534;font-size:13px;
          font-weight:700;text-decoration:none;border:2px solid #22C55E;">
    🔄 Battle Again
</a>

@elseif($sessStatus === 'lost')
<div style="text-align:center;padding:8px;background:#FEE2E2;
            border-radius:10px;margin-bottom:8px;">
    <div style="font-size:12px;font-weight:700;color:#991B1B;">
        💀 You lost to {{ $enemies->get($activity->level)?->name ?? 'the enemy' }}!
    </div>
    <div style="font-size:10px;color:#991B1B;">
        The battle continues — try again!
    </div>
</div>
<a href="{{ route('student.game.start', $activity->id) }}"
   style="display:block;text-align:center;padding:9px;border-radius:10px;
          background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;
          font-size:13px;font-weight:700;text-decoration:none;">
    ⚔️ Try Again!
</a>

@elseif($sessStatus === 'ongoing')
<div style="text-align:center;padding:6px;background:#FEF3C7;
            border-radius:10px;margin-bottom:8px;
            font-size:11px;font-weight:600;color:#92400E;">
    ⚔️ Battle in progress — {{ $sess->rounds_played }} rounds played
</div>
<a href="{{ route('student.game.battle', $sess->id) }}"
   style="display:block;text-align:center;padding:9px;border-radius:10px;
          background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;
          font-size:13px;font-weight:700;text-decoration:none;">
    ▶️ Continue Battle
</a>

@else
<a href="{{ route('student.game.start', $activity->id) }}"
   style="display:block;text-align:center;padding:10px;border-radius:10px;
          background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;
          font-size:13px;font-weight:700;text-decoration:none;">
    ⚔️ Start Battle!
</a>
@endif
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="dash-card text-center py-5">
            <div style="font-size:48px;">🔒</div>
            <div class="text-muted mt-2 fw-semibold">No battles available yet.</div>
            <div class="text-muted small">Ask your teacher to add some activities!</div>
        </div>
    </div>
    @endforelse
</div>

@endsection