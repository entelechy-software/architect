@props([
    'title' => '',
    'timestamp' => '',
    'icon' => null,
    'color' => 'primary',
    'isLast' => false,
])

<div class="arch-timeline-item"
     data-color="{{ $color }}"
     @if ($isLast) data-last="true" @endif
     {{ $attributes }}>
    <div class="arch-timeline-item__indicator">
        @if ($icon)<x-architect::icon :name="$icon" size="sm" />@endif
    </div>
    <div class="arch-timeline-item__connector"></div>
    <div class="arch-timeline-item__content">
        <p class="arch-timeline-item__title">{{ $title }}</p>
        <time class="arch-timeline-item__timestamp">{{ $timestamp }}</time>
        @if ($slot->isNotEmpty())
            <div class="arch-timeline-item__body">{{ $slot }}</div>
        @endif
    </div>
</div>
