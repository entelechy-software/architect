{{--
    Toolbar partial: ToolbarButton
    Renders a single action button — href, wire:click, event dispatch, or panel.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarButton $item */
    if (!$this->can($item->getPermission())) return;

    $isLink = $item->getHref() !== null;
    $tag    = $isLink ? 'a' : 'button';
@endphp

@if ($item->getHref() !== null)
    <a
        href="{{ $item->getHref() }}"
        @if ($item->isNewWindow()) target="_blank" rel="noopener noreferrer" @endif
        @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
        @class(['arch-btn arch-btn-sm', 'arch-btn-' . $item->getColor() => !$item->isOutlined(), 'arch-btn-outline-' . $item->getColor() => $item->isOutlined(), 'opacity-50 pointer-events-none' => $item->isDisabled()])
    >
        @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
        @if ($item->getLabel() !== '') <span>{{ $item->getLabel() }}</span>@endif
        @if ($item->getBadge() !== null)
            <span class="badge bg-{{ $item->getBadgeColor() }} ms-1">{{ $item->getBadge() }}</span>
        @endif
    </a>
@elseif ($item->getWireClick() !== null)
    <button
        type="button"
        wire:click="{{ $item->getWireClick() }}"
        @if ($item->isDisabled()) disabled @endif
        @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
        @class(['arch-btn arch-btn-sm', 'arch-btn-' . $item->getColor() => !$item->isOutlined(), 'arch-btn-outline-' . $item->getColor() => $item->isOutlined()])
    >
        @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
        @if ($item->getLabel() !== '') <span>{{ $item->getLabel() }}</span>@endif
        @if ($item->getBadge() !== null)
            <span class="badge bg-{{ $item->getBadgeColor() }} ms-1">{{ $item->getBadge() }}</span>
        @endif
    </button>
@elseif ($item->getDispatchEvent() !== null)
    <button
        type="button"
        @click="$dispatch('{{ $item->getDispatchEvent() }}', {{ \Illuminate\Support\Js::from($item->getDispatchPayload()) }})"
        @if ($item->isDisabled()) disabled @endif
        @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
        @class(['arch-btn arch-btn-sm', 'arch-btn-' . $item->getColor() => !$item->isOutlined(), 'arch-btn-outline-' . $item->getColor() => $item->isOutlined()])
    >
        @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
        @if ($item->getLabel() !== '') <span>{{ $item->getLabel() }}</span>@endif
        @if ($item->getBadge() !== null)
            <span class="badge bg-{{ $item->getBadgeColor() }} ms-1">{{ $item->getBadge() }}</span>
        @endif
    </button>
@else
    <button
        type="button"
        @if ($item->isDisabled()) disabled @endif
        @if ($item->getTooltip()) title="{{ $item->getTooltip() }}" @endif
        @class(['arch-btn arch-btn-sm', 'arch-btn-' . $item->getColor() => !$item->isOutlined(), 'arch-btn-outline-' . $item->getColor() => $item->isOutlined()])
    >
        @if ($item->getIcon())<i class="{{ $item->getIcon() }}"></i>@endif
        @if ($item->getLabel() !== '') <span>{{ $item->getLabel() }}</span>@endif
        @if ($item->getBadge() !== null)
            <span class="badge bg-{{ $item->getBadgeColor() }} ms-1">{{ $item->getBadge() }}</span>
        @endif
    </button>
@endif
