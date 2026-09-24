<section class="rk-card">
    <div class="rk-card-heading"><h2><i class="ti ti-chart-bar" aria-hidden="true"></i> Reading Assessment Overview</h2></div>
    <p class="rk-card-subtitle">Average results from the new manual reading rubric</p>
    <div class="ra-metric-grid">
        <div class="ra-metric rk-tone-blue"><span class="rk-skill-icon"><i class="ti ti-microphone" aria-hidden="true"></i></span><h3>Oral Reading Score</h3><strong>{{ $assessment['averages']['oral'] === null ? '—' : number_format($assessment['averages']['oral'], 2).'%' }}</strong><div class="rk-progress"><span style="width:{{ $assessment['averages']['oral'] ?? 0 }}%"></span></div><small>Average score</small></div>
        <div class="ra-metric rk-tone-green"><span class="rk-skill-icon"><i class="ti ti-brain" aria-hidden="true"></i></span><h3>Comprehension Score</h3><strong>{{ $assessment['averages']['comprehension'] === null ? '—' : number_format($assessment['averages']['comprehension'], 2).'%' }}</strong><div class="rk-progress"><span style="width:{{ $assessment['averages']['comprehension'] ?? 0 }}%"></span></div><small>Average score</small></div>
        <div class="ra-metric rk-tone-purple"><span class="rk-skill-icon"><i class="ti ti-clock" aria-hidden="true"></i></span><h3>Reading Time</h3><strong>{{ $assessment['readingTime'] }}</strong><small>Average per reading</small></div>
        <div class="ra-metric rk-tone-yellow"><span class="rk-skill-icon"><i class="ti ti-book" aria-hidden="true"></i></span><h3>Miscues</h3><strong>{{ $assessment['averages']['miscues'] === null ? '—' : number_format($assessment['averages']['miscues'], 1) }}</strong><small>Average per reading</small></div>
    </div>
</section>
