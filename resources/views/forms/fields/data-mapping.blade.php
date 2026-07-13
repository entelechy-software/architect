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
    </div>
</x-architect::field-wrapper>
