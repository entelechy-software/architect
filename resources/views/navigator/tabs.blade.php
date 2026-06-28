{{--
        Architect Navigator: tabs style.
        Supports two display variants controlled by $definition->tabStyle:
          'button' (default) — pill-button row using <x-architect::button>
          'page'             — underline border-bottom row using plain <a>/<button> tags

        SPA mode (when $definition->spa is true):
          Tab content is embedded inline. Alpine handles client-side switching.
          URL sync via ?tab= query param (configurable).
--}}
@php
    use Entelechy\Architect\Navigator\Items\Tab;
    use Entelechy\Architect\Navigator\Items\NavSeparator;
    use Entelechy\Architect\Table\Livewire\Engine as ArchitectEngine;

    $activeItem = $definition->activeItem($path);
    $tabStyle   = $definition->tabStyle ?? 'button';

    $alignClass = match ($definition->align) {
        'center' => 'justify-center',
        'end'    => 'justify-end',
        'fill'   => 'justify-stretch',
        default  => 'justify-start',
    };
@endphp

@php $wrapInCard = $wrapInCard ?? true; @endphp

@if ($definition->spa)
{{-- ── SPA mode: inline content, Alpine switching ────────────────────── --}}
@php
    $spaInitial  = $definition->initialTab(request()->query($definition->urlParam ?? '', ''));
    $spaUrlParam = $definition->urlParam;
    $spaLazy     = $definition->loadingStrategy === 'lazy';
@endphp
<div
    x-data="{
        activeTab: '{{ $spaInitial }}',
        tabBreadcrumbs: {{ json_encode($tabBreadcrumbs ?? []) }},
        switchTab(slug) {
            this.activeTab = slug;
            @if ($spaUrlParam)
            const url = new URL(window.location.href);
            url.searchParams.set('{{ $spaUrlParam }}', slug);
            history.replaceState(null, '', url.toString());
            @endif
            @if ($definition->inheritBreadcrumbs)
            window.dispatchEvent(new CustomEvent('architect:breadcrumbs', {
                detail: this.tabBreadcrumbs[slug] || []
            }));
            @endif
        }
    }"
