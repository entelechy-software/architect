@props([
    'title' => '',
    'description' => null,
    'divided' => false,
])

<section class="arch-section" @if ($divided) data-divided="true" @endif {{ $attributes }}>
    <div class="arch-section__header">
        <div>
            <h2 class="arch-section__title">{{ $title }}</h2>
            @if ($description)
                <p class="arch-section__description">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="arch-section__actions">{{ $actions }}</div>
        @endisset
    </div>

    <div class="arch-section__body">
        {{ $slot }}
    </div>
</section>
