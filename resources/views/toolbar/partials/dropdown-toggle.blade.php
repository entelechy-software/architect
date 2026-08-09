{{--
    Toolbar partial: DropdownCheckbox (toggle/pill style) — created via DropdownCheckbox::toggle().
    State stored server-side in $toggleValues['dropdownKey.itemKey'].
    Clicking calls wire:click="setToggle(compoundKey, newValue)".
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownCheckbox $dropdownItem */
    if (!$this->can($dropdownItem->getPermission())) return;

    $compoundKey = $parentDropKey . '.' . $dropdownItem->getKey();
    $isOn        = $toggleValues[$compoundKey] ?? $dropdownItem->getDefault();
    $persist     = $dropdownItem->getPersist();
    $lsKey       = $persist === 'local'
        ? "architectToolbar_{$toolbarKey}_toggle_{$compoundKey}"
        : null;
@endphp

<li
    role="menuitemcheckbox"
    aria-checked="{{ $isOn ? 'true' : 'false' }}"
    @if ($lsKey)
        x-init="
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null) $wire.call('setToggle', '{{ $compoundKey }}', __stored === 'true');
        "
    @endif
>
    <button
        type="button"
        wire:click="setToggle('{{ $compoundKey }}', {{ $isOn ? 'false' : 'true' }})"
        @if ($dropdownItem->isDisabled()) disabled @endif
        @class([
            'flex w-full items-center justify-between gap-2 px-4 py-2 text-sm text-left text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
            'opacity-50' => $dropdownItem->isDisabled(),
        ])
    >
        <span class="flex items-center gap-2">
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>@endif
            {{ $dropdownItem->getLabel() }}
        </span>
        {{-- Toggle pill --}}
        <span @class([
            'relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200',
            'bg-primary-600' => $isOn,
            'bg-gray-300 dark:bg-gray-600' => !$isOn,
        ])>
            <span @class([
                'inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200',
                'translate-x-4' => $isOn,
                'translate-x-0' => !$isOn,
            ])></span>
        </span>
    </button>
</li>
