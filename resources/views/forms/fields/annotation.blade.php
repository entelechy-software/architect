@php /** @var \Entelechy\Architect\Forms\Fields\AnnotationField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-annotation"
         wire:ignore
         x-data="architectAnnotation({ wireField: 'formData.{{ $field->getName() }}', imageUrl: @js($field->getImageUrl()) })">
        <div x-ref="canvas"></div>
    </div>
</x-architect::field-wrapper>
