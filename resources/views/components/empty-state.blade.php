@props([
    'icon'        => 'fas fa-inbox',
    'title'       => '',
    'description' => null,
])

<div class="arch-empty-state" {{ $attributes }}>
    <div class="arch-empty-state__icon">
        <i class="{{ $icon }}"></i>
    </div>
    <p class="arch-empty-state__title">{{ $title }}</p>
    @if($description)
        <p class="arch-empty-state__description">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="arch-empty-state__action">{{ $action }}</div>
    @endisset
</div>
