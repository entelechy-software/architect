{{--
    ModuleNavigator: dropdown style.
    Renders a Filament trigger with a popover-style navigation list.
--}}
@php
    use Entelechy\Architect\Navigator\Items\Tab;
    use Entelechy\Architect\Navigator\Items\NavButton;
    use Entelechy\Architect\Navigator\Items\NavSeparator;

    $activeItem = $definition->activeItem($path);

    $triggerLabel = $activeItem !== null && method_exists($activeItem, 'label')
        ? $activeItem->getLabel()
        : 'Navigate';
@endphp

@php $wrapInCard = $wrapInCard ?? true; @endphp
@php $wrapInCard = $wrapInCard ?? true; @endphp
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
    <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block module-navigator module-navigator--dropdown">
        <x-architect::button
            size="sm"
            color="gray"
            outlined
            icon="{{ $activeItem !== null && method_exists($activeItem, 'getIcon') && $activeItem->getIcon() ? $activeItem->getIcon() : 'fas fa-chevron-down' }}"
            @click="open = ! open"
            :aria-expanded="open"
        >
            {{ $triggerLabel }}
        </x-architect::button>

        <div
            x-show="open"
            x-transition
            class="absolute left-0 z-20 mt-2 min-w-56 rounded-xl border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-900"
            role="menu"
        >
            @foreach ($definition->items as $item)
                @if ($item instanceof NavSeparator)
                    <div class="my-2 border-t border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
                @elseif ($item instanceof Tab || $item instanceof NavButton)
                    @php
                        $isActive   = $activeItem === $item;
                        $isDisabled = $item->isDisabled();
                    @endphp
                    @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-400" aria-disabled="true">
                            @if ($item->getIcon())
                                <i class="{{ $item->getIcon() }}"></i>
                            @endif
                            <span>{{ $item->getLabel() }}</span>
                        </div>
                    @elseif ($item->getOpenInTab())
                        @php
                            $navOIT      = $item->getOpenInTab();
                            $navTabType  = $navOIT['type'];
                            $navFallback = $navOIT['fallback'] ?: ($item->getHref() ?? '');
                        @endphp
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm transition hover:bg-gray-100 dark:hover:bg-gray-800 {{ $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300' : 'text-gray-700 dark:text-gray-200' }}"
                            @if ($isActive) aria-current="page" @endif
                            @click="open = false; $dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                        >
                            @if ($item->getIcon())
                                <i class="{{ $item->getIcon() }}"></i>
                            @endif
                            <span>{{ $item->getLabel() }}</span>
                        </button>
                    @else
                        <a
                            href="{{ $item->getHref() }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition hover:bg-gray-100 dark:hover:bg-gray-800 {{ $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300' : 'text-gray-700 dark:text-gray-200' }}"
                            @if ($isActive) aria-current="page" @endif
                            @click="open = false"
                        >
                            @if ($item->getIcon())
                                <i class="{{ $item->getIcon() }}"></i>
                            @endif
                            <span>{{ $item->getLabel() }}</span>
                        </a>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</x-architect::navigator.shell>
