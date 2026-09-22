@php $typeTotal = $activityTypes->sum(); @endphp
<div class="rk-type-list">
@forelse($activityTypes as $type => $count)
    @php $tone = $type === 'Read Aloud' ? 'green' : ($type === 'Battle' || $type === 'Word Game' ? 'purple' : 'blue'); $percentage = $typeTotal ? round($count / $typeTotal * 100) : 0; @endphp
    <div class="rk-type-item rk-tone-{{ $tone }}"><span class="rk-skill-icon"><i class="ti ti-{{ $type === 'Read Aloud' ? 'book' : ($type === 'Battle' ? 'device-gamepad-2' : 'puzzle') }}" aria-hidden="true"></i></span>
        <div><div class="rk-skill-label"><strong>{{ $type }}</strong><span>{{ $count }}</span></div><div class="rk-progress"><span style="width:{{ $percentage }}%"></span></div><small>{{ $percentage }}% of activities</small></div>
    </div>
@empty<div class="rk-empty"><i class="ti ti-books" aria-hidden="true"></i><p>No activities yet.</p></div>@endforelse
</div>
