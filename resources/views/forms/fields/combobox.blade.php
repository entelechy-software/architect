@php /** @var \Entelechy\Architect\Forms\Fields\ComboboxField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-combobox"
         wire:ignore
         x-data="architectCombobox({
            wireField: 'formData.{{ $field->getName() }}',
            options: @js($field->getOptions(fn (string $f) => null)),
            allowCustomValue: @js($field->isCustomValueAllowed()),
         })">
        <input type="text"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               x-ref="input"
               @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif>
        <div class="arch-combobox__options" x-ref="options"></div>
    </div>
</x-architect::field-wrapper>
