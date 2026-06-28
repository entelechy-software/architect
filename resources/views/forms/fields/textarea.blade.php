@php /** @var \Entelechy\Architect\Forms\Fields\TextareaField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <textarea id="field-{{ $field->getName() }}"
              class="arch-textarea"
              rows="{{ $field->getRows() }}"
              wire:model="formData.{{ $field->getName() }}"
              @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif
              @if ($field->getMaxLength()) maxlength="{{ $field->getMaxLength() }}" @endif></textarea>

    @if ($field->getShowCharCount() && $field->getMaxLength())
        <div class="arch-field__char-count"
             x-data
             x-text="$wire.formData.{{ $field->getName() }}?.length ?? 0">0</div>
    @endif
</x-architect::field-wrapper>
