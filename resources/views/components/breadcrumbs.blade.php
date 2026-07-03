@props([
    'items' => [],
])

<nav class="arch-breadcrumbs" aria-label="Breadcrumb" {{ $attributes }}>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        @foreach ($items as $item)
            @php
                $hasMenu = ! empty($item['menu']);
                $isCurrent = empty($item['url']);
            @endphp

            @if ($hasMenu)
                <span class="arch-breadcrumb-item arch-breadcrumb-item--dropdown" x-data="{ open: false }" @click.outside="open = false">
                    @if (! $isCurrent)
                        <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                    @else
                        <span data-current="true" aria-current="page">{{ $item['title'] }}</span>
                    @endif

                    <button type="button"
                            class="arch-breadcrumb-item__toggle"
                            aria-haspopup="menu"
                            :aria-expanded="open"
                            @click.prevent="open = !open">
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>

                    <ul class="arch-breadcrumb-item__menu"
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:leave="transition ease-in duration-75"
                        role="menu">
                        @foreach ($item['menu'] as $menuItem)
                            <li role="none">
                                <a role="menuitem" href="{{ $menuItem['url'] ?? '#' }}">
                                    {{ $menuItem['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </span>
            @else
                <span class="arch-breadcrumb-item"
                      @if ($isCurrent) data-current="true" aria-current="page" @endif>
                    @if (! $isCurrent)
                        <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                    @else
                        {{ $item['title'] }}
                    @endif
                </span>
            @endif
        @endforeach
    @endif
</nav>
