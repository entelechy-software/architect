@php /** @var \Entelechy\Architect\Forms\Fields\DrawingSketchField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-drawing-sketch"
         wire:ignore
         x-data="architectDrawingSketch({ wireField: 'formData.{{ $field->getName() }}', strokeFormat: @js($field->usesStrokeFormat()) })">
        <canvas x-ref="canvas" width="500" height="300"></canvas>
        <div class="arch-drawing-sketch__toolbar" x-ref="toolbar"></div>
    </div>
</x-architect::field-wrapper>
