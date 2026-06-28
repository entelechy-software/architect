@php /** @var \Entelechy\Architect\Forms\Fields\RichEditor $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-rich-editor"
         x-data="architectRichEditor({ wireField: 'formData.{{ $field->getName() }}', toolbar: @js($field->getToolbar()) })"
         wire:ignore>
        <div class="arch-rich-editor__toolbar" x-ref="toolbar"></div>
        <div class="arch-rich-editor__content" x-ref="editor"></div>
    </div>
</x-architect::field-wrapper>
