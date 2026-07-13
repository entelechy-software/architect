@php /** @var \Entelechy\Architect\Forms\Fields\PostalCodeLookupField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-postal-code-lookup"
         wire:ignore
         x-data="architectPostalCodeLookup({ wireField: 'formData.{{ $field->getName() }}', lookupUrl: @js($field->getLookupUrl()) })">
        <input type="text" class="arch-input" x-ref="postcode" placeholder="{{ __('Postcode') }}">
        <button type="button" class="arch-button" data-variant="ghost" x-on:click="lookup()">{{ __('Find address') }}</button>
        <select class="arch-select" x-ref="results"></select>
    </div>
</x-architect::field-wrapper>
