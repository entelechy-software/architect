@php /** @var \Entelechy\Architect\Forms\Fields\TextField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="{{ $field->getClientValidationAttributes()['type'] ?? 'text' }}"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           wire:model="formData.{{ $field->getName() }}"
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif
           @if ($field->getMaxLength()) maxlength="{{ $field->getMaxLength() }}" @endif
           @if ($field->isRequired()) required @endif
           @if (isset($field->getClientValidationAttributes()['pattern'])) pattern="{{ $field->getClientValidationAttributes()['pattern'] }}" @endif>

    @if ($field->getShowCharCount() && $field->getMaxLength())
        <div class="arch-field__char-count"
             x-data
             x-text="$wire.formData.{{ $field->getName() }}?.length ?? 0">0</div>
    @endif
</x-architect::field-wrapper>

