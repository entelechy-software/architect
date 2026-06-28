{{--
    CrossTab section — dynamic-column matrix table.

    Variables:
        $section  ArchitectStatDefinition (type = 'crosstab')
        $data     array{
                    rowLabel: string,
                    columns:  string[],
                    rows:     array{label: string, counts: int[]}[],
                  }
--}}
@php
    $rowLabel = $data['rowLabel'] ?? 'Category';
    $columns  = $data['columns'] ?? [];
    $rows     = $data['rows']    ?? [];
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
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">{{ $rowLabel }}</th>
                            @foreach ($columns as $col)
                                <th class="px-4 py-2.5 font-medium text-right">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ $row['label'] }}</td>
                                @foreach ($row['counts'] as $count)
                                    <td class="px-4 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                        {{ $count }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
