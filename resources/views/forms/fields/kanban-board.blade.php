@php /** @var \Entelechy\Architect\Forms\Fields\KanbanBoardField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-kanban-board"
         wire:ignore
         x-data="architectKanbanBoard({
            wireField: 'formData.{{ $field->getName() }}',
            columns: @js($field->getColumns()),
            items: @js($field->getItems()),
         })">
        @foreach ($field->getColumns() as $column)
            <div class="arch-kanban-board__column" data-column="{{ $column }}">
                <h4>{{ $column }}</h4>
                <div class="arch-kanban-board__items" data-column-items="{{ $column }}"></div>
            </div>
        @endforeach
    </div>
</x-architect::field-wrapper>
