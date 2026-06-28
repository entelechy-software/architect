@props([
    'field',
])

@php
    $errorKey = 'formData.' . $field->getName();
    $type     = Str::kebab(class_basename($field));
@endphp

<div class="arch-field"
     data-type="{{ $type }}"
     data-required="{{ $field->isRequired() ? 'true' : 'false' }}"
     @if ($errors->has($errorKey)) data-invalid="true" @endif>
    <label class="arch-field__label" for="field-{{ $field->getName() }}">
        {{ $field->getLabel() }}
        @if ($field->isRequired())<span class="arch-field__required">*</span>@endif
    </label>

    <div class="arch-field__control">
        {{ $slot }}
    </div>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif

    @error($errorKey)
        <div class="arch-field__error">{{ $message }}</div>
    @enderror
</div>
