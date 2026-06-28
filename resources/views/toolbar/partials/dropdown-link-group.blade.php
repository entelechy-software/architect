{{--
    Toolbar partial: DropdownLinkGroup — a labelled group of plain anchor links.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownLinkGroup $dropdownItem */
@endphp

@if ($dropdownItem->getLabel() !== null)
    <li role="none" class="px-4 pt-2 pb-1">
        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ $dropdownItem->getLabel() }}
        </span>
    </li>
@endif

@foreach ($dropdownItem->getLinks() as $link)
    <li role="none">
        <a
            href="{{ $link['url'] }}"
            @if ($link['newWindow']) target="_blank" rel="noopener noreferrer" @endif
            role="menuitem"
            class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
            @if ($link['icon'] !== null)<i class="{{ $link['icon'] }} w-4 text-center"></i>@endif
            {{ $link['label'] }}
        </a>
    </li>
@endforeach
