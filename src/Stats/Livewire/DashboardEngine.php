<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats\Livewire;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Navigator\Livewire\SpaSharedDefinition;
use Entelechy\Architect\Stats\ArchitectStatDefinition;
use Entelechy\Architect\Stats\DateRange;
use Entelechy\Architect\Stats\Elements\MetricCard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Container;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Livewire engine for Dashboard definition classes.
 *
 * Route wiring:
 *   Route::get('/advice/statistics', DashboardEngine::class)
 *       ->defaults('definitionClass', AdviceDashboardDefinition::class)
 *       ->name('advice.statistics');
 *
 * Definition class contract:
 *   Must have a public static definition(): ArchitectStatDefinition method
 *   where the definition was built with type('dashboard').
 */
#[Layout('layouts.app')]
class DashboardEngine extends Component
{
    /**
     * FQCN of the class whose ::definition() returns the dashboard definition.
     */
    public string $definitionClass = '';

    /** Date range filter — persisted as Livewire properties for wire:model. */
    public string $dateFrom = '';

    public string $dateTo = '';

    /** Active chart granularity: H | D | M | A */
    public string $granularity = 'D';

    /** Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    public function mount(string $definitionClass): void
    {
        abort_unless(
            class_exists($definitionClass) && method_exists($definitionClass, 'definition'),
            404
        );

        $this->definitionClass = $definitionClass;

        // Default: last 30 days
        $this->dateFrom = CarbonImmutable::now()->subDays(30)->toDateString();
        $this->dateTo = CarbonImmutable::now()->toDateString();

        // Respect the definition's default granularity
        /** @var ArchitectStatDefinition $def */
        $def = ($this->definitionClass)::definition();
        $this->granularity = $def->defaultGranularity;
    }

    public function updateGranularity(string $granularity): void
    {
        $this->granularity = in_array($granularity, ['H', 'D', 'M', 'A'], true)
            ? $granularity
            : 'D';

        // Dispatch browser event so the architectChart Alpine component can update
        // its series without a full page re-render. The chart section partial
        // also re-renders via Livewire, but the event gives ApexCharts a chance
        // to animate the transition client-side.
        $this->dispatch('architect:chart-update');
    }

    public function render(): View
    {
        /** @var ArchitectStatDefinition $definition */
        $definition = ($this->definitionClass)::definition();

        $range = $this->buildDateRange();

        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            if ($definition->permission !== null
                && ! app(PermissionResolver::class)->can(auth()->user(), $definition->permission)) {
                throw new AuthorizationException('You do not have permission to view this dashboard.');
            }

            // Resolve each section's data
            $resolvedSections = $this->resolveSections($definition->sections, $range);
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            $this->dispatch('architect:unauthorized');
            $resolvedSections = [];
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this dashboard. Please try again.';
            report($e);
            $resolvedSections = [];
        } finally {
            $this->isLoading = false;
        }

        // Share breadcrumbs with the layout topbar
        if ($definition->breadcrumbs !== []) {
            view()->share('definition', new SpaSharedDefinition(
                breadcrumbs: $definition->breadcrumbs,
            ));
        }

        return view('architect::stats.engine', [
            'definition' => $definition,
            'resolvedSections' => $resolvedSections,
            'range' => $range,
            'granularity' => $this->granularity,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'hasChart' => $this->definitionHasChart($definition),
        ]);
    }

    /**
     * Export table/crosstab/metrics sections as a multi-sheet .xlsx download.
     *
     * @param  string[]  $visibleKeys  When non-empty, only sections whose key
     *                                 is in this list are exported. Passed by
     *                                 the Alpine dashboardEdit component so
     *                                 hidden sections are excluded.
     */
    public function export(array $visibleKeys = []): BinaryFileResponse
    {
        /** @var ArchitectStatDefinition $definition */
        $definition = ($this->definitionClass)::definition();
        $range = $this->buildDateRange();

        $sections = $visibleKeys === []
            ? $definition->sections
            : array_values(array_filter(
                $definition->sections,
                static fn (ArchitectStatDefinition $s): bool => in_array($s->key, $visibleKeys, true),
            ));

        $sheets = $this->buildExportSheets($sections, $range);

        $filename = str($definition->pageTitle ?? 'statistics')
            ->slug('-')
            ->append('-'.now()->format('Y-m-d'))
            ->append('.xlsx')
            ->toString();

        return Excel::download(new class($sheets) implements WithMultipleSheets
        {
            /** @var array<int, object> */
            private readonly array $sheets;

            /** @param array<int, object> $sheets */
            public function __construct(array $sheets)
            {
                $this->sheets = $sheets;
            }

            /** @return array<int, object> */
            public function sheets(): array
            {
                return $this->sheets;
            }
        }, $filename);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildDateRange(): DateRange
    {
        return new DateRange(
            from: CarbonImmutable::parse($this->dateFrom)->startOfDay(),
            to: CarbonImmutable::parse($this->dateTo)->endOfDay(),
        );
    }

    /**
     * Resolve each section's callable, injecting DateRange and/or Container
     * as appropriate. Returns an array parallel to $sections with resolved data.
     *
     * @param  ArchitectStatDefinition[]  $sections
     * @return array<int, mixed>
     */
    private function resolveSections(array $sections, DateRange $range): array
    {
        $app = Container::getInstance();
        $resolved = [];

        foreach ($sections as $section) {
            $resolved[] = match ($section->type) {
                'metrics' => $this->resolveMetrics($section, $range, $app),
                'chart' => $this->resolveChart($section, $range, $app),
                'table',
                'crosstab' => $this->resolveData($section, $range, $app),
                default => null,
            };
        }

        return $resolved;
    }

    /**
     * Resolve MetricCard values for a metrics section.
     * Returns an array of resolved card data suitable for the metrics Blade partial.
     *
     * @return array<int, array{card: MetricCard, value: mixed, live: bool}>
     */
    private function resolveMetrics(ArchitectStatDefinition $section, DateRange $range, Container $app): array
    {
        // If cards were provided via a closure (the ->cards(fn...) path), resolve them now
        if ($section->dataCallable !== null) {
            $callable = $section->dataCallable;

            return $section->dataRequiresDateRange
                ? $callable($range, $app)
                : $callable($app);
        }

        // Static array of MetricCard objects — resolve non-live values now
        return array_map(function (MetricCard $card) use ($range, $app): array {
            if ($card->isLive()) {
                // Live cards are rendered as wire:poll children — don't resolve here
                return ['card' => $card, 'value' => null, 'live' => true];
            }

            $callable = $card->getValueCallable();
            $value = $callable !== null
                ? ($card->isDateRangeRequired() ? $callable($range, $app) : $callable($app))
                : null;

            return ['card' => $card, 'value' => $value, 'live' => false];
        }, $section->cards);
    }

    /**
     * Resolve a chart section's data.
     *
     * Time-series charts (style=line) call seriesCallable with granularity.
     * Categorical charts (style=bar|donut) call dataCallable — no granularity.
     *
     * @return array<string, mixed>
     */
    private function resolveChart(ArchitectStatDefinition $section, DateRange $range, Container $app): array
    {
        // Time-series
        if (in_array($section->style, [null, 'line'], true)) {
            $callable = $section->seriesCallable;
            if ($callable === null) {
                return ['series' => [], 'granularity' => $this->granularity];
            }

            return [
                'series' => $callable($range, $this->granularity, $app),
                'granularity' => $this->granularity,
            ];
        }

        // Categorical (bar, donut, horizontalBar) — use dataCallable, no granularity
        $callable = $section->dataCallable;
        if ($callable === null) {
            return [];
        }

        return $section->dataRequiresDateRange
            ? $callable($range, $app)
            : $callable($app);
    }

    /**
     * Resolve a table or crosstab section's data callable.
     *
     * @return array<string, mixed>
     */
    private function resolveData(ArchitectStatDefinition $section, DateRange $range, Container $app): array
    {
        $callable = $section->dataCallable;
        if ($callable === null) {
            return [];
        }

        return $section->dataRequiresDateRange
            ? $callable($range, $app)
            : $callable($app);
    }

    /**
     * Build export sheets from resolved table/crosstab/metrics sections.
     *
     * @param  ArchitectStatDefinition[]  $sections
     * @return object[]
     */
    private function buildExportSheets(array $sections, DateRange $range): array
    {
        $app = Container::getInstance();
        $sheets = [];

        foreach ($sections as $section) {
            if (! in_array($section->type, ['table', 'crosstab', 'metrics'], true)) {
                continue;
            }

            $data = $this->resolveData($section, $range, $app);
            $title = $section->title ?? 'Sheet';

            $sheets[] = new class($title, $data, $section->type) implements FromArray, WithTitle
            {
                /** @var array<string, mixed> */
                private readonly array $data;

                /** @param array<string, mixed> $data */
                public function __construct(
                    private readonly string $title,
                    array $data,
                    private readonly string $type,
                ) {
                    $this->data = $data;
                }

                public function title(): string
                {
                    return substr($this->title, 0, 31);
                }

                /** @return array<int, mixed> */
                public function array(): array
                {
                    if ($this->type === 'crosstab') {
                        $header = array_merge([$this->data['rowLabel'] ?? 'Category'], $this->data['columns'] ?? []);
                        $rows = array_map(
                            fn (array $r): array => array_merge([$r['label']], $r['counts']),
                            $this->data['rows'] ?? [],
                        );

                        return array_merge([$header], $rows);
                    }

                    // table
                    $rows = array_merge(
                        [$this->data['columns'] ?? []],
                        $this->data['rows'] ?? [],
                    );
                    if (! empty($this->data['totals'])) {
                        $rows[] = $this->data['totals'];
                    }

                    return $rows;
                }
            };
        }

        return $sheets;
    }

    /**
     * Determine whether any child section is a time-series chart
     * (controls whether the granularity toggle is shown).
     */
    private function definitionHasChart(ArchitectStatDefinition $definition): bool
    {
        foreach ($definition->sections as $section) {
            if ($section->type === 'chart' && in_array($section->style, [null, 'line'], true)) {
                return true;
            }
        }

        return false;
    }
}
