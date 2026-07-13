@php /** @var \Entelechy\Architect\Forms\Fields\DependencyBuilderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-dependency-builder">
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.field">
            @foreach ($field->getAvailableFields() as $f)
                <option value="{{ $f }}">{{ $f }}</option>
            @endforeach
        </select>
        <select class="arch-select" wire:model="formData.{{ $field->getName() }}.operator">
            @foreach ($field->getAvailableOperators() as $operator)
                <option value="{{ $operator }}">{{ str_replace('_', ' ', $operator) }}</option>
            @endforeach
        </select>
        <input type="text" class="arch-input" wire:model="formData.{{ $field->getName() }}.value">
    </div>
</x-architect::field-wrapper>
