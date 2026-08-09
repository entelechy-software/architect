{{--
    Toolbar partial: DropdownRadioGroup — vertical radio options inside a dropdown.
    Clicking an option calls wire:click="setDropdownRadio(compoundKey, value)"
    without closing the dropdown (stays open for discoverability).
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownRadioGroup $dropdownItem */
    $compoundKey   = $parentDropKey . '.' . $dropdownItem->getKey();
    $currentValue  = $dropdownRadioValues[$compoundKey] ?? $dropdownItem->getDefault() ?? '';
    $persist       = $dropdownItem->getPersist();
    $lsKey         = $persist === 'local'
        ? "architectToolbar_{$toolbarKey}_dropdown-radio_{$compoundKey}"
        : null;
@endphp

<li
    role="none"
    @if ($lsKey)
        x-init="
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null) $wire.call('setDropdownRadio', '{{ $compoundKey }}', __stored);
        "
    @endif
>
    <ul role="group" class="py-0.5">
        @foreach ($dropdownItem->getOptions() as $option)
            @php $isSelected = $currentValue === $option['value']; @endphp
            <li role="none">
                <button
                    type="button"
                    wire:click="setDropdownRadio('{{ $compoundKey }}', '{{ $option['value'] }}')"
                    @if ($dropdownItem->isDisabled()) disabled @endif
                    role="menuitemradio"
                    aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                    @class([
                        'flex w-full items-center gap-3 px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700',
                        'text-primary-600 dark:text-primary-400 font-medium' => $isSelected,
                        'text-gray-700 dark:text-gray-300' => !$isSelected,
                    ])
                >
                    {{-- Radio dot indicator --}}
                    <span @class([
                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border',
                        'border-primary-500 bg-primary-500' => $isSelected,
                        'border-gray-400 dark:border-gray-500' => !$isSelected,
                    ])>
                        @if ($isSelected)
                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                        @endif
                    </span>
                    @if ($option['icon'] !== null)
                        <i class="{{ $option['icon'] }}"></i>
                    @endif
                    {{ $option['label'] }}
                </button>
            </li>
        @endforeach
    </ul>
</li>
