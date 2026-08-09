{{--
    Architect Toolbar — main engine view.

    Renders a horizontally split toolbar:
      - Left zone:   position='left' items (default)
      - Center zone: position='center' items
      - Right zone:  position='right' items (auto pushed via ml-auto)

    Alpine x-data initialises from localStorage and URL params on first mount,
    then calls $wire.loadFromLocalStorage() and $wire.loadFromUrl() to hydrate
    Livewire state. URL params take priority over localStorage.
--}}
@if ($hasError)
    <x-architect::callout type="danger">{{ $errorMessage }}</x-architect::callout>
@else
<div
    data-loading="{{ $isLoading ? 'true' : 'false' }}"
    wire:key="architect-toolbar-{{ $toolbarKey }}"
    x-data="architectToolbar({
        toolbarKey:    '{{ $toolbarKey }}',
        persistKeys:   {{ \Illuminate\Support\Js::from($this->buildPersistKeys($definition)) }},
        urlPersistKeys: {{ \Illuminate\Support\Js::from($this->buildUrlPersistKeys($definition)) }},
    })"
    x-init="init()"
    class="architect-toolbar flex items-center bg-white dark:bg-gray-900 {{ $definition->getSize() === 'sm' ? 'gap-1.5 px-2 py-1' : 'gap-2 px-4 py-2' }} {{ $definition->isBordered() ? 'border-b border-gray-200 dark:border-gray-700' : '' }} {{ $definition->isSticky() ? 'sticky top-0 z-20' : '' }}"
    role="toolbar"
    aria-label="Toolbar"
>
    {{-- Left items --}}
    <div class="flex items-center gap-2 flex-wrap">
        @foreach ($byPosition['left'] as $item)
            @include('architect::toolbar.partials.' . $item->getItemType(), [
                'item' => $item,
                'definition' => $definition,
            ])
        @endforeach
    </div>

    {{-- Center items (if any) --}}
    @if (!empty($byPosition['center']))
        <div class="flex items-center gap-2 mx-auto">
            @foreach ($byPosition['center'] as $item)
                @include('architect::toolbar.partials.' . $item->getItemType(), [
                    'item' => $item,
                    'definition' => $definition,
                ])
            @endforeach
        </div>
    @endif

    {{-- Spacer (if no center items and no explicit spacer) — auto push right items --}}
    @if (empty($byPosition['center']) && !empty($byPosition['right']))
        <div class="flex-1"></div>
    @endif

    {{-- Right items --}}
    @if (!empty($byPosition['right']))
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($byPosition['right'] as $item)
                @include('architect::toolbar.partials.' . $item->getItemType(), [
                    'item' => $item,
                    'definition' => $definition,
                ])
            @endforeach
        </div>
    @endif
</div>
@endif
