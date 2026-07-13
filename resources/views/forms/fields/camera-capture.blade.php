@php /** @var \Entelechy\Architect\Forms\Fields\CameraCaptureField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-camera-capture"
         wire:ignore
         x-data="architectCameraCapture({ wireField: 'formData.{{ $field->getName() }}', facing: @js($field->getFacing()) })">
        <video x-ref="preview" autoplay muted playsinline></video>
        <button type="button" class="arch-button" data-variant="solid" data-color="primary" x-on:click="capture()">{{ __('Take photo') }}</button>
    </div>
</x-architect::field-wrapper>
