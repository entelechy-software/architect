@php /** @var \Entelechy\Architect\Forms\Fields\ImageComparisonSliderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-image-comparison-slider"
         wire:ignore
         x-data="architectImageComparisonSlider({
            wireField: 'formData.{{ $field->getName() }}',
            beforeImageUrl: @js($field->getBeforeImageUrl()),
            afterImageUrl: @js($field->getAfterImageUrl()),
         })">
        <div x-ref="slider"></div>
    </div>
</x-architect::field-wrapper>
