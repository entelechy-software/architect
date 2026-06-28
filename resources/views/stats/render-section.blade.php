{{--
    Dispatch partial — routes a section to its type-specific partial.

    Variables:
        $section      ArchitectStatDefinition
        $data         mixed — resolved section data (from DashboardEngine)
        $granularity  string — H | D | M | A (passed through for chart sections)
--}}
@php
    $partial = match ($section->type) {
        'metrics'  => 'architect::stats.sections.metrics',
        'chart'    => 'architect::stats.sections.chart',
        'table'    => 'architect::stats.sections.table',
        'crosstab' => 'architect::stats.sections.crosstab',
        default    => null,
    };
@endphp

@if ($partial)
    @include($partial, [
        'section'     => $section,
        'data'        => $data,
        'granularity' => $granularity ?? 'D',
    ])
@endif
