@php /** @var \Entelechy\Architect\Forms\Fields\RelationshipPickerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-relationship-picker"
         wire:ignore
         x-data="architectRelationshipPicker({ wireField: 'formData.{{ $field->getName() }}', allowedTypes: @js($field->getAllowedTypes()) })">
        <select class="arch-select" x-ref="type">
            @foreach ($field->getAllowedTypes() as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </select>
        <input type="text" class="arch-input" x-ref="search" placeholder="{{ __('Search…') }}">
    </div>
</x-architect::field-wrapper>
