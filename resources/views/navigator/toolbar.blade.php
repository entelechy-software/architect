{{--
    ModuleNavigator: toolbar style.
    Renders a compact icon+label button row.

    Phase A: same rendering as buttons, distinguished by CSS class
    so themes can target it independently.
--}}
@php
    use Entelechy\Architect\Navigator\Items\NavButton;
    use Entelechy\Architect\Navigator\Items\Tab;
    use Entelechy\Architect\Navigator\Items\NavSeparator;

    $activeItem = $definition->activeItem($path);
@endphp

@php $wrapInCard = $wrapInCard ?? true; @endphp
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
    <div class="module-navigator module-navigator--toolbar flex flex-wrap items-center gap-2" role="toolbar" aria-label="Navigation toolbar">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <span class="mx-1 h-6 border-l border-gray-200 dark:border-gray-700" aria-hidden="true"></span>
            @elseif ($item instanceof NavButton || $item instanceof Tab)
                @php
                    $isActive   = $activeItem === $item;
                    $isDisabled = $item->isDisabled();
                    $color    = $item instanceof NavButton ? $item->getColor() : 'secondary';
                    $color      = in_array($color, ['primary', 'gray', 'success', 'danger', 'warning', 'info'], true)
                        ? $color
                        : 'gray';
                    $icon       = $item->getIcon();
                @endphp
                @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                    <x-architect::button size="sm" :color="$color" outlined disabled aria-disabled="true">
                        @if ($icon)
                            <i class="{{ $icon }}"></i>
                        @endif
                        <span>{{ $item->getLabel() }}</span>
                    </x-architect::button>
                @elseif ($item->getOpenInTab())
                    @php
                        $navOIT      = $item->getOpenInTab();
                        $navTabType  = $navOIT['type'];
                        $navFallback = $navOIT['fallback'] ?: ($item->getHref() ?? '');
                    @endphp
                    <x-architect::button
                        size="sm"
                        :color="$color"
                        :outlined="! $isActive"
                        @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                        @if ($isActive) aria-current="page" @endif
                    >
                        @if ($icon)
                            <i class="{{ $icon }}"></i>
                        @endif
                        <span>{{ $item->getLabel() }}</span>
                    </x-architect::button>
                @else
                    <x-architect::button
                        size="sm"
                        :color="$color"
                        :outlined="! $isActive"
                        :href="$item->getHref()"
                        tag="a"
                        @if ($isActive) aria-current="page" @endif
                    >
                        @if ($icon)
                            <i class="{{ $icon }}"></i>
                        @endif
                        <span>{{ $item->getLabel() }}</span>
                    </x-architect::button>
                @endif
            @endif
        @endforeach
    </div>
</x-architect::navigator.shell>
