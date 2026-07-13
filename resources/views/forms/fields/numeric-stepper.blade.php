@php /** @var \Entelechy\Architect\Forms\Fields\NumericStepperField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-numeric-stepper">
        <button type="button" class="arch-button" data-variant="ghost" wire:click="$set('formData.{{ $field->getName() }}', (formData.{{ $field->getName() }} ?? 0) - {{ $field->getStep() }})">&minus;</button>
        <input type="number"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               step="{{ $field->getStep() }}"
               @if ($field->getMin() !== null) min="{{ $field->getMin() }}" @endif
               @if ($field->getMax() !== null) max="{{ $field->getMax() }}" @endif
               wire:model="formData.{{ $field->getName() }}">
        <button type="button" class="arch-button" data-variant="ghost" wire:click="$set('formData.{{ $field->getName() }}', (formData.{{ $field->getName() }} ?? 0) + {{ $field->getStep() }})">+</button>
    </div>
</x-architect::field-wrapper>
