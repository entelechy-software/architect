@php /** @var \Entelechy\Architect\Forms\Fields\BarcodeQrScannerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-barcode-scanner"
         wire:ignore
         x-data="architectBarcodeScanner({ wireField: 'formData.{{ $field->getName() }}', formats: @js($field->getFormats()) })">
        <video x-ref="preview" autoplay muted playsinline></video>
        <input type="text" class="arch-input" wire:model="formData.{{ $field->getName() }}" readonly>
    </div>
</x-architect::field-wrapper>
