@php /** @var \Entelechy\Architect\Forms\Fields\MentionEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-mention-editor"
         wire:ignore
         x-data="architectMentionEditor({ wireField: 'formData.{{ $field->getName() }}', mentionableUrl: @js($field->getMentionableUrl()) })">
        <div class="arch-mention-editor__content" x-ref="editor" contenteditable="true"></div>
    </div>
</x-architect::field-wrapper>
