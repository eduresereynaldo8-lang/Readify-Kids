@foreach($skills as $label => $value)
    @php $visual = ['Oral Reading'=>['blue','microphone'], 'Pronunciation'=>['blue','microphone'], 'Fluency'=>['green','volume'], 'Accuracy'=>['purple','alphabet-latin'], 'Comprehension'=>['yellow','brain']][$label]; @endphp
    <x-dashboard.progress-row :label="$label" :value="$value" :tone="$visual[0]" :icon="$visual[1]" />
@endforeach
<p class="rk-footnote">{{ collect($skills)->filter(fn($value) => $value !== null)->isEmpty() ? 'Reading skills will appear after a teacher evaluates a recording.' : 'Based on teacher evaluations · all time' }}</p>