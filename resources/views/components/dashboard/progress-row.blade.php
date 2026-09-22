@props(['label', 'value' => null, 'tone' => 'blue', 'icon' => 'chart-bar'])
<div class="rk-skill rk-tone-{{ $tone }}">
    <span class="rk-skill-icon"><i class="ti ti-{{ $icon }}" aria-hidden="true"></i></span>
    <div class="rk-skill-body"><div class="rk-skill-label"><span>{{ $label }}</span><strong>{{ $value === null ? '—' : $value . '%' }}</strong></div>
        <div class="rk-progress" @if($value !== null) role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100" @endif><span style="width:{{ max(0, min(100, $value ?? 0)) }}%"></span></div>
    </div>
</div>
