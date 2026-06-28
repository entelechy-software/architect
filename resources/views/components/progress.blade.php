@props([
    'value' => 0,
    'color' => 'primary',
    'size' => 'md',
    'label' => null,
    'showValue' => false,
])

<div class="arch-progress"
     data-color="{{ $color }}"
     data-size="{{ $size }}"
     role="progressbar"
     aria-valuenow="{{ $value }}"
     aria-valuemin="0"
     aria-valuemax="100"
     {{ $attributes }}>
    @if ($label || $showValue)
        <div class="arch-progress__header">
            @if ($label)<span class="arch-progress__label">{{ $label }}</span>@endif
            @if ($showValue)<span class="arch-progress__value">{{ $value }}%</span>@endif
        </div>
    @endif
    <div class="arch-progress__track">
        <div class="arch-progress__fill" style="width: {{ $value }}%"></div>
    </div>
</div>
