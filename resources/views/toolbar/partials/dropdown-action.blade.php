{{--
    Toolbar partial: DropdownAction — a clickable link/button inside a dropdown.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownAction $dropdownItem */
    if (!$this->can($dropdownItem->getPermission())) return;

    $isDanger = $dropdownItem->getColor() === 'danger';
@endphp

<li role="none">
    @if ($dropdownItem->getHref() !== null)
        <a
            href="{{ $dropdownItem->getHref() }}"
            @if ($dropdownItem->isNewWindow()) target="_blank" rel="noopener noreferrer" @endif
            @if ($dropdownItem->getConfirm()) onclick="return confirm('{{ addslashes($dropdownItem->getConfirm()) }}')" @endif
            role="menuitem"
            @class([
                'flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
                'text-red-600 dark:text-red-400' => $isDanger,
                'text-gray-700 dark:text-gray-300' => !$isDanger,
                'opacity-50 pointer-events-none' => $dropdownItem->isDisabled(),
            ])
        >
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>@endif
            <span class="flex-1">{{ $dropdownItem->getLabel() }}</span>
            @if ($dropdownItem->getBadge())
                <span class="ms-auto badge bg-{{ $dropdownItem->getBadgeColor() }}">{{ $dropdownItem->getBadge() }}</span>
            @endif
        </a>
    @elseif ($dropdownItem->getWireClick() !== null)
        <button
            type="button"
            wire:click="{{ $dropdownItem->getWireClick() }}"
            @click="{{ $dropKey }} = false"
            @if ($dropdownItem->isDisabled()) disabled @endif
            role="menuitem"
            @class([
                'flex w-full items-center gap-2 px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700',
                'text-red-600 dark:text-red-400' => $isDanger,
                'text-gray-700 dark:text-gray-300' => !$isDanger,
            ])
        >
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>@endif
            <span class="flex-1">{{ $dropdownItem->getLabel() }}</span>
            @if ($dropdownItem->getBadge())
                <span class="ms-auto badge bg-{{ $dropdownItem->getBadgeColor() }}">{{ $dropdownItem->getBadge() }}</span>
            @endif
        </button>
    @elseif ($dropdownItem->getDispatchEvent() !== null)
        <button
            type="button"
            @click="$dispatch('{{ $dropdownItem->getDispatchEvent() }}', {{ \Illuminate\Support\Js::from($dropdownItem->getDispatchPayload()) }}); {{ $dropKey }} = false"
            @if ($dropdownItem->isDisabled()) disabled @endif
            role="menuitem"
            @class([
                'flex w-full items-center gap-2 px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700',
                'text-red-600 dark:text-red-400' => $isDanger,
                'text-gray-700 dark:text-gray-300' => !$isDanger,
            ])
        >
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>@endif
            <span class="flex-1">{{ $dropdownItem->getLabel() }}</span>
            @if ($dropdownItem->getBadge())
                <span class="ms-auto badge bg-{{ $dropdownItem->getBadgeColor() }}">{{ $dropdownItem->getBadge() }}</span>
            @endif
        </button>
    @else
        <span
            role="menuitem"
            @class([
                'flex items-center gap-2 px-4 py-2 text-sm opacity-50',
                'text-gray-700 dark:text-gray-300',
            ])
        >
            @if ($dropdownItem->getIcon())<i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>@endif
            <span class="flex-1">{{ $dropdownItem->getLabel() }}</span>
            @if ($dropdownItem->getBadge())
                <span class="ms-auto badge bg-{{ $dropdownItem->getBadgeColor() }}">{{ $dropdownItem->getBadge() }}</span>
            @endif
        </span>
    @endif
</li>
