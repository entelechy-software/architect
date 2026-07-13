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
        <div class="arch-dual-listbox__pane" x-ref="available"></div>
        <div class="arch-dual-listbox__controls"></div>
        <div class="arch-dual-listbox__pane" x-ref="selected"></div>
    </div>
</x-architect::field-wrapper>
