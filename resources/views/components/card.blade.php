@props([
    'padding' => true,
    'shadow' => true,
])

<div class="arch-card"
     @if (! $shadow) data-shadow="false" @endif
     {{ $attributes }}>
    @isset($header)
        <div class="arch-card-header">{{ $header }}</div>
    @endisset

    <div class="arch-card-body" @if (! $padding) style="padding: 0" @endif>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="arch-card-footer">{{ $footer }}</div>
    @endisset
</div>
