@php /** @var \Entelechy\Architect\Forms\Fields\EntityPickerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-entity-picker"
         wire:ignore
         x-data="architectEntityPicker({
            wireField: 'formData.{{ $field->getName() }}',
            searchUrl: @js($field->getSearchUrl()),
            multiple: @js($field->isMultiple()),
         })">
        <input type="text" class="arch-input" x-ref="search" placeholder="{{ __('Search…') }}">
        <div class="arch-entity-picker__results" x-ref="results"></div>
    </div>
</x-architect::field-wrapper>
