{{--
    Toolbar partial: DropdownSubmenu — an inline accordion sub-menu inside a dropdown.
    Clicking the submenu header toggles the sub-items in/out without closing the parent.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownSubmenu $dropdownItem */
    if (!$this->can($dropdownItem->getPermission())) return;

    $subMenuAlpineId = 'submenu_' . $parentDropKey . '_' . $dropdownItem->key();
@endphp

<li role="none" x-data="{ {{ $subMenuAlpineId }}: false }">
    {{-- Sub-menu header / toggle row --}}
    <button
        type="button"
        x-on:click="{{ $subMenuAlpineId }} = !{{ $subMenuAlpineId }}"
        @if ($dropdownItem->isDisabled()) disabled @endif
        role="menuitem"
        aria-haspopup="true"
        :aria-expanded="{{ $subMenuAlpineId }} ? 'true' : 'false'"
        @class([
            'flex w-full items-center justify-between px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
            'opacity-50 cursor-not-allowed' => $dropdownItem->isDisabled(),
        ])
    >
        <span class="flex items-center gap-2">
            @if ($dropdownItem->getIcon())
                <i class="{{ $dropdownItem->getIcon() }} w-4 text-center"></i>
            @endif
            {{ $dropdownItem->getLabel() }}
        </span>
        <i class="fas fa-chevron-down w-3 text-gray-400 transition-transform" :class="{{ $subMenuAlpineId }} ? 'rotate-180' : ''"></i>
    </button>

    {{-- Sub-items list (accordion body) --}}
    <ul
        x-show="{{ $subMenuAlpineId }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        role="menu"
        class="border-l-2 border-gray-200 dark:border-gray-600 ml-4 my-0.5"
    >
        @foreach ($dropdownItem->getSubItems() as $subItem)
            @include('architect::toolbar.partials.dropdown-item', [
                'dropdownItem'  => $subItem,
                'parentDropKey' => $parentDropKey,
            ])
        @endforeach
    </ul>
</li>
