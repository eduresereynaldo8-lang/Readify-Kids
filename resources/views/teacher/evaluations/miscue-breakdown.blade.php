<div class="ra-miscue-breakdown">
    @if($evaluation->has_legacy_miscues)
    <p class="ra-legacy-note">Legacy total miscues: <strong>{{ $evaluation->miscues }}</strong>. No detailed breakdown was recorded.</p>
    @endif
    <dl class="ra-breakdown-list">
        @foreach(\App\Services\ReadingAssessment::MISCUE_TYPES as $field => $label)
        <div><dt>{{ $label }}</dt><dd>{{ $evaluation->{$field} ?? 0 }}</dd></div>
        @endforeach
        <div class="ra-breakdown-total"><dt>Total Miscues</dt><dd>{{ $evaluation->miscues ?? 'N/A' }}</dd></div>
        <div class="ra-breakdown-total"><dt>Oral Reading Score</dt><dd>{{ $evaluation->oral_reading_score === null ? 'N/A' : number_format($evaluation->oral_reading_score, 2).'%' }}</dd></div>
    </dl>
</div>