>
    {{-- Tab bar --}}
    <div class="mb-3">
    @if ($tabStyle === 'page')
    <div class="flex border-b border-gray-200 dark:border-gray-700 {{ $alignClass }} module-navigator module-navigator--page-tabs" role="tablist">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <span class="module-navigator__separator px-3 self-center text-gray-300 dark:text-gray-600" aria-hidden="true">|</span>
            @elseif ($item instanceof Tab)
                @php
                    $isDisabled = $item->isDisabled();
                    $itemIcon   = $item->getIcon();
                    $tabSlug    = $item->getSlug();
                @endphp
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors {{ $isDisabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                    :class="activeTab === '{{ $tabSlug }}'
                        ? 'border-[#047db5] text-[#047db5] dark:border-[#5ab4d8] dark:text-[#5ab4d8] bg-white dark:bg-transparent'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                    role="tab"
                    :aria-selected="activeTab === '{{ $tabSlug }}'"
                    @if (! $isDisabled) x-on:click="switchTab('{{ $tabSlug }}')" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($itemIcon) <i class="{{ $itemIcon }} mr-1.5"></i> @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs ml-1">{{ $item->getBadge() }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
    @else
    {{-- button style: plain <button> elements so Alpine :class bindings work correctly --}}
    <div class="flex flex-wrap gap-2 {{ $alignClass }} module-navigator module-navigator--tabs" role="tablist">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <span class="module-navigator__separator px-1 text-gray-400" aria-hidden="true">|</span>
            @elseif ($item instanceof Tab)
                @php
                    $isDisabled = $item->isDisabled();
                    $itemIcon   = $item->getIcon();
                    $tabSlug    = $item->getSlug();
                    $usesLegacyIcon = is_string($itemIcon) && (str_contains($itemIcon, ' ') || str_starts_with($itemIcon, 'fa'));
                @endphp
                <button
                    type="button"
                    class="arch-btn arch-btn-sm {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    :class="activeTab === '{{ $tabSlug }}' ? 'arch-btn-secondary' : 'arch-btn-outline-secondary'"
                    role="tab"
                    :aria-selected="activeTab === '{{ $tabSlug }}'"
                    @if (! $isDisabled) x-on:click="switchTab('{{ $tabSlug }}')" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($usesLegacyIcon) <i class="{{ $itemIcon }}"></i> @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
    @endif
    </div>

    {{-- Tab panels --}}
    @foreach ($definition->items as $item)
        @if ($item instanceof Tab && ! $item->isDisabled())
            @php $tabSlug = $item->getSlug(); @endphp
            <div x-show="activeTab === '{{ $tabSlug }}'" x-cloak>
                @if ($item->getContentType() === 'architect')
                    @livewire(ArchitectEngine::class, ['definitionClass' => $item->getArchitectClass(), 'embedded' => true], key('spa-tab-' . $tabSlug))
                @elseif ($item->getContentType() === 'component')
                    @livewire($item->getComponentClass(), $item->getComponentProps(), key('spa-tab-' . $tabSlug))
                @elseif ($item->getContentType() === 'view')
                    @include($item->getViewPath(), $item->getViewData())
                @endif
            </div>
        @endif
    @endforeach
</div>

@elseif ($tabStyle === 'page')
{{-- ── Page style: underline border-bottom tabs ───────────────────────── --}}
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
    <div class="flex border-b border-gray-200 dark:border-gray-700 {{ $alignClass }} module-navigator module-navigator--page-tabs" role="tablist">
    @foreach ($definition->items as $item)
        @if ($item instanceof NavSeparator)
            <span class="module-navigator__separator px-3 self-center text-gray-300 dark:text-gray-600" aria-hidden="true">|</span>
        @elseif ($item instanceof Tab)
            @php
                $isActive   = $activeItem === $item;
                $isDisabled = $item->isDisabled();
                $itemIcon   = $item->getIcon();
                $activeClasses = $isActive
                    ? 'border-[#047db5] text-[#047db5] dark:border-[#5ab4d8] dark:text-[#5ab4d8] bg-white dark:bg-transparent'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200';
                $disabledClasses = $isDisabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
            @endphp
            @if ($item->getOpenInTab())
                @php
                    $navOIT      = $item->getOpenInTab();
                    $navTabType  = $navOIT['type'];
                    $navFallback = $navOIT['fallback'] ?: ($item->getHref() ?? '');
                @endphp
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors {{ $activeClasses }} {{ $disabledClasses }}"
                    role="tab"
                    :aria-current="'{{ $isActive ? 'page' : 'false' }}'"
                    @if (! $isDisabled) @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($itemIcon) <i class="{{ $itemIcon }} mr-1.5"></i> @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs ml-1">{{ $item->getBadge() }}</span>
                    @endif
                </button>
            @else
                <a
                    href="{{ $item->getHref() ?? '#' }}"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition-colors {{ $activeClasses }} {{ $disabledClasses }}"
                    role="tab"
                    @if ($isActive) aria-current="page" @endif
                >
                    @if ($itemIcon) <i class="{{ $itemIcon }} mr-1.5"></i> @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs ml-1">{{ $item->getBadge() }}</span>
                    @endif
                </a>
            @endif
        @endif
    @endforeach
    </div>
</x-architect::navigator.shell>

@else
{{-- ── Button style: pill-button row (default) ────────────────────────── --}}
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
        <div class="flex flex-wrap gap-2 {{ $alignClass }} module-navigator module-navigator--tabs" role="tablist">
    @foreach ($definition->items as $item)
        @if ($item instanceof NavSeparator)
            <span class="module-navigator__separator px-1 text-gray-400" aria-hidden="true">|</span>
        @elseif ($item instanceof Tab)
            @php
                $isActive   = $activeItem === $item;
                $isDisabled = $item->isDisabled();
                $itemIcon = $item->getIcon();
                $usesLegacyIcon = is_string($itemIcon)
                    && (str_contains($itemIcon, ' ') || str_starts_with($itemIcon, 'fa'));
                $usesBladeIcon = is_string($itemIcon) && ! $usesLegacyIcon;
            @endphp
            @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                <x-architect::button size="sm" color="gray" :outlined="! $isActive" disabled aria-disabled="true">
                    @if ($usesLegacyIcon)
                        <i class="{{ $itemIcon }}"></i>
                    @elseif ($usesBladeIcon)
                        <i class="{{ $itemIcon }}"></i>
                    @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                    @endif
                </x-architect::button>
            @elseif ($item->getOpenInTab())
                @php
                    $navOIT      = $item->getOpenInTab();
                    $navTabType  = $navOIT['type'];
                    $navFallback = $navOIT['fallback'] ?: ($item->getHref() ?? '');
                @endphp
                <x-architect::button
                    size="sm"
                    color="gray"
                    :outlined="! $isActive"
                    role="tab"
                    :aria-current="$isActive ? 'page' : null"
                    @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                >
                    @if ($usesLegacyIcon)
                        <i class="{{ $itemIcon }}"></i>
                    @elseif ($usesBladeIcon)
                        <i class="{{ $itemIcon }}"></i>
                    @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                    @endif
                </x-architect::button>
            @else
                @if ($isActive)
                    <x-architect::button
                        size="sm"
                        color="gray"
                        :outlined="! $isActive"
                        :href="$item->getHref()"
                        tag="a"
                        role="tab"
                        aria-current="page"
                    >
                        @if ($usesLegacyIcon)
                            <i class="{{ $itemIcon }}"></i>
                        @elseif ($usesBladeIcon)
                            <i class="{{ $itemIcon }}"></i>
                        @endif
                        {{ $item->getLabel() }}
                        @if ($item->getBadge() !== null)
                            <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                        @endif
                    </x-architect::button>
                @else
                    <x-architect::button
                        size="sm"
                        color="gray"
                        :outlined="! $isActive"
                        :href="$item->getHref()"
                        tag="a"
                        role="tab"
                    >
                        @if ($usesLegacyIcon)
                            <i class="{{ $itemIcon }}"></i>
                        @elseif ($usesBladeIcon)
                            <i class="{{ $itemIcon }}"></i>
                        @endif
                        {{ $item->getLabel() }}
                        @if ($item->getBadge() !== null)
                            <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                        @endif
                    </x-architect::button>
                @endif
            @endif
        @endif
    @endforeach
        </div>
    </x-architect::navigator.shell>

@endif{{-- end SPA / @if ($tabStyle === 'page') / @else --}}
