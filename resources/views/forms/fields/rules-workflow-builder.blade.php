@php /** @var \Entelechy\Architect\Forms\Fields\RulesWorkflowBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-rules-workflow-builder"
         wire:ignore
         x-data="architectRulesWorkflowBuilder({ wireField: 'formData.{{ $field->getName() }}', availableNodeTypes: @js($field->getAvailableNodeTypes()) })">
        <div class="arch-rules-workflow-builder__nodes" x-ref="nodes"></div>
        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="addNode()">{{ __('Add node') }}</button>

        <div class="arch-rules-workflow-builder__edges" x-ref="edges"></div>
        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="addEdge()">{{ __('Add edge') }}</button>
    </div>
</x-architect::field-wrapper>
