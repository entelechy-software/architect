{{--
    Toolbar partial: ToolbarBadge — a read-only reactive pill/counter.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarBadge $item */
@endphp

<span
    @class([
        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
        'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200' => $item->getColor() === 'primary',
        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $item->getColor() === 'secondary' || $item->getColor() === 'default',
        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $item->getColor() === 'danger',
        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $item->getColor() === 'success',
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $item->getColor() === 'warning',
        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $item->getColor() === 'info',
    ])
    @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
>
    @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
    {{ $item->getLabel() }}
</span>
