@php /** @var \Entelechy\Architect\Forms\Fields\NodeGraphEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-node-graph-editor"
         wire:ignore
         x-data="architectNodeGraphEditor({ wireField: 'formData.{{ $field->getName() }}', availableNodeTypes: @js($field->getAvailableNodeTypes()) })"
         style="height: 400px">
        <div x-ref="canvas" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
