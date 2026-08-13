@extends('layouts.admin')
@section('title', 'Evaluations')
@section('page-title', 'View Evaluations')
@section('page-sub', 'All Read Aloud evaluations across the system.')

@section('content')

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#FEF3C7;">
                <span style="font-size:20px;">⏳</span>
            </div>
            <div>
                <div class="metric-label">Pending Evaluations</div>
                <div class="metric-value">{{ $pending }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DCFCE7;">
                <span style="font-size:20px;">✅</span>
            </div>
            <div>
                <div class="metric-label">Evaluated</div>
                <div class="metric-value">{{ $evaluated }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metric-card d-flex align-items-center gap-3">
            <div class="metric-icon" style="background:#DBEAFE;">
                <span style="font-size:20px;">🎙️</span>
            </div>
            <div>
                <div class="metric-label">Total Evaluations</div>
                <div class="metric-value">{{ $evaluations->count() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Evaluations table --}}
<div class="dash-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div style="font-size:13px;font-weight:600;color:#111827;">
            {{ $evaluations->count() }} evaluations total
        </div>
        <input type="text" id="searchInput" class="form-control form-control-sm"
               placeholder="Search student or activity…" style="width:220px;"
               onkeyup="filterTable()">
    </div>

    <table class="dash-table" id="evalTable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Activity</th>
                <th>Teacher</th>
                <th>Pronunciation</th>
                <th>Fluency</th>
                <th>Accuracy</th>
                <th>Comprehension</th>
                <th>Final Score</th>
                <th>Proficiency</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluations as $eval)
            @php
                $rec     = $eval->voiceRecording;
                $student = $rec?->student;
                $activity= $rec?->activity;
                $avg     = round(($eval->pronunciation_score + $eval->fluency_score + $eval->accuracy_score + $eval->comprehension_score) / 4 * 20, 1);
                $scoreColor = $avg >= 75 ? '#166534' : ($avg >= 50 ? '#92400E' : '#991B1B');
                $scoreBg    = $avg >= 75 ? '#DCFCE7' : ($avg >= 50 ? '#FEF3C7' : '#FEE2E2');
            @endphp
            <tr>
                <td>
                    <div style="font-size:12px;font-weight:600;color:#111827;">
                        {{ $student?->firstname }} {{ $student?->lastname }}
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">
                        {{ $student?->section }}
                    </div>
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $activity?->activity_name ?? '—' }}
                </td>
                <td style="font-size:11px;color:#6B7280;">
                    {{ $student?->teacher?->firstname }}
                    {{ $student?->teacher?->lastname }}
                </td>
                @foreach(['pronunciation_score','fluency_score','accuracy_score','comprehension_score'] as $field)
                <td>
                    <div style="display:flex;gap:1px;">
                        @for($s = 1; $s <= 5; $s++)
                        <span style="color:{{ $s <= $eval->$field ? '#F59E0B' : '#E5E7EB' }};font-size:12px;">★</span>
                        @endfor
                    </div>
                    <div style="font-size:10px;color:#9CA3AF;">{{ $eval->$field }}/5</div>
                </td>
                @endforeach
                <td>
                    <span style="background:{{ $scoreBg }};color:{{ $scoreColor }};
                                 font-size:12px;font-weight:700;
                                 padding:3px 10px;border-radius:20px;">
                        {{ $avg }}%
                    </span>
                </td>
                <td>
                    <span class="status-badge badge-blue" style="font-size:10px;">
                        {{ $eval->proficiency_level ?? '—' }}
                    </span>
                </td>
                <td style="font-size:11px;color:#9CA3AF;white-space:nowrap;">
                    {{ \Carbon\Carbon::parse($eval->created_at)->format('M d, Y') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    No evaluations yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
function filterTable() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#evalTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endpush