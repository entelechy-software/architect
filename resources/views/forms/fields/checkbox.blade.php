@php /** @var \Entelechy\Architect\Forms\Fields\CheckboxField $field */ @endphp
<div class="arch-field" data-type="checkbox">
    <label class="arch-checkbox" for="field-{{ $field->getName() }}">
        <input type="checkbox"
               id="field-{{ $field->getName() }}"
               class="arch-checkbox__input"
               wire:model="formData.{{ $field->getName() }}">
        <span class="arch-checkbox__label">{{ $field->getLabel() }}</span>
    </label>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif

    @error('formData.' . $field->getName())
        <div class="arch-field__error">{{ $message }}</div>
    @enderror
</div>
