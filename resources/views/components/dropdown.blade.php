@props([
    'align' => 'left',
])

<div class="arch-dropdown" x-data="{ open: false }" @click.outside="open = false" {{ $attributes }}>
    <div @click="open = !open">{{ $trigger }}</div>
    <ul class="arch-dropdown-menu"
        data-align="{{ $align }}"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:leave="transition ease-in duration-75">
        {{ $slot }}
    </ul>
</div>
