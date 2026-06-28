@props([
    'style' => 'tabs',
    'items' => [],
])

<nav class="arch-tab-bar" data-style="{{ $style }}" {{ $attributes }}>
    @foreach ($items as $item)
        <a class="arch-tab"
           data-style="{{ $style }}"
           href="{{ $item['href'] ?? '#' }}"
           @if ($item['active'] ?? false) data-active="true" aria-current="page" @endif>
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
