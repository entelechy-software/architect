@php /** @var \Entelechy\Architect\Forms\Fields\RulesWorkflowBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-rules-workflow-builder"
         wire:ignore
         x-data="architectRulesWorkflowBuilder({ wireField: 'formData.{{ $field->getName() }}', availableNodeTypes: @js($field->getAvailableNodeTypes()) })"
         style="height: 400px">
        <div x-ref="canvas" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
