{{--
    Toolbar partial: DropdownTextInput — inline text/number/date input inside a dropdown.
    Uses Alpine @input.debounce to avoid hammering Livewire on every keystroke.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownTextInput $dropdownItem */
    $compoundKey  = $parentDropKey . '.' . $dropdownItem->getKey();
    $currentValue = $textValues[$compoundKey] ?? $dropdownItem->getDefault();
    $debounceMs   = $dropdownItem->getDebounceMs();
    $inputType    = $dropdownItem->getInputType();
    $persist      = $dropdownItem->getPersist();
    $lsKey        = $persist === 'local'
        ? "architectToolbar_{$toolbarKey}_text_{$compoundKey}"
        : null;
@endphp

<li
    role="none"
    @if ($lsKey)
        x-init="
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null) $wire.call('setTextValue', '{{ $compoundKey }}', __stored);
        "
    @endif
>
    <div class="px-4 py-2 space-y-1">
        @if ($dropdownItem->getLabel())
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 select-none">
                {{ $dropdownItem->getLabel() }}
            </label>
        @endif
        <input
            type="{{ $inputType }}"
            value="{{ $currentValue }}"
            @if ($dropdownItem->getPlaceholder())
                placeholder="{{ $dropdownItem->getPlaceholder() }}"
            @endif
            @if ($dropdownItem->isDisabled()) disabled @endif
            x-on:input.debounce.{{ $debounceMs }}ms="$wire.call('setTextValue', '{{ $compoundKey }}', $el.value)"
            @class([
                'block w-full rounded border px-2 py-1 text-sm',
                'border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                'dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500',
                'opacity-50 cursor-not-allowed' => $dropdownItem->isDisabled(),
            ])
        >
    </div>
</li>
