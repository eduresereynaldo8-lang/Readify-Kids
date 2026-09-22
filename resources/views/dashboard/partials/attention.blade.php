<section class="rk-card rk-attention"><div class="rk-card-heading"><h2><i class="ti ti-alert-triangle" aria-hidden="true"></i> Needs Attention</h2><a href="{{ $attentionUrl }}">View details →</a></div>
<div class="rk-attention-grid">
    <div><i class="ti ti-users" aria-hidden="true"></i><strong>{{ $attention['inactive'] }}</strong><span>students with no completed activity this week</span></div>
    <div><i class="ti ti-clipboard" aria-hidden="true"></i><strong>{{ $attention['unattempted'] }}</strong><span>published activities with no attempts</span></div>
    <div><i class="ti ti-microphone" aria-hidden="true"></i><strong>{{ $attention['pending'] }}</strong><span>recordings waiting for review</span></div>
</div></section>
