@props(['label', 'value', 'icon', 'tone' => 'blue', 'hint' => '', 'href' => null])
<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif class="rk-stat rk-tone-{{ $tone }}">
    <span class="rk-stat-icon"><i class="ti ti-{{ $icon }}" aria-hidden="true"></i></span>
    <div class="rk-stat-copy"><span class="rk-stat-label">{{ $label }}</span><strong class="rk-stat-value">{{ $value }}</strong><small>{{ $hint }}</small></div>
    @if($href)<i class="ti ti-chevron-right rk-stat-arrow" aria-hidden="true"></i>@endif
</{{ $href ? 'a' : 'div' }}>
