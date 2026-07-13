@php /** @var \Entelechy\Architect\Forms\Fields\TreeSelectField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-tree-select"
         wire:ignore
         x-data="architectTreeSelect({
            wireField: 'formData.{{ $field->getName() }}',
            tree: @js($field->getTree($get)),
            selectableBranches: @js($field->areBranchesSelectable()),
         })">
        <div x-ref="tree"></div>
    </div>
</x-architect::field-wrapper>
