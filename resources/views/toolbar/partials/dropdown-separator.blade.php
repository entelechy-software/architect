{{--
    Toolbar partial: DropdownSeparator — a divider or labelled section header.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Dropdown\DropdownSeparator $dropdownItem */
@endphp

@if ($dropdownItem->getLabel() !== null)
    <li role="none" class="px-4 pt-2 pb-1">
        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ $dropdownItem->getLabel() }}
        </span>
    </li>
@else
    <li role="none" class="my-1 border-t border-gray-200 dark:border-gray-700"></li>
@endif
