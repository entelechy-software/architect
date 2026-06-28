{{--
    Stats table section — read-only summary table. No CRUD actions.

    Variables:
        $section  ArchitectStatDefinition (type = 'table')
        $data     array{
                    columns: string[],
                    rows: array[],
                    totals?: array|null,
                  }
--}}
@php
    $columns    = $data['columns'] ?? [];
    $rows       = $data['rows']    ?? [];
    $totals     = $data['totals']  ?? null;
    $scrollable = $section->scrollableHeight !== null;
@endphp

<div class="arch-card">
    @if ($section->title)
        <div class="arch-card-header">
            <h6 class="arch-card-title">{{ $section->title }}</h6>
        </div>
    @endif
    <div class="arch-card-body p-0">
        @if (empty($rows))
            <p class="text-sm text-gray-400 dark:text-gray-500 px-4 py-3">No data for selected period.</p>
        @elseif ($scrollable)
            {{-- Sticky header + scrolling body --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <tr>
                            @foreach ($columns as $col)
                                <th class="px-4 py-2.5 font-medium">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
                <div class="overflow-y-auto" style="max-height: {{ $section->scrollableHeight }}px">
                    <table class="w-full text-sm text-left">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    @foreach ($row as $cell)
                                        <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $cell !== null ? $cell : '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($totals !== null)
                    <table class="w-full text-sm text-left border-t-2 border-gray-200 dark:border-gray-600">
                        <tbody>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 font-semibold">
                                @foreach ($totals as $cell)
                                    <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200">{{ $cell !== null ? $cell : '' }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                @endif
            </div>
        @else
            {{-- Standard non-scrolling table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <tr>
                            @foreach ($columns as $col)
                                <th class="px-4 py-2.5 font-medium">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                @foreach ($row as $cell)
                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $cell !== null ? $cell : '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        @if ($totals !== null)
                            <tr class="bg-gray-50 dark:bg-gray-700/50 font-semibold border-t-2 border-gray-200 dark:border-gray-600">
                                @foreach ($totals as $cell)
                                    <td class="px-4 py-2.5 text-gray-800 dark:text-gray-200">{{ $cell !== null ? $cell : '' }}</td>
                                @endforeach
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
