<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="field-{{ $field->name() }}">
        {{ $field->getLabel() }}
    </label>

    <x-architect::input-wrapper :valid="! $errors->has('form.' . $field->name())">
        <input
            type="text"
            id="field-{{ $field->name() }}"
            class="arch-input"
            placeholder="{{ $field->getPlaceholder() !== '' ? $field->getPlaceholder() : 'dd/mm/yyyy' }}"
            wire:model="form.{{ $field->name() }}"
            autocomplete="off"
        >
    </x-architect::input-wrapper>

    @error('form.' . $field->name())
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror
</div>
