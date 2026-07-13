@php /** @var \Entelechy\Architect\Forms\Fields\AudioRecorderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-audio-recorder"
         wire:ignore
         x-data="architectAudioRecorder({ wireField: 'formData.{{ $field->getName() }}', maxDurationSeconds: @js($field->getMaxDurationSeconds()) })">
        <button type="button" class="arch-button" data-variant="solid" data-color="primary" x-on:click="toggleRecording()" x-text="recording ? '{{ __('Stop') }}' : '{{ __('Record') }}'"></button>
        <span x-ref="duration">00:00</span>
        <audio x-ref="playback" controls></audio>
    </div>
</x-architect::field-wrapper>
