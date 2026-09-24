<section class="rk-card">
    <div class="rk-card-heading"><h2><i class="ti ti-chart-line" aria-hidden="true"></i> Assessment Trend</h2><span class="rk-pill">Last 4 weeks</span></div>
    <p class="rk-card-subtitle">Weekly average scores · current week through today</p>
    <svg class="ra-trend" viewBox="0 0 520 235" role="img" aria-label="Oral reading and comprehension scores for the last four calendar weeks. Exact values are in the table below.">
        @foreach([0, 25, 50, 75, 100] as $tick)
        <line x1="42" y1="{{ 190 - $tick * 1.6 }}" x2="482" y2="{{ 190 - $tick * 1.6 }}" stroke="#e4edf8"/>
        <text x="33" y="{{ 194 - $tick * 1.6 }}" text-anchor="end">{{ $tick }}%</text>
        @endforeach
        @foreach($assessment['trend'] as $i => $period)
        <text x="{{ 62 + $i * 132 }}" y="218" text-anchor="middle">{{ $period['label'] }}</text>
        @endforeach
        @foreach(['oral' => '#1686ff', 'comprehension' => '#18b779'] as $series => $color)
            @foreach($assessment['trend'] as $i => $period)
                @if($period[$series] !== null)
                    @if($i > 0 && $assessment['trend'][$i - 1][$series] !== null)
                    <line x1="{{ 62 + ($i - 1) * 132 }}" y1="{{ 190 - $assessment['trend'][$i - 1][$series] * 1.6 }}" x2="{{ 62 + $i * 132 }}" y2="{{ 190 - $period[$series] * 1.6 }}" stroke="{{ $color }}" stroke-width="3"/>
                    @endif
                    <circle cx="{{ 62 + $i * 132 }}" cy="{{ 190 - $period[$series] * 1.6 }}" r="5" fill="{{ $color }}"><title>{{ $series === 'oral' ? 'Oral Reading' : 'Comprehension' }} · {{ $period['period'] }}: {{ $period[$series] }}%</title></circle>
                @endif
            @endforeach
        @endforeach
    </svg>
    <div class="ra-chart-legend"><span><i style="background:#1686ff"></i> Oral Reading</span><span><i style="background:#18b779"></i> Comprehension</span></div>
    <details class="ra-chart-data"><summary>View scores by week</summary><div class="rk-table-scroll"><table class="rk-table"><thead><tr><th>Week</th><th>Oral Reading</th><th>Comprehension</th></tr></thead><tbody>@foreach($assessment['trend'] as $period)<tr><td>{{ $period['period'] }}</td><td>{{ $period['oral'] === null ? '—' : number_format($period['oral'], 2).'%' }}</td><td>{{ $period['comprehension'] === null ? '—' : number_format($period['comprehension'], 2).'%' }}</td></tr>@endforeach</tbody></table></div></details>
    <p class="rk-footnote">Missing scores appear as gaps, not zero. Dates reflect the initial evaluation; edits update that evaluation’s scores.</p>
</section>
