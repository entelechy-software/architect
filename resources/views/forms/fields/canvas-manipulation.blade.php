@php /** @var \Entelechy\Architect\Forms\Fields\CanvasManipulationField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-canvas-manipulation"
         wire:ignore
         x-data="architectCanvasManipulation({ wireField: 'formData.{{ $field->getName() }}' })"
         style="height: 400px">
        <div x-ref="canvas" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
