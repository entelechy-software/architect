@php /** @var \Entelechy\Architect\Forms\Fields\DialKnobField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-dial-knob"
         wire:ignore
         x-data="architectDialKnob({ wireField: 'formData.{{ $field->getName() }}', min: {{ $field->getMin() }}, max: {{ $field->getMax() }} })">
        <div x-ref="dial"></div>
    </div>
</x-architect::field-wrapper>
