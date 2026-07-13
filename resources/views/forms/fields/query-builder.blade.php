@php /** @var \Entelechy\Architect\Forms\Fields\QueryBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-query-builder"
         wire:ignore
         x-data="architectQueryBuilder({ wireField: 'formData.{{ $field->getName() }}', availableFields: @js($field->getAvailableFields()) })">
        <div x-ref="groups"></div>
    </div>
</x-architect::field-wrapper>
