@php /** @var \Entelechy\Architect\Forms\Fields\DocumentScannerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-document-scanner"
         wire:ignore
         x-data="architectDocumentScanner({ wireField: 'formData.{{ $field->getName() }}', outputFormat: @js($field->getOutputFormat()) })">
        <video x-ref="preview" autoplay muted playsinline></video>
        <button type="button" class="arch-button" data-variant="solid" data-color="primary" x-on:click="capture()">{{ __('Scan document') }}</button>
    </div>
</x-architect::field-wrapper>
