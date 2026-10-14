<section class="rk-card mb-4" aria-label="Level progress" style="padding:20px;">
    <h2 style="font-size:18px;margin:0 0 12px;">⭐ Level {{ $levelProgress['current_level'] }}</h2>
    @if($levelProgress['at_max_level'])
        <p style="margin:0;">Maximum level reached! Keep reading and collecting points.</p>
        <p style="margin:8px 0 0;">{{ number_format($levelProgress['total_points']) }} total points</p>
    @else
        <p>Next Level: {{ $levelProgress['next_level'] }}</p>
        <div style="display:flex;flex-wrap:wrap;gap:12px 32px;margin-bottom:12px;">
            <div>
                <strong>Points:</strong>
                {{ number_format($levelProgress['total_points']) }} / {{ number_format($levelProgress['required_points']) }}
                @if($levelProgress['points_met']) <span>— Reached!</span> @endif
            </div>
            <div>
                <strong>Level {{ $levelProgress['current_level'] }} Reading Average:</strong>
                @php
                    $average = $levelProgress['average_score'];
                    $averageLabel = $average === null ? 'N/A' : number_format($average, 2).'%';
                    // Never display a rounded passing score when the actual average is below it.
                    if ($average !== null && !$levelProgress['reading_met']
                        && round($average, 2) >= $levelProgress['required_score']) {
                        $averageLabel = '<'.number_format($levelProgress['required_score'], 0).'%';
                    }
                @endphp
                {{ $averageLabel }}
                / {{ number_format($levelProgress['required_score'], 0) }}%
                <span>— {{ $levelProgress['reading_met'] ? 'Passed!' : 'Keep practicing' }}</span>
            </div>
        </div>
        <div class="rk-progress" role="progressbar" aria-label="Points toward next level"
             aria-valuenow="{{ $levelProgress['points_percent'] }}" aria-valuemin="0" aria-valuemax="100">
            <span style="width:{{ $levelProgress['points_percent'] }}%"></span>
        </div>
        <p style="margin:12px 0 0;" role="status">
            @if($levelProgress['average_score'] === null)
                Complete a reading activity and receive a score to build your reading average.
                @if($levelProgress['points_met'])
                    Points requirement reached!
                @else
                    Earn {{ number_format($levelProgress['points_remaining']) }} more points to advance.
                @endif
            @elseif($levelProgress['points_met'] && !$levelProgress['reading_met'])
                Points requirement reached. Achieve a {{ number_format($levelProgress['required_score'], 0) }}% reading average to advance.
            @elseif($levelProgress['reading_met'] && !$levelProgress['points_met'])
                Reading requirement passed. Earn {{ number_format($levelProgress['points_remaining']) }} more points to advance.
            @elseif($levelProgress['eligible'])
                Both requirements reached! You are ready for the next level.
            @else
                Earn {{ number_format($levelProgress['points_remaining']) }} more points and reach a {{ number_format($levelProgress['required_score'], 0) }}% reading average to advance.
            @endif
        </p>
    @endif
</section>
