{{--
    Toolbar partial: dropdown item dispatcher.
    Routes to the correct sub-partial based on DropdownItem::itemType().
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem $dropdownItem */
    $dropdownItemType = $dropdownItem->getItemType();
@endphp

@if ($dropdownItemType === 'action')
    @include('architect::toolbar.partials.dropdown-action', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'toggle')
    @include('architect::toolbar.partials.dropdown-toggle', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'checkbox')
    @include('architect::toolbar.partials.dropdown-checkbox', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'radio-group')
    @include('architect::toolbar.partials.dropdown-radio-group', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'text-input')
    @include('architect::toolbar.partials.dropdown-text-input', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'submenu')
    @include('architect::toolbar.partials.dropdown-submenu', compact('dropdownItem', 'parentDropKey', 'dropKey'))
@elseif ($dropdownItemType === 'separator')
    @include('architect::toolbar.partials.dropdown-separator', compact('dropdownItem'))
@elseif ($dropdownItemType === 'link-group')
    @include('architect::toolbar.partials.dropdown-link-group', compact('dropdownItem'))
@endif
