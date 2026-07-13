@php /** @var \Entelechy\Architect\Forms\Fields\MeasurementField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-input-group">
        <input type="number" class="arch-input" wire:model="formData.{{ $field->getName() }}.value">
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.unit">
            @foreach ($field->getUnits() as $unit)
                <option value="{{ $unit }}" @selected($unit === $field->getDefaultUnit())>{{ $unit }}</option>
            @endforeach
        </select>
    </div>
</x-architect::field-wrapper>
