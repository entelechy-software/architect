{{--
    Toolbar partial: DropdownCheckbox — a standard checkbox boolean item inside a dropdown.
    For a pill/toggle-switch style, use DropdownCheckbox::toggle() (rendered via dropdown-toggle.blade.php).
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownCheckbox $dropdownItem */
    if (!$this->can($dropdownItem->getPermission())) return;

    $compoundKey = $parentDropKey . '.' . $dropdownItem->key();
    $isChecked   = $checkboxValues[$compoundKey] ?? $dropdownItem->getDefault();
    $persist     = $dropdownItem->getPersist();
    $lsKey       = $persist === 'local'
        ? "architectToolbar_{$toolbarKey}_checkbox_{$compoundKey}"
        : null;
@endphp

<li
    role="menuitemcheckbox"
    aria-checked="{{ $isChecked ? 'true' : 'false' }}"
    @if ($lsKey)
        x-init="
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null) $wire.call('setCheckbox', '{{ $compoundKey }}', __stored === 'true');
        "
    @endif
>
    <button
        type="button"
        wire:click="setCheckbox('{{ $compoundKey }}', {{ $isChecked ? 'false' : 'true' }})"
        @if ($dropdownItem->isDisabled()) disabled @endif
        @class([
            'flex w-full items-center gap-3 px-4 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
            'opacity-50' => $dropdownItem->isDisabled(),
        ])
    >
        {{-- Checkbox square --}}
        <span @class([
            'flex h-4 w-4 shrink-0 items-center justify-center rounded border',
            'border-primary-500 bg-primary-500 text-white' => $isChecked,
            'border-gray-400 bg-white dark:bg-gray-700 dark:border-gray-500' => !$isChecked,
        ])>
            @if ($isChecked)
                <i class="fas fa-check text-xs"></i>
            @endif
        </span>
        <span class="flex items-center gap-2">
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }}"></i>@endif
            {{ $dropdownItem->getLabel() }}
        </span>
    </button>
</li>
