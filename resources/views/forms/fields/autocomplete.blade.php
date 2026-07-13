@php /** @var \Entelechy\Architect\Forms\Fields\AutocompleteField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-autocomplete"
         wire:ignore
         x-data="architectAutocomplete({
            wireField: 'formData.{{ $field->getName() }}',
            options: @js($field->getOptions(fn (string $f) => null)),
         })">
        <input type="text"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               x-ref="input"
               @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif>
        <div class="arch-autocomplete__suggestions" x-ref="suggestions"></div>
    </div>
</x-architect::field-wrapper>
