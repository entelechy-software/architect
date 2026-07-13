@php /** @var \Entelechy\Architect\Forms\Fields\VideoRecorderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-video-recorder"
         wire:ignore
         x-data="architectVideoRecorder({
            wireField: 'formData.{{ $field->getName() }}',
            facing: @js($field->getFacing()),
            maxDurationSeconds: @js($field->getMaxDurationSeconds()),
         })">
        <video x-ref="preview" autoplay muted playsinline></video>
        <button type="button" class="arch-button" data-variant="solid" data-color="primary" x-on:click="toggleRecording()" x-text="recording ? '{{ __('Stop') }}' : '{{ __('Record') }}'"></button>
    </div>
</x-architect::field-wrapper>
