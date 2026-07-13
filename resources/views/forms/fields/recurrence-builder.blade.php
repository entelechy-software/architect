@php /** @var \Entelechy\Architect\Forms\Fields\RecurrenceBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-recurrence-builder">
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.freq">
            @foreach ($field->getFrequencies() as $freq)
                <option value="{{ $freq }}">{{ ucfirst($freq) }}</option>
            @endforeach
        </select>
        <input type="number" class="arch-input" min="1" placeholder="{{ __('Interval') }}" wire:model="formData.{{ $field->getName() }}.interval">
        <input type="text" class="arch-input" placeholder="{{ __('Until (optional)') }}" wire:model="formData.{{ $field->getName() }}.until">
    </div>
</x-architect::field-wrapper>
