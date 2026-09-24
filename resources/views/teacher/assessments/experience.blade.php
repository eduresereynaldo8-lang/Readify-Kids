<section class="rk-card">
    <div class="rk-card-heading"><h2><i class="ti ti-mood-smile" aria-hidden="true"></i> Learner Experience</h2></div>
    <p class="rk-card-subtitle">How much did students enjoy their reading experience?</p>
    <div class="ra-experience-report">
        <div class="ra-rating-average"><small>Average rating</small><strong><span aria-hidden="true">★</span> {{ $assessment['averages']['experience'] === null ? '—' : number_format($assessment['averages']['experience'], 1).' / 5' }}</strong><small>{{ $assessment['experienceTotal'] }} recorded ratings</small></div>
        <div class="ra-rating-bars">
        @foreach($assessment['experiences'] as $rating => $count)
            <div><span class="ra-face" aria-hidden="true">{{ [1 => '😞', 2 => '🙁', 3 => '😐', 4 => '🙂', 5 => '😃'][$rating] }}</span><strong>{{ $rating }}</strong><div class="ra-rating-track"><span style="height:{{ $assessment['experienceTotal'] ? $count / max($assessment['experiences']) * 100 : 0 }}%"></span></div><small>{{ $count }}<span class="visually-hidden"> evaluations rated {{ $rating }} out of 5</span></small></div>
        @endforeach
        </div>
    </div>
</section>
