<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats;

use Closure;
use Entelechy\Architect\Stats\Elements\MetricCard;
use Illuminate\Support\Str;

/**
 * Fluent builder for all stat component types.
 *
 * Returned by Architect::make('stat') and by the Architect::stats() alias.
 *
 * Usage:
 *
 *   // Full dashboard
 *   Architect::stats()
 *       ->pageTitle('Advice Statistics')
 *       ->breadcrumbs([...])
 *       ->defaultGranularity('D')
 *       ->export()
 *       ->section(Architect::make('stat')->type('metrics')->columns(3)->cards([...]), span: 12)
 *       ->section(Architect::make('stat')->type('chart')->style('line')->series(fn(...) => ...), span: 12)
 *       ->build();
 *
 *   // Standalone metric grid (above a table, etc.)
 *   Architect::make('stat')->type('metrics')
 *       ->columns(4)
 *       ->layout('inline')
 *       ->cards([MetricCard::make('Open Cases')->live(fn(Container $app) => ..., every: 30)])
 *       ->build();
 *
 *   // Standalone chart
 *   Architect::make('stat')->type('chart')->style('line')
 *       ->title('Case Activity')
 *       ->series(fn(DateRange $range, string $granularity, Container $app) => ...)
 *       ->build();
 *
 *   // Read-only table
 *   Architect::make('stat')->type('table')
 *       ->title('Closure Reasons')
 *       ->data(fn(DateRange $range, Container $app) => ...)
 *       ->build();
 *
 *   // Cross-tab matrix
 *   Architect::make('stat')->type('crosstab')
 *       ->title('Year of Study by Category')
 *       ->data(fn(DateRange $range, Container $app) => ...)
 *       ->build();
 */
final class StatBuilder
{
    // ── Core ──────────────────────────────────────────────────────────────
    private string $type = 'dashboard';

    private ?string $style = null;

    private ?string $title = null;

    private ?string $pageTitle = null;

    private ?string $key = null;

    /** @var array<int, array{title: string, url?: string}> */
    private array $breadcrumbs = [];

    private bool $card = true;

    // ── Dashboard ─────────────────────────────────────────────────────────

    /** @var ArchitectStatDefinition[] */
    private array $sections = [];

    /** @var int[] */
    private array $sectionSpans = [];

    private string $defaultGranularity = 'D';

    private ?int $pollSeconds = null;

    private bool $exportEnabled = false;

    // ── Metrics ───────────────────────────────────────────────────────────
    private int $columns = 4;

    private string $layout = 'inline';

    /** @var MetricCard[] */
    private array $cards = [];

    // ── Chart ─────────────────────────────────────────────────────────────
    private ?Closure $seriesCallable = null;

    // ── Table / CrossTab ──────────────────────────────────────────────────
    private ?Closure $dataCallable = null;

    private bool $dataRequiresDateRange = false;

    private ?int $scrollableHeight = null;

    private ?string $permission = null;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    // ── Type + style ──────────────────────────────────────────────────────

