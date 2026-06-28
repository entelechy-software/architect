{{--
    Architect Navigator: sidebar style.
    Renders a vertical button list.
    Supports SPA mode (->spa()) for inline content switching.
--}}
@php
    use Entelechy\Architect\Navigator\Items\Tab;
    use Entelechy\Architect\Navigator\Items\NavSeparator;
    use Entelechy\Architect\Table\Livewire\Engine as ArchitectEngine;

    $activeItem = $definition->activeItem($path);
@endphp

@php $wrapInCard = $wrapInCard ?? true; @endphp

@if ($definition->spa)
{{-- ── SPA mode ──────────────────────────────────────────────────────── --}}
@php
    $spaInitial  = $definition->initialTab(request()->query($definition->urlParam ?? '', ''));
    $spaUrlParam = $definition->urlParam;
    $spaLazy     = $definition->loadingStrategy === 'lazy';
@endphp
<div
    class="flex gap-4"
    x-data="{
        activeTab: '{{ $spaInitial }}',
        switchTab(slug) {
            this.activeTab = slug;
            @if ($spaUrlParam)
            const url = new URL(window.location.href);
            url.searchParams.set('{{ $spaUrlParam }}', slug);
            history.replaceState(null, '', url.toString());
            @endif
        }
    }"
>
    {{-- Sidebar rail --}}
    <div class="module-navigator module-navigator--sidebar flex flex-col gap-1 shrink-0 w-48">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <div class="my-2 border-t border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
            @elseif ($item instanceof Tab)
                @php $isDisabled = $item->isDisabled(); $tabSlug = $item->getSlug(); @endphp
                <button
                    type="button"
                    class="arch-btn arch-btn-sm justify-start w-full {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    :class="activeTab === '{{ $tabSlug }}' ? 'arch-btn-secondary' : 'arch-btn-outline-secondary'"
                    @if (! $isDisabled) x-on:click="switchTab('{{ $tabSlug }}')" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($item->getIcon()) <i class="{{ $item->getIcon() }}"></i> @endif
                    <span class="flex-1 text-left">{{ $item->getLabel() }}</span>
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>

    {{-- Content panels --}}
    <div class="flex-1 min-w-0">
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
</div>

@else
{{-- ── Link mode ─────────────────────────────────────────────────────── --}}
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
        <div class="module-navigator module-navigator--sidebar flex flex-col gap-1">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <div class="my-2 border-t border-gray-200 dark:border-gray-700" aria-hidden="true"></div>
            @elseif ($item instanceof Tab)
                @php
                    $isActive   = $activeItem === $item;
                    $isDisabled = $item->isDisabled();
                @endphp
                @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                    <x-architect::button size="sm" color="gray" outlined disabled aria-disabled="true" class="justify-start w-full">
                        @if ($item->getIcon())
                            <i class="{{ $item->getIcon() }}"></i>
                        @endif
                        <span class="flex-1 text-left">{{ $item->getLabel() }}</span>
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
                        class="justify-start w-full"
                        @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                        @if ($isActive) aria-current="page" @endif
                    >
                        @if ($item->getIcon())
                            <i class="{{ $item->getIcon() }}"></i>
                        @endif
                        <span class="flex-1 text-left">{{ $item->getLabel() }}</span>
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
                        class="justify-start w-full"
                        @if ($isActive) aria-current="page" @endif
                    >
                        @if ($item->getIcon())
                            <i class="{{ $item->getIcon() }}"></i>
                        @endif
                        <span class="flex-1 text-left">{{ $item->getLabel() }}</span>
                        @if ($item->getBadge() !== null)
                            <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                        @endif
                    </x-architect::button>
                @endif
            @endif
        @endforeach
        </div>
</x-architect::navigator.shell>
@endif{{-- end SPA / link --}}
