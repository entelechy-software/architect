@php
/**
 * @var \Entelechy\Architect\Panels\Panels\ImageCarouselPanel $panel
 * @var \Entelechy\Architect\Panels\ArchitectPanelDefinition $def
 */
$images   = $panel->getImages();
$advance  = $panel->getAutoAdvanceSeconds();
$showDots = $panel->getShowDots();
@endphp

<div
    class="arch-panel arch-panel--carousel"
    x-data="{
        current: 0,
        images: {{ Js::from($images) }},
        @if ($advance) autoAdvance: {{ $advance * 1000 }}, @else autoAdvance: null, @endif
        init() {
            if (this.autoAdvance) {
                setInterval(() => { this.current = (this.current + 1) % this.images.length; }, this.autoAdvance);
            }
        }
    }"
>
    @if ($def->title)
        <h3 class="arch-panel__title">{{ $def->title }}</h3>
    @endif

    <div class="arch-carousel">
        <template x-for="(img, idx) in images" :key="idx">
            <div class="arch-carousel__slide" x-show="current === idx">
                <template x-if="img.href">
                    <a :href="img.href">
                        <img :src="img.src" :alt="img.caption ?? ''" class="arch-carousel__image" />
                    </a>
                </template>
                <template x-if="!img.href">
                    <img :src="img.src" :alt="img.caption ?? ''" class="arch-carousel__image" />
                </template>
                <template x-if="img.caption">
                    <p class="arch-carousel__caption" x-text="img.caption"></p>
                </template>
            </div>
        </template>
    </div>

    @if ($showDots)
        <div class="arch-carousel__dots">
            <template x-for="(img, idx) in images" :key="idx">
                <button
                    type="button"
                    class="arch-carousel__dot"
                    :class="{ 'is-active': current === idx }"
                    @click="current = idx"
                    :aria-label="'Slide ' + (idx + 1)"
                ></button>
            </template>
        </div>
    @endif
</div>
