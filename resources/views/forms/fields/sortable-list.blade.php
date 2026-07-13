@php /** @var \Entelechy\Architect\Forms\Fields\SortableListField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <ul class="arch-sortable-list"
        wire:ignore
        x-data="architectSortableList({ wireField: 'formData.{{ $field->getName() }}', options: @js($field->getOptions($get)) })"
        x-ref="list">
    </ul>
</x-architect::field-wrapper>
