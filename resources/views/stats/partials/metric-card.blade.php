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
