<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats;

use Closure;
use Entelechy\Architect\Stats\Elements\MetricCard;

/**
 * Immutable value object produced by StatBuilder::build().
 *
 * Represents a single stat component of a given type. Dashboards nest
 * multiple ArchitectStatDefinition instances in $sections.
 *
 * Types:
 *   'dashboard' — full page with date filter, composed of child sections
 *   'metrics'   — grid of MetricCard KPI cards
 *   'chart'     — time-series or categorical chart (ApexCharts)
 *   'table'     — read-only summary table (no CRUD)
 *   'crosstab'  — dynamic-column matrix table
 */
final class ArchitectStatDefinition
{
    /**
     * @param  string  $type  dashboard | metrics | chart | table | crosstab
     * @param  string|null  $style  chart style: 'line' | 'bar' | null for other types
     * @param  string|null  $title  section title (shown above the section card)
     * @param  string|null  $key  stable slug used for personalisation state and export filtering
     * @param  string|null  $pageTitle  topbar page heading (dashboard type only)
     * @param  array<int, array{title: string, url?: string}>  $breadcrumbs
     * @param  bool  $card  wrap in arch-card; ignored inside a dashboard
     * @param  bool  $requiresDateRange  true if ANY section callable needs DateRange injected
     * @param  string  $defaultGranularity  H | D | M | A
     * @param  int|null  $pollSeconds  dashboard wire:poll interval; null = no auto-refresh
     * @param  bool  $exportEnabled  show Export button on dashboard
     * @param  ArchitectStatDefinition[]  $sections  child sections (dashboard only)
     * @param  int[]  $sectionSpans  parallel array of 12-col grid spans per section
     * @param  int  $columns  metrics: cards per row
     * @param  string  $layout  metrics: 'inline' | 'stacked'
     * @param  MetricCard[]  $cards  metrics: card definitions
     * @param  Closure|null  $seriesCallable  chart: fn(DateRange, string $granularity, Container): array
     * @param  Closure|null  $dataCallable  table/crosstab: fn(DateRange, Container): array
     *                                      OR  fn(Container): array (no date range)
     * @param  bool  $dataRequiresDateRange  whether $dataCallable needs DateRange injected
     * @param  int|null  $scrollableHeight  table: max-height in px before body scrolls; null = no scroll
     * @param  string|null  $permission  node gating the whole dashboard; null = no top-level gate
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $style,
        public readonly ?string $title,
        public readonly ?string $key,
        public readonly ?string $pageTitle,
        public readonly array $breadcrumbs,
        public readonly bool $card,
        public readonly bool $requiresDateRange,
        public readonly string $defaultGranularity,
        public readonly ?int $pollSeconds,
        public readonly bool $exportEnabled,
        public readonly array $sections,
        public readonly array $sectionSpans,
        public readonly int $columns,
        public readonly string $layout,
        public readonly array $cards,
        public readonly ?Closure $seriesCallable,
        public readonly ?Closure $dataCallable,
        public readonly bool $dataRequiresDateRange,
        public readonly ?int $scrollableHeight = null,
        public readonly ?string $permission = null,
    ) {}
}
