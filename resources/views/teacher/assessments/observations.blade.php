<section class="rk-card">
    <div class="rk-card-heading"><h2><i class="ti ti-file-description" aria-hidden="true"></i> Observation Level Distribution</h2></div>
    <p class="rk-card-subtitle">Teacher observations · {{ $assessment['observationTotal'] }} evaluations</p>
    <div class="ra-distribution">
    @foreach($assessment['observations'] as $level => $count)
        @php $percentage = $assessment['observationTotal'] ? round($count / $assessment['observationTotal'] * 100, 1) : 0; @endphp
        <div class="ra-distribution-row ra-level-{{ $level }}"><div><strong>Level {{ $level }}</strong><span>{{ $count }} ({{ $percentage }}%)</span></div><small>{{ $assessment['observationLabels'][$level] }}</small><div class="rk-progress"><span style="width:{{ $percentage }}%"></span></div></div>
    @endforeach
    </div>
    @if(!$assessment['observationTotal'])<p class="rk-footnote">No observation levels recorded yet.</p>@endif
</section>
