@php /** @var \Entelechy\Architect\Forms\Fields\GradientEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-gradient-editor"
         wire:ignore
         x-data="architectGradientEditor({ wireField: 'formData.{{ $field->getName() }}' })">
        <div class="arch-gradient-editor__preview" x-ref="preview"></div>
        <label class="arch-gradient-editor__angle">
            {{ __('Angle') }}
            <input type="range" min="0" max="360" x-ref="angle" x-on:input="onAngleInput($event.target.value)">
        </label>
        <div class="arch-gradient-editor__stops" x-ref="stops"></div>
        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="addStop()">{{ __('Add stop') }}</button>
    </div>
</x-architect::field-wrapper>
