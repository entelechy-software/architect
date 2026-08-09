@php /** @var \Entelechy\Architect\Forms\Fields\NodeGraphEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-node-graph-editor"
         wire:ignore
         x-data="architectNodeGraphEditor({ wireField: 'formData.{{ $field->getName() }}', availableNodeTypes: @js($field->getAvailableNodeTypes()) })">
        <div class="arch-node-graph-editor__toolbar">
            <select class="arch-select" x-ref="nodeType">
                @foreach ($field->getAvailableNodeTypes() as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="addNode($refs.nodeType.value)">{{ __('Add node') }}</button>
            <span class="arch-field__hint" x-show="pendingConnection" x-cloak>{{ __('Click a target node to connect…') }}</span>
        </div>
        <div class="arch-node-graph-editor__canvas" x-ref="canvas">
            <svg class="arch-node-graph-editor__edges" x-ref="svg"></svg>
        </div>
    </div>
</x-architect::field-wrapper>
