@php /** @var \Entelechy\Architect\Forms\Fields\HierarchicalCheckboxTreeField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-checkbox-tree"
         wire:ignore
         x-data="architectCheckboxTree({
            wireField: 'formData.{{ $field->getName() }}',
            tree: @js($field->getTree($get)),
         })">
        <div x-ref="tree"></div>
    </div>
</x-architect::field-wrapper>
