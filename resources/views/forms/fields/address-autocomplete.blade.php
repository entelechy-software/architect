@php /** @var \Entelechy\Architect\Forms\Fields\AddressAutocompleteField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-address-autocomplete"
         wire:ignore
         x-data="architectAddressAutocomplete({ wireField: 'formData.{{ $field->getName() }}', searchUrl: @js($field->getSearchUrl()) })">
        <input type="text" class="arch-input" x-ref="search" placeholder="{{ __('Start typing an address…') }}">
        <div class="arch-address-autocomplete__results" x-ref="results"></div>
    </div>
</x-architect::field-wrapper>
