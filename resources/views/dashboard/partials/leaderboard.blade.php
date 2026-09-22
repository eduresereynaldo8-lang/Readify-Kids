<div class="rk-table-scroll" role="region" aria-label="Student leaderboard" tabindex="0">
<table class="rk-table"><thead><tr><th scope="col">#</th><th scope="col">Student</th>@if($showScores ?? false)<th scope="col">Avg. score</th>@endif<th scope="col" class="rk-align-right">Points</th></tr></thead>
<tbody>@forelse($ranking as $learner)
<tr @if(($myId ?? null) === $learner->id) class="rk-current-student" @endif>
    <td><span class="rk-rank rank-{{ $loop->iteration }}">{{ $loop->iteration }}</span></td>
    <td><div class="rk-person"><span class="rk-avatar rk-avatar-{{ $loop->index % 3 }}">{{ mb_strtoupper(mb_substr($learner->firstname,0,1).mb_substr($learner->lastname,0,1)) }}</span><div><strong>{{ $learner->firstname }} {{ $learner->lastname }} @if(($myId ?? null) === $learner->id)<small class="rk-you">You</small>@endif</strong><small>{{ $learner->section ?: 'No section' }} · Level {{ $learner->current_level }}</small></div></div></td>
    @if($showScores ?? false)<td class="rk-score-cell">{{ $learner->activity_results_avg_score === null ? '—' : round($learner->activity_results_avg_score,1).'%' }}<div class="rk-progress rk-tone-green"><span style="width:{{ min(100, max(0, $learner->activity_results_avg_score ?? 0)) }}%"></span></div></td>@endif
    <td class="rk-points rk-align-right">{{ number_format($learner->total_points) }} <span aria-label="points">★</span></td>
</tr>
@empty<tr><td colspan="{{ ($showScores ?? false) ? 4 : 3 }}" class="rk-empty">Students will appear here as your classroom grows.</td></tr>@endforelse</tbody></table>
</div>
