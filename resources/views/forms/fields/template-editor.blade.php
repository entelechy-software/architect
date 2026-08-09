@php /** @var \Entelechy\Architect\Forms\Fields\TemplateEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-template-editor"
         wire:ignore
         x-data="architectTemplateEditor({ wireField: 'formData.{{ $field->getName() }}', availableVariables: @js($field->getAvailableVariables()) })">
        @if ($field->getAvailableVariables() !== [])
            <div class="arch-template-editor__variables">
                @foreach ($field->getAvailableVariables() as $variable)
                    <button type="button" class="arch-chip" x-on:click="insertVariable('{{ $variable }}')">{{ $variable }}</button>
                @endforeach
            </div>
        @endif
        <textarea class="arch-input" rows="4" x-ref="input" x-on:input="onInput($event.target.value)"></textarea>
        <div class="arch-template-editor__preview" x-ref="preview"></div>
    </div>
</x-architect::field-wrapper>
