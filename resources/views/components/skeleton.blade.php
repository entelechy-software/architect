@props([
    'variant' => 'lines',
    'lines' => 3,
])

<div class="arch-skeleton"
     data-variant="{{ $variant }}"
     @if ($variant === 'lines') data-lines="{{ $lines }}" @endif
     {{ $attributes }}
     role="status"
     aria-label="{{ __('architect::architect.loading') }}">
    @if ($variant === 'lines')
        @for ($i = 0; $i < $lines; $i++)
            <div class="arch-skeleton__line"></div>
        @endfor
    @endif
</div>
