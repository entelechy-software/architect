@php /** @var \Entelechy\Architect\Forms\Fields\RankingField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <ol class="arch-ranking"
        wire:ignore
        x-data="architectRanking({ wireField: 'formData.{{ $field->getName() }}', options: @js($field->getOptions($get)) })">
        <template x-for="(key, index) in order" :key="key">
            <li class="arch-ranking__item"
                draggable="true"
                x-on:dragstart="onDragStart(index, $event)"
                x-on:dragover="onDragOver($event)"
                x-on:drop="onDrop(index)">
                <span class="arch-ranking__handle">::</span>
                <span x-text="label(key)"></span>
            </li>
        </template>
    </ol>
</x-architect::field-wrapper>
