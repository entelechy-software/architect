@php /** @var \Entelechy\Architect\Forms\Fields\AudioWaveformSelectionField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-audio-waveform-selection"
         wire:ignore
         x-data="architectAudioWaveformSelection({ wireField: 'formData.{{ $field->getName() }}', audioUrl: @js($field->getAudioUrl()) })">
        <div class="arch-audio-waveform-selection__waveform" x-ref="waveform"></div>
    </div>
</x-architect::field-wrapper>
