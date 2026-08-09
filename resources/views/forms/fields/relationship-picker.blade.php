@php /** @var \Entelechy\Architect\Forms\Fields\RelationshipPickerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-relationship-picker"
         wire:ignore
         x-data="architectRelationshipPicker({
            wireField: 'formData.{{ $field->getName() }}',
            allowedTypes: @js($field->getAllowedTypes()),
            searchUrl: @js($field->getSearchUrl()),
         })">
        <select class="arch-select" x-ref="type" x-on:change="onTypeChanged($event.target.value)">
            @foreach ($field->getAllowedTypes() as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </select>
        <input type="text" class="arch-input" x-ref="search" placeholder="{{ __('Search…') }}" x-on:input="onSearchInput($event.target.value)">
        <div class="arch-relationship-picker__results" x-ref="results"></div>
    </div>
</x-architect::field-wrapper>
