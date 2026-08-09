{{--
    Toolbar partial: ToolbarDropdown
    Renders a button that opens a dropdown menu containing DropdownItem children.
    Uses Alpine x-show for open/close toggle (no Bootstrap JS dependency).
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarDropdown $item */
    if (!$this->can($item->getPermission())) return;

    $dropKey = 'dropdown_' . $item->getKey();
@endphp

<div
    class="relative"
    x-data="{ {{ $dropKey }}: false }"
    @click.outside="{{ $dropKey }} = false"
    @keydown.escape="{{ $dropKey }} = false"
>
    <button
        type="button"
        @click="{{ $dropKey }} = !{{ $dropKey }}"
        aria-haspopup="true"
        :aria-expanded="{{ $dropKey }}.toString()"
        @if ($item->isDisabled()) disabled @endif
        @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
        @class(['arch-btn arch-btn-sm', 'arch-btn-' . $item->getColor() => !$item->isOutlined(), 'arch-btn-outline-' . $item->getColor() => $item->isOutlined()])
    >
        @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
        @if ($item->getLabel() !== '') <span class="{{ $item->getIcon() ? 'ms-1' : '' }}">{{ $item->getLabel() }}</span>@endif
        @if ($item->getBadge() !== null)
            <span class="badge bg-{{ $item->getBadgeColor() }} ms-1">{{ $item->getBadge() }}</span>
        @endif
        <i class="fas fa-chevron-down ms-1 text-xs"></i>
    </button>

    <div
        x-show="{{ $dropKey }}"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute left-0 z-50 mt-1 min-w-48 rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        role="menu"
    >
        <ul class="py-1" role="none">
            @foreach ($item->getItems() as $dropdownItem)
                @include('architect::toolbar.partials.dropdown-item', [
                    'dropdownItem'  => $dropdownItem,
                    'parentDropKey' => $item->getKey(),
                    'dropKey'       => $dropKey,
                ])
            @endforeach
        </ul>
    </div>
</div>
