@extends('layouts.teacher')
@section('title', 'Evaluations')
@section('page-title', 'Manual Reading Evaluation')
@section('page-sub', 'Listen to student recordings and provide feedback.')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3">

    {{-- Pending evaluations --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">
                Pending Evaluations
                <span class="status-badge badge-amber">{{ $pending->count() }}</span>
            </div>

            @forelse($pending as $recording)
            @php
                $initials = strtoupper(
                    substr($recording->student->firstname, 0, 1) .
                    substr($recording->student->lastname, 0, 1)
                );
            @endphp
            <div class="d-flex align-items-center gap-10 p-2 mb-2 rounded"
                 style="border:1px solid #E5E7EB; background:#F9FAFB; gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:#FEF3C7;color:#92400E;
                            display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:700;flex-shrink:0;">
                    {{ $initials }}
                </div>
                <div class="flex-grow-1">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $recording->student->firstname }} {{ $recording->student->lastname }}
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;">
                        {{ $recording->activity->activity_name }} ·
                        Attempt {{ $recording->attempt_number }} ·
                        {{ \Carbon\Carbon::parse($recording->created_at)->diffForHumans() }}
                    </div>
                </div>
                <a href="{{ route('teacher.evaluations.show', $recording->id) }}"
                   class="btn btn-sm btn-primary" style="white-space:nowrap;">
                    <i class="ti ti-player-play"></i> Evaluate
                </a>
            </div>
            @empty
            <div class="text-center py-4">
                <div style="font-size:36px;">🎉</div>
                <div class="text-muted small mt-2">No pending evaluations!</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recently evaluated --}}
    <div class="col-md-6">
        <div class="dash-card">
            <div class="dash-card-title">Recently Evaluated</div>
            @forelse($evaluated as $recording)
            @php
                $avg = $recording->evaluation
                    ? round((
                        $recording->evaluation->pronunciation_score +
                        $recording->evaluation->fluency_score +
                        $recording->evaluation->accuracy_score +
                        $recording->evaluation->comprehension_score
                      ) / 4 * 20, 1)
                    : 0;
            @endphp
            <div class="d-flex align-items-center gap-10 p-2 mb-2 rounded"
                 style="border:1px solid #E5E7EB; background:#F9FAFB; gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:#DCFCE7;color:#166534;
                            display:flex;align-items:center;justify-content:center;
                            font-size:13px;font-weight:700;flex-shrink:0;">
                    {{ strtoupper(substr($recording->student->firstname,0,1).substr($recording->student->lastname,0,1)) }}
                </div>
                <div class="flex-grow-1">
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $recording->student->firstname }} {{ $recording->student->lastname }}
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;">
                        {{ $recording->activity->activity_name }} · Score: {{ $avg }}%
                    </div>
                </div>
                <span class="status-badge badge-green">Evaluated ✓</span>
            </div>
            @empty
            <div class="text-center text-muted small py-4">No evaluated recordings yet.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection