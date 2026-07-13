@php /** @var \Entelechy\Architect\Forms\Fields\TimelineEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-timeline-editor"
         wire:ignore
         x-data="architectTimelineEditor({ wireField: 'formData.{{ $field->getName() }}', totalDuration: {{ $field->getTotalDuration() }} })">
        <div class="arch-timeline-editor__track" x-ref="track"></div>
    </div>
</x-architect::field-wrapper>