    /**
     * Set the stat element type.
     *
     * @param  string  $type  'dashboard' | 'metrics' | 'chart' | 'table' | 'crosstab'
     */
    public function type(string $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    /**
     * Set the visual style — used by 'chart' type.
     *
     * @param  string  $style  'line' | 'bar'
     */
    public function style(string $style): self
    {
        $clone = clone $this;
        $clone->style = $style;

        return $clone;
    }

    // ── Universal ─────────────────────────────────────────────────────────

    public function title(string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /**
     * Explicit key for personalisation (localStorage state, drag-and-drop, export filtering).
     * Auto-derived from title as a slug if not set.
     */
    public function key(string $key): self
    {
        $clone = clone $this;
        $clone->key = $key;

        return $clone;
    }

    /** Page heading shown in the topbar — dashboard type only. */
    public function pageTitle(string $pageTitle): self
    {
        $clone = clone $this;
        $clone->pageTitle = $pageTitle;

        return $clone;
    }

    /**
     * @param  array<int, array{title: string, url?: string}>  $breadcrumbs
     */
    public function breadcrumbs(array $breadcrumbs): self
    {
        $clone = clone $this;
        $clone->breadcrumbs = $breadcrumbs;

        return $clone;
    }

    /**
     * Whether to wrap this component in an arch-card container.
     * Default true. Ignored when the section is nested inside a dashboard.
     */
    public function card(bool $wrap = true): self
    {
        $clone = clone $this;
        $clone->card = $wrap;

        return $clone;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    /**
     * Add a section to the dashboard, with an optional 12-column grid span.
     * Sections whose spans sum to ≤12 on a row sit side-by-side; overflow wraps.
     *
     * @param  ArchitectStatDefinition  $section  Built sub-component
     * @param  int  $span  1–12 grid columns this section occupies (default 12 = full width)
     */
    public function section(ArchitectStatDefinition $section, int $span = 12): self
    {
        $clone = clone $this;
        $clone->sections[] = $section;
        $clone->sectionSpans[] = max(1, min(12, $span));

        return $clone;
    }

    /**
     * Default time granularity for chart sections.
     *
     * @param  string  $granularity  'H' | 'D' | 'M' | 'A'
     */
    public function defaultGranularity(string $granularity): self
    {
        $clone = clone $this;
        $clone->defaultGranularity = $granularity;

        return $clone;
    }

    /**
     * Auto-refresh all sections every N seconds (wire:poll on the engine).
     */
    public function poll(int $seconds): self
    {
        $clone = clone $this;
        $clone->pollSeconds = $seconds;

        return $clone;
    }

    /** Show an Export button that downloads a multi-sheet .xlsx. */
    public function export(): self
    {
        $clone = clone $this;
        $clone->exportEnabled = true;

        return $clone;
    }

    // ── Metrics ───────────────────────────────────────────────────────────

    /**
     * Number of cards per row in 'inline' layout.
     * Has no effect when layout is 'stacked'.
     */
    public function columns(int $columns): self
    {
        $clone = clone $this;
        $clone->columns = max(1, $columns);

        return $clone;
    }

    /**
     * Card arrangement direction.
     *
     * 'inline'  — LTR grid, cards fill rows left-to-right (default)
     * 'stacked' — top-to-bottom single column; ->columns() is ignored
     */
    public function layout(string $layout): self
    {
        $clone = clone $this;
        $clone->layout = $layout;

        return $clone;
    }

    /**
     * Provide the MetricCard definitions.
     * Accepts a plain array of MetricCard objects or a callable that returns one.
     *
     * @param  MetricCard[]|Closure  $cards
     */
    public function cards(array|Closure $cards): self
    {
        $clone = clone $this;
        if ($cards instanceof Closure) {
            // Wrap in a data callable so the engine resolves it at render time
            $clone->dataCallable = $cards;
            $clone->dataRequiresDateRange = $clone->detectClosureDateRange($cards);
        } else {
            $clone->cards = $cards;
        }

        return $clone;
    }

    // ── Chart ─────────────────────────────────────────────────────────────

    /**
     * Callable that returns the series data for the chart.
     *
     * Signature:
     *   fn(DateRange $range, string $granularity, Container $app): array
     *
     * Return shape:
     *   [['name' => 'New Cases', 'data' => [[timestamp_ms, value], ...]], ...]
     */
    public function series(Closure $callable): self
    {
        $clone = clone $this;
        $clone->seriesCallable = $callable;

        return $clone;
    }

    // ── Table / CrossTab ──────────────────────────────────────────────────

    /**
     * Callable that returns the structured data for a table or crosstab section.
     *
     * Table return shape:
     *   ['columns' => string[], 'rows' => array[], 'totals' => array|null]
     *
     * CrossTab return shape:
     *   ['rowLabel' => string, 'columns' => string[], 'rows' => [['label' => string, 'counts' => int[]], ...]]
     *
     * Callable signature:
     *   fn(DateRange $range, Container $app): array  — date-range-aware
     *   fn(Container $app): array                    — current-state only
     */
    public function data(Closure $callable): self
    {
        $clone = clone $this;
        $clone->dataCallable = $callable;
        $clone->dataRequiresDateRange = $clone->detectClosureDateRange($callable);

        return $clone;
    }

    /**
     * Make the table body scrollable at a fixed max-height, with a sticky header.
     *
     * @param  int  $maxHeightPx  Max body height in pixels before scrolling kicks in (default 320 ≈ 10 rows).
     */
    public function scrollable(int $maxHeightPx = 320): self
    {
        $clone = clone $this;
        $clone->scrollableHeight = $maxHeightPx;

        return $clone;
    }

    /**
     * Permission node gating the whole dashboard/section. When set,
     * DashboardEngine checks it on every render and dispatches
     * `architect:unauthorized` if the current user lacks it.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    // ── Build ─────────────────────────────────────────────────────────────

    public function build(): ArchitectStatDefinition
    {
        // Determine whether this definition (or any child section) needs
        // DateRange injected at render time.
        $requiresDateRange = $this->computeRequiresDateRange();

        // Auto-derive key from title if not explicitly set.
        $key = $this->key ?? ($this->title !== null ? Str::slug($this->title) : null);

        return new ArchitectStatDefinition(
            type: $this->type,
            style: $this->style,
            title: $this->title,
            key: $key,
            pageTitle: $this->pageTitle,
            breadcrumbs: $this->breadcrumbs,
            card: $this->card,
            requiresDateRange: $requiresDateRange,
            defaultGranularity: $this->defaultGranularity,
            pollSeconds: $this->pollSeconds,
            exportEnabled: $this->exportEnabled,
            sections: $this->sections,
            sectionSpans: $this->sectionSpans,
            columns: $this->columns,
            layout: $this->layout,
            cards: $this->cards,
            seriesCallable: $this->seriesCallable,
            dataCallable: $this->dataCallable,
            dataRequiresDateRange: $this->dataRequiresDateRange,
            scrollableHeight: $this->scrollableHeight,
            permission: $this->permission,
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Inspect a closure's first parameter to detect DateRange injection.
     */
    private function detectClosureDateRange(Closure $callable): bool
    {
        try {
            $ref = new \ReflectionFunction($callable);
            $params = $ref->getParameters();
            if (empty($params)) {
                return false;
            }
            $first = $params[0]->getType();

            return $first instanceof \ReflectionNamedType
                && $first->getName() === DateRange::class;
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * A dashboard requires date range if any of its child sections do,
     * or if the current type directly uses date-aware callables.
     */
    private function computeRequiresDateRange(): bool
    {
        // Direct callables
        if ($this->dataRequiresDateRange) {
            return true;
        }

        if ($this->seriesCallable !== null) {
            // Series callables are always date-range-aware by convention
            return true;
        }

        // MetricCard cards
        foreach ($this->cards as $card) {
            if ($card->isDateRangeRequired()) {
                return true;
            }
        }

        // Child sections
        foreach ($this->sections as $section) {
            if ($section->requiresDateRange) {
                return true;
            }
        }

        return false;
    }
}
