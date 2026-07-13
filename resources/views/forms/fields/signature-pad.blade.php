@php /** @var \Entelechy\Architect\Forms\Fields\SignaturePadField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-signature-pad"
         wire:ignore
         x-data="architectSignaturePad({
            wireField: 'formData.{{ $field->getName() }}',
            penColor: @js($field->getPenColor()),
            backgroundColor: @js($field->getBackgroundColor()),
         })">
        <canvas x-ref="canvas" width="400" height="150"></canvas>
        <button type="button" class="arch-button" data-variant="ghost" x-on:click="clear()">{{ __('Clear') }}</button>
    </div>
</x-architect::field-wrapper>
