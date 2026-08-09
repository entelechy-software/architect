@php /** @var \Entelechy\Architect\Forms\Fields\SortableListField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <ul class="arch-sortable-list"
        wire:ignore
        x-data="architectSortableList({ wireField: 'formData.{{ $field->getName() }}', options: @js($field->getOptions($get)) })">
        <template x-for="(key, index) in order" :key="key">
            <li class="arch-sortable-list__item"
                draggable="true"
                x-on:dragstart="onDragStart(index, $event)"
                x-on:dragover="onDragOver($event)"
                x-on:drop="onDrop(index)">
                <span class="arch-sortable-list__handle">::</span>
                <span x-text="label(key)"></span>
            </li>
        </template>
    </ul>
</x-architect::field-wrapper>
