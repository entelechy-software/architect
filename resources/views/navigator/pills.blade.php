{{--
    Architect Navigator: pills style.
    Renders a navigation row using pill-like buttons.
    Supports SPA mode (->spa()) for inline content switching.
--}}
@php
    use Entelechy\Architect\Navigator\Items\Tab;
    use Entelechy\Architect\Navigator\Items\NavSeparator;
    use Entelechy\Architect\Table\Livewire\Engine as ArchitectEngine;

    $activeItem = $definition->activeItem($path);

    $alignClass = match ($definition->align) {
        'center' => 'justify-center',
        'end'    => 'justify-end',
        'fill'   => 'w-full [&>*]:flex-1',
        default  => '',
    };
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
        <div class="flex flex-wrap gap-2 {{ $alignClass }} module-navigator module-navigator--pills" role="tablist">
        @foreach ($definition->items as $item)
            @if ($item instanceof NavSeparator)
                <span class="module-navigator__separator px-1 text-gray-400" aria-hidden="true">|</span>
            @elseif ($item instanceof Tab)
                @php $isDisabled = $item->isDisabled(); $tabSlug = $item->getSlug(); @endphp
                <button
                    type="button"
                    class="arch-btn arch-btn-sm {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                    :class="activeTab === '{{ $tabSlug }}' ? 'arch-btn-secondary' : 'arch-btn-outline-secondary'"
                    role="tab"
                    :aria-selected="activeTab === '{{ $tabSlug }}'"
                    @if (! $isDisabled) x-on:click="switchTab('{{ $tabSlug }}')" @endif
                    @if ($isDisabled) disabled @endif
                >
                    @if ($item->getIcon()) <i class="{{ $item->getIcon() }}"></i> @endif
                    {{ $item->getLabel() }}
                    @if ($item->getBadge() !== null)
                        <span class="arch-badge arch-badge-secondary text-xs">{{ $item->getBadge() }}</span>
                    @endif
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
        <div class="flex flex-wrap gap-2 {{ $alignClass }} module-navigator module-navigator--pills" role="tablist">
    @foreach ($definition->items as $item)
        @if ($item instanceof NavSeparator)
            <span class="module-navigator__separator px-1 text-gray-400" aria-hidden="true">|</span>
        @elseif ($item instanceof Tab)
            @php
                $isActive   = $activeItem === $item;
                $isDisabled = $item->isDisabled();
            @endphp
            @if ($isDisabled || ($item->getHref() === null && $item->getOpenInTab() === null))
                <x-architect::button size="sm" color="gray" :outlined="! $isActive" disabled aria-disabled="true">
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
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
                    @click="$dispatch('architect:open-record', { type: '{{ $navTabType }}', props: {}, fallback: '{{ $navFallback }}' })"
                    role="tab"
                    @if ($isActive) aria-current="page" @endif
                >
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
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
                    @if ($isActive) aria-current="page" @endif
                >
                    @if ($item->getIcon())
                        <i class="{{ $item->getIcon() }}"></i>
                    @endif
                    {{ $item->getLabel() }}
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
