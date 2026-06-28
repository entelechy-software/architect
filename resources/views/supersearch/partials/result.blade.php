{{--
    Supersearch — individual result row.

    @param array  $item        Rendered result array from ResultCard::renderFor()
    @param int    $groupIndex  Group index in $this->results
    @param int    $itemIndex   Item index within the group
    @param int    $flatIndex   Global sequential index used for keyboard navigation
--}}
@php
    $action      = $item['action'] ?? [];
    $actionType  = $action['type'] ?? null;
    $iconColour  = $item['iconColour'] ?? null;

    // Map icon colour names to Tailwind utility classes
    $colourMap = [
        'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',   'text' => 'text-blue-600 dark:text-blue-400'],
        'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-400'],
        'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30',     'text' => 'text-red-600 dark:text-red-400'],
        'amber'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600 dark:text-amber-400'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30','text' => 'text-purple-600 dark:text-purple-400'],
        'gray'   => ['bg' => 'bg-gray-100 dark:bg-gray-800',      'text' => 'text-gray-500 dark:text-gray-400'],
    ];
    $colours = $colourMap[$iconColour] ?? $colourMap['gray'];
@endphp

<div
    wire:key="ss-result-{{ $groupIndex }}-{{ $itemIndex }}"
    data-ss-flat="{{ $flatIndex }}"
    class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $item['dim'] ? 'opacity-50' : '' }}"
    onmouseenter="window.dispatchEvent(new CustomEvent('ss:hover',{detail:{idx:{{ $flatIndex }}}}))"
    onclick="console.log('[SS] row click', {{ $groupIndex }}, {{ $itemIndex }}, {{ \Illuminate\Support\Js::from($action) }}); window.dispatchEvent(new CustomEvent('ss:click',{detail:{g:{{ $groupIndex }},i:{{ $itemIndex }},a:{{ \Illuminate\Support\Js::from($action) }}}}))"
    role="option"
>
    {{-- Icon / avatar --}}
    <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg {{ $colours['bg'] }}">
        @if(!empty($item['avatar']))
            <img src="{{ $item['avatar'] }}" alt="" class="w-8 h-8 rounded-lg object-cover">
        @elseif(!empty($item['icon']))
            <i class="{{ $item['icon'] }} text-sm {{ $colours['text'] }}"></i>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        @if(!empty($item['eyebrow']))
            <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate leading-none mb-0.5">{{ $item['eyebrow'] }}</p>
        @endif

        <div class="flex items-center gap-2 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ $item['title'] ?? '—' }}
            </p>

            @if(!empty($item['badge']))
                @php
                    $badgeColourMap = [
                        'green'  => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                        'red'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        'amber'  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                        'blue'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                        'gray'   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                    ];
                    $badgeClass = $badgeColourMap[$item['badgeColour'] ?? 'gray'] ?? $badgeColourMap['gray'];
                @endphp
                <span class="inline-flex shrink-0 items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $badgeClass }}">
                    {{ $item['badge'] }}
                </span>
            @endif
        </div>

        @if(!empty($item['meta']))
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $item['meta'] }}</p>
        @endif

        @if(!empty($item['tags']))
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach($item['tags'] as $tag)
                    <span class="inline-flex items-center rounded px-1 py-px text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Trailing: timestamp + action hint --}}
    <div class="shrink-0 flex flex-col items-end gap-1">
        @if(!empty($item['timestamp']))
            <span class="text-[10px] text-gray-400 dark:text-gray-500 whitespace-nowrap">{{ $item['timestamp'] }}</span>
        @endif

        @if($actionType)
            <span class="text-[10px] text-gray-300 dark:text-gray-600">
                @switch($actionType)
                    @case('href')
                        <i class="fas fa-arrow-right"></i>
                        @break
                    @case('open-tab')
                        <i class="fas fa-external-link-alt"></i>
                        @break
                    @case('email')
                        <i class="fas fa-envelope"></i>
                        @break
                    @case('phone')
                        <i class="fas fa-phone"></i>
                        @break
                    @case('copy')
                        <i class="fas fa-copy"></i>
                        @break
                @endswitch
            </span>
        @endif
    </div>
</div>
