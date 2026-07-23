@extends('layouts.student')
@section('title', 'Leaderboard')
@section('page-greet', 'Class Leaderboard 🏆')
@section('page-sub', 'See where you rank among your classmates!')

@section('content')

{{-- My rank banner --}}
<div style="background:linear-gradient(135deg,#185FA5,#2563EB);border-radius:14px;
            padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;gap:16px;">
    <div style="font-size:48px;">
        @if($myRank == 1) 🥇
        @elseif($myRank == 2) 🥈
        @elseif($myRank == 3) 🥉
        @else 🏅
        @endif
    </div>
    <div>
        <div style="font-size:13px;color:rgba(255,255,255,0.8);margin-bottom:2px;">Your current rank</div>
        <div style="font-size:28px;font-weight:700;color:#fff;">#{{ $myRank }} in class</div>
        <div style="font-size:12px;color:rgba(255,255,255,0.8);">
            {{ number_format($student->total_points) }} points · Level {{ $student->current_level }}
        </div>
    </div>
    @if($myRank > 1)
    @php $above = $classmates->get($myRank - 2); @endphp
    <div style="margin-left:auto;background:rgba(255,255,255,0.15);border-radius:10px;padding:10px 14px;text-align:center;">
        <div style="font-size:10px;color:rgba(255,255,255,0.8);">Points needed to beat</div>
        <div style="font-size:13px;font-weight:700;color:#fff;">{{ $above->firstname }}</div>
        <div style="font-size:18px;font-weight:700;color:#FCD34D;">
            +{{ number_format($above->total_points - $student->total_points + 1) }} pts
        </div>
    </div>
    @endif
</div>

{{-- Top 3 podium --}}
@if($classmates->count() >= 3)
<div class="dash-card mb-4">
    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:16px;text-align:center;">🏆 Top 3</div>
    <div class="d-flex align-items-end justify-content-center gap-3">
        {{-- 2nd place --}}
        @php $second = $classmates->get(1); @endphp
        <div class="text-center">
            <div style="width:52px;height:52px;border-radius:50%;background:#E5E7EB;color:#374151;
                        display:flex;align-items:center;justify-content:center;
                        font-size:16px;font-weight:700;margin:0 auto 6px;
                        {{ $second->id == $student->id ? 'border:3px solid #185FA5;' : '' }}">
                {{ strtoupper(substr($second->firstname,0,1).substr($second->lastname,0,1)) }}
            </div>
            <div style="font-size:11px;font-weight:600;color:#111827;">{{ $second->firstname }}</div>
            <div style="font-size:10px;color:#F59E0B;font-weight:700;">{{ number_format($second->total_points) }} pts</div>
            <div style="background:#9CA3AF;border-radius:6px 6px 0 0;height:60px;
                        width:70px;display:flex;align-items:center;justify-content:center;
                        font-size:22px;margin-top:8px;">🥈</div>
        </div>

        {{-- 1st place --}}
        @php $first = $classmates->get(0); @endphp
        <div class="text-center">
            <div style="width:60px;height:60px;border-radius:50%;background:#FEF3C7;color:#92400E;
                        display:flex;align-items:center;justify-content:center;
                        font-size:18px;font-weight:700;margin:0 auto 6px;
                        border:3px solid #F59E0B;
                        {{ $first->id == $student->id ? 'border-color:#185FA5;' : '' }}">
                {{ strtoupper(substr($first->firstname,0,1).substr($first->lastname,0,1)) }}
            </div>
            <div style="font-size:12px;font-weight:700;color:#111827;">{{ $first->firstname }}</div>
            <div style="font-size:11px;color:#F59E0B;font-weight:700;">{{ number_format($first->total_points) }} pts</div>
            <div style="background:#F59E0B;border-radius:6px 6px 0 0;height:80px;
                        width:70px;display:flex;align-items:center;justify-content:center;
                        font-size:26px;margin-top:8px;">🥇</div>
        </div>

        {{-- 3rd place --}}
        @php $third = $classmates->get(2); @endphp
        <div class="text-center">
            <div style="width:48px;height:48px;border-radius:50%;background:#FEE2E2;color:#991B1B;
                        display:flex;align-items:center;justify-content:center;
                        font-size:14px;font-weight:700;margin:0 auto 6px;
                        {{ $third->id == $student->id ? 'border:3px solid #185FA5;' : '' }}">
                {{ strtoupper(substr($third->firstname,0,1).substr($third->lastname,0,1)) }}
            </div>
            <div style="font-size:11px;font-weight:600;color:#111827;">{{ $third->firstname }}</div>
            <div style="font-size:10px;color:#F59E0B;font-weight:700;">{{ number_format($third->total_points) }} pts</div>
            <div style="background:#CD7C0A;border-radius:6px 6px 0 0;height:44px;
                        width:70px;display:flex;align-items:center;justify-content:center;
                        font-size:20px;margin-top:8px;">🥉</div>
        </div>
    </div>
</div>
@endif

{{-- Full leaderboard --}}
<div class="dash-card">
    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:12px;">📋 Full Rankings</div>
    @foreach($classmates as $i => $s)
    @php $isMe = $s->id === $student->id; @endphp
    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;margin-bottom:6px;
                {{ $isMe ? 'background:#EFF6FF;border:1.5px solid #BFDBFE;' : 'background:#F9FAFB;border:1px solid #E5E7EB;' }}">
        {{-- Rank --}}
        <div style="width:28px;text-align:center;font-size:{{ $i < 3 ? '18' : '12' }}px;flex-shrink:0;
                    color:{{ $i >= 3 ? '#9CA3AF' : '' }}">
            @if($i == 0) 🥇
            @elseif($i == 1) 🥈
            @elseif($i == 2) 🥉
            @else #{{ $i + 1 }}
            @endif
        </div>

        {{-- Avatar --}}
        <div style="width:36px;height:36px;border-radius:50%;flex-shrink:0;
                    background:{{ $isMe ? '#DBEAFE' : '#F3F4F6' }};
                    color:{{ $isMe ? '#1E40AF' : '#374151' }};
                    display:flex;align-items:center;justify-content:center;
                    font-size:13px;font-weight:700;">
            {{ strtoupper(substr($s->firstname,0,1).substr($s->lastname,0,1)) }}
        </div>

        {{-- Name --}}
        <div style="flex:1;">
            <div style="font-size:13px;font-weight:{{ $isMe ? '700' : '600' }};
                        color:{{ $isMe ? '#1E40AF' : '#111827' }};">
                {{ $s->firstname }} {{ $s->lastname }}
                @if($isMe)<span style="font-size:10px;background:#DBEAFE;color:#1E40AF;
                                       padding:1px 6px;border-radius:10px;margin-left:4px;">You</span>@endif
            </div>
            <div style="font-size:10px;color:#9CA3AF;">Level {{ $s->current_level }} · {{ $s->section }}</div>
        </div>

        {{-- Points --}}
        <div style="font-size:14px;font-weight:700;color:#F59E0B;">
            ⭐ {{ number_format($s->total_points) }}
        </div>
    </div>
    @endforeach
</div>

@endsection