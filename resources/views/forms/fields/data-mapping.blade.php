@php /** @var \Entelechy\Architect\Forms\Fields\DataMappingField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-data-mapping"
         wire:ignore
         x-data="architectDataMapping({
            wireField: 'formData.{{ $field->getName() }}',
            sourceFields: @js($field->getSourceFields()),
            destinationFields: @js($field->getDestinationFields()),
         })">
        <div x-ref="rows"></div>
        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="addRow()">{{ __('Add mapping') }}</button>
    </div>
</x-architect::field-wrapper>
