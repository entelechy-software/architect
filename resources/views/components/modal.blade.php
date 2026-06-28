@props([
    'name' => '',
    'size' => 'md',
    'dismissable' => true,
])

<div class="arch-modal"
     data-size="{{ $size }}"
     @if ($dismissable) data-dismissable="true" @endif
     x-data="{ open: false }"
     x-on:architect:modal:open:{{ $name }}.window="open = true"
     x-on:architect:modal:close:{{ $name }}.window="open = false"
     x-cloak>
    <div class="arch-modal__backdrop" x-show="open" @if ($dismissable) @click="open = false" @endif></div>
    <div class="arch-modal__wrapper" x-show="open">
        <div class="arch-modal__panel" {{ $attributes }}>
            @isset($title)
                <div class="arch-card-header">{{ $title }}</div>
            @endisset
            <div class="arch-card-body">{{ $slot }}</div>
            @isset($footer)
                <div class="arch-card-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
