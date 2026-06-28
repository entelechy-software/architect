@props([
    'items' => [],
])

<nav class="arch-breadcrumbs" aria-label="Breadcrumb" {{ $attributes }}>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        @foreach ($items as $item)
            <span class="arch-breadcrumb-item"
                  @if (empty($item['url'])) data-current="true" aria-current="page" @endif>
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                @else
                    {{ $item['title'] }}
                @endif
            </span>
        @endforeach
    @endif
</nav>
