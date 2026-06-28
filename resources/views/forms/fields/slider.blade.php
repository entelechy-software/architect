@php /** @var \Entelechy\Architect\Forms\Fields\Slider $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-slider" x-data="{ value: $wire.entangle('formData.{{ $field->getName() }}') }">
        <input type="range"
               class="arch-slider__input"
               min="{{ $field->getMin() }}"
               max="{{ $field->getMax() }}"
               step="{{ $field->getStep() }}"
               x-model.number="value">
        <span class="arch-slider__value" x-text="value"></span>
    </div>
</x-architect::field-wrapper>
