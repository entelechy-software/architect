{{--
    Architect Navigator: buttons style.
    Renders a button row with active-state URL matching.
    Supports SPA mode (->spa()) for inline content switching.
--}}
@php
    use Entelechy\Architect\Navigator\Items\NavButton;
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
    <div class="mb-3">
        <div class="flex flex-wrap gap-2 module-navigator module-navigator--buttons" role="group" aria-label="Navigation">
        @foreach ($definition->items as $item)
            @if ($item instanceof Tab)
                @php $isDisabled = $item->isDisabled(); $tabSlug = $item->getSlug(); @endphp
                <button
                    type="button"
                    class="arch-btn arch-btn-sm {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    :class="activeTab === '{{ $tabSlug }}' ? 'arch-btn-secondary' : 'arch-btn-outline-secondary'"
                    @if (! $isDisabled) x-on:click="switchTab('{{ $tabSlug }}')" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($item->getIcon()) <i class="{{ $item->getIcon() }}"></i> @endif
                    {{ $item->getLabel() }}
                </button>
            @endif
        @endforeach
        </div>
    </div>
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

@else
{{-- ── Link mode ─────────────────────────────────────────────────────── --}}
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
        <div class="flex flex-wrap gap-2 module-navigator module-navigator--buttons" role="group" aria-label="Navigation">
    @foreach ($definition->items as $item)
        @if ($item instanceof NavSeparator)
            {{-- Separators are visually skipped in button groups --}}
        @elseif ($item instanceof NavButton || $item instanceof Tab)
            @php
                $isActive   = $activeItem === $item;
                $isDisabled = $item->isDisabled();
                $color    = $item instanceof NavButton ? $item->getColor() : 'secondary';
                $filled     = $isActive;
            @endphp
            @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                <x-architect::button size="sm" :color="$color" :outlined="! $filled" disabled aria-disabled="true">
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
                    @endif
                    {{ $item->getLabel() }}
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
                    :outlined="! $filled"
                    @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                    @if ($isActive) aria-current="page" @endif
                >
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
                    @endif
                    {{ $item->getLabel() }}
                </x-architect::button>
            @else
                <x-architect::button
                    size="sm"
                    :color="$color"
                    :outlined="! $filled"
                    :href="$item->getHref()"
                    tag="a"
                    role="button"
                    @if ($isActive) aria-current="page" @endif
                >
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
                    @endif
                    {{ $item->getLabel() }}
                </x-architect::button>
            @endif
        @endif
    @endforeach
        </div>
</x-architect::navigator.shell>
@endif{{-- end SPA / link --}}
