@php /** @var \Entelechy\Architect\Forms\Fields\DualListboxField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-dual-listbox"
         wire:ignore
         x-data="architectDualListbox({
            wireField: 'formData.{{ $field->getName() }}',
            options: @js($field->getOptions($get)),
            availableLabel: @js($field->getAvailableLabel()),
            selectedLabel: @js($field->getSelectedLabel()),
         })">
        <div class="arch-dual-listbox__pane">
            <h5 x-text="availableLabel"></h5>
            <div x-ref="available"></div>
        </div>
        <div class="arch-dual-listbox__controls">
            <button type="button" class="arch-button" data-variant="ghost" x-on:click="moveToSelected()">&rsaquo;</button>
            <button type="button" class="arch-button" data-variant="ghost" x-on:click="moveToAvailable()">&lsaquo;</button>
        </div>
        <div class="arch-dual-listbox__pane">
            <h5 x-text="selectedLabel"></h5>
            <div x-ref="selected"></div>
        </div>
    </div>
</x-architect::field-wrapper>
