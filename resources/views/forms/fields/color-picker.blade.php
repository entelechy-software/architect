@php /** @var \Entelechy\Architect\Forms\Fields\ColorPicker $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-color-picker"
         x-data="architectColorPicker({
            wireField: 'formData.{{ $field->getName() }}',
            format: '{{ $field->getFormat() }}',
            withAlpha: {{ $field->getWithAlpha() ? 'true' : 'false' }},
         })">
        <button type="button" class="arch-color-picker__swatch" :style="{ backgroundColor: value }" @click="open = !open"></button>
        <input type="text" class="arch-input" x-model="value" placeholder="{{ $field->getFormat() === 'hex' ? '#0ea5e9' : $field->getFormat() }}">
        <input type="color" class="arch-color-picker__native" x-model="value" x-show="open">
    </div>
</x-architect::field-wrapper>
