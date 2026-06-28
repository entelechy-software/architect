@props([
    'name' => '',
    'width' => 'md',
    'position' => 'right',
    'dismissable' => true,
])

<div x-data="{ open: false }"
     x-on:architect:slide-over:open:{{ $name }}.window="open = true"
     x-on:architect:slide-over:close:{{ $name }}.window="open = false"
     x-cloak>
    <div class="arch-slide-over-backdrop" x-show="open" @if ($dismissable) @click="open = false" @endif></div>
    <div class="arch-slide-over"
         data-width="{{ $width }}"
         data-position="{{ $position }}"
         x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:leave="transition ease-in duration-150"
         {{ $attributes }}>
        <div class="arch-slide-over-header">
            <span class="arch-slide-over-title">{{ $title ?? '' }}</span>
        </div>
        <div class="arch-slide-over-body">{{ $slot }}</div>
        @isset($footer)
            <div class="arch-slide-over-footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
