@props([
    'type'        => 'info',
    'title'       => null,
    'dismissable' => false,
])

<div class="arch-callout"
     data-type="{{ $type }}"
     @if($dismissable) data-dismissable="true" @endif
     {{ $attributes }}>
    <div class="arch-callout__icon">
        @switch($type)
            @case('info')    <i class="fas fa-circle-info"></i> @break
            @case('success') <i class="fas fa-circle-check"></i> @break
            @case('warning') <i class="fas fa-triangle-exclamation"></i> @break
            @case('danger')  <i class="fas fa-circle-xmark"></i> @break
        @endswitch
    </div>
    <div class="arch-callout__body">
        @if($title)
            <p class="arch-callout__title">{{ $title }}</p>
        @endif
        <div class="arch-callout__content">{{ $slot }}</div>
    </div>
    @if($dismissable)
    <button class="arch-callout__dismiss"
            x-on:click="$el.closest('.arch-callout').remove()"
            type="button"
            aria-label="{{ __('architect::architect.cancel_button') }}">
        <i class="fas fa-xmark"></i>
    </button>
    @endif
</div>
