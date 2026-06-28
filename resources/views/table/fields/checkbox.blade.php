@php
    $switchId = 'form-switch-' . str_replace(['.', '[', ']'], '-', (string) $field->name());
@endphp
<div class="flex flex-col gap-2">
    <div class="arch-check arch-switch mb-0 inline-flex items-center gap-3">
        <input
            type="checkbox"
            role="switch"
            id="{{ $switchId }}"
            class="arch-switch-input"
            wire:model="form.{{ $field->name() }}"
            value="1"
        >
        <label class="arch-check-label text-sm font-medium text-gray-700 dark:text-gray-200 mb-0" for="{{ $switchId }}">
            {{ $field->getLabel() }}
        </label>
    </div>
</div>
@error('form.' . $field->name())
    <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
@enderror
