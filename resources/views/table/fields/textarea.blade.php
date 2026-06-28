@php /** @var \Entelechy\Architect\Table\Fields\TextareaField $field */ @endphp
<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="field-{{ $field->name() }}">
        {{ $field->getLabel() }}
        @if ($field->isRequired())<span class="text-red-600 dark:text-red-400">*</span>@endif
    </label>

    <x-architect::input-wrapper :valid="! $errors->has('form.' . $field->name())">
        <textarea
            id="field-{{ $field->name() }}"
            class="arch-input"
            rows="{{ $field->getRows() }}"
            wire:model="form.{{ $field->name() }}"
            @if ($field->getPlaceholder() !== '') placeholder="{{ $field->getPlaceholder() }}" @endif
            @if ($field->getMaxLength()) maxlength="{{ $field->getMaxLength() }}" @endif
        ></textarea>
    </x-architect::input-wrapper>

    @if ($field->getHint() !== null)
        <div class="fi-fo-hint text-sm text-gray-500 dark:text-gray-400">{{ $field->getHint() }}</div>
    @endif
    @error('form.' . $field->name())
        <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror
</div>
