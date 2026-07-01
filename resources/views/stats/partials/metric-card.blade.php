{{--
    Individual metric card partial.

    Variables:
        $item  array{card: MetricCard, value: mixed, live: bool}
--}}
@php
    /** @var \Entelechy\Architect\Stats\Elements\MetricCard $card */
    $card  = $item['card'];
    $value = $item['value'];
    $live  = $item['live'] ?? false;
    $trend = $card->getTrend();

    // Determine whether to use count-up animation.
    // Only for static (non-live) numeric values when animations are enabled.
    $numericValue  = null;
    $useCountUp    = false;
    if (! $live
        && $card->shouldCountUp()
        && config('architect.animations', true)
        && $value !== null
        && is_numeric((string) $value)
    ) {
        $numericValue = (float) $value;
        $useCountUp   = true;
    }
@endphp

<div class="arch-card h-full">
    <div class="arch-card-body flex items-center gap-4 py-4">

        @if ($card->getIcon())
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                <i class="{{ $card->getIcon() }} text-[#047db5] dark:text-[#5ab4d8]"></i>
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $card->getLabel() }}</p>

            @if ($live)
                {{-- Live cards update via wire:poll on the parent engine --}}
                <p class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    {{ $value ?? '—' }}
                </p>
            @elseif ($useCountUp)
                <p class="text-2xl font-semibold text-gray-800 dark:text-gray-100"
                    x-data="{ n: 0 }"
                    x-init="
                        (function() {
                            var target = {{ $numericValue }};
                            var start  = null;
                            var dur    = 800;
                            var ease   = function(p) { return p < 0.5 ? 2*p*p : -1+(4-2*p)*p; };
                            function step(ts) {
                                if (!start) start = ts;
                                var p = Math.min((ts - start) / dur, 1);
                                $data.n = Math.round(ease(p) * target);
                                if (p < 1) requestAnimationFrame(step);
                                else $data.n = target;
                            }
                            requestAnimationFrame(step);
                        })();
                    "
                    x-text="n.toLocaleString()"
                >{{ $value }}</p>
            @else
                <p class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    {{ $value ?? '—' }}
                </p>
            @endif

            @if ($trend !== null)
                @php
                    $trendUp    = $trend >= 0;
                    $trendClass = $trendUp
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-red-500 dark:text-red-400';
                    $trendIcon  = $trendUp ? 'fa-arrow-up' : 'fa-arrow-down';
                    $trendAbs   = abs($trend);
                @endphp
                <p class="text-xs mt-0.5 {{ $trendClass }}">
                    <i class="fas {{ $trendIcon }} text-[10px]"></i>
                    {{ number_format($trendAbs, 1) }}% vs prior period
                </p>
            @endif
        </div>

    </div>
</div>
