{{-- Props are resolved by \Entelechy\Architect\View\Components\Button --}}
@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'arch-button']) }}
       data-color="{{ $color }}"
       data-variant="{{ $resolvedVariant }}"
       data-size="{{ $resolvedSize }}"
       @if($disabled) data-disabled="true" aria-disabled="true" @endif>
        @if($resolvedIcon) <i class="{{ $resolvedIcon }}"></i> @endif
        {{ $slot }}
        @if($resolvedTrailingIcon) <i class="{{ $resolvedTrailingIcon }}"></i> @endif
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'arch-button']) }}
            data-color="{{ $color }}"
            data-variant="{{ $resolvedVariant }}"
            data-size="{{ $resolvedSize }}"
            @disabled($disabled || $loading)>
        @if($loading)
            <span class="arch-loader" data-variant="spinner" style="width:1em;height:1em;"></span>
        @elseif($resolvedIcon)
            <i class="{{ $resolvedIcon }}"></i>
        @endif
        {{ $slot }}
        @if($resolvedTrailingIcon && !$loading) <i class="{{ $resolvedTrailingIcon }}"></i> @endif
    </button>
@endif
