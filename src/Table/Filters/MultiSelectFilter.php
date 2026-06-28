<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Multi-select filter — renders a Lookup checkbox-list picker.
 *
 * The submitted value is an array of selected option keys. When the
 * array is empty or null the filter is a no-op. When one or more values
 * are selected a WHERE IN (...) constraint is applied.
 *
 * API:
 *   MultiSelectFilter::make('status')
 *       ->label('Status')
 *       ->options(['active' => 'Active', 'suspended' => 'Suspended'])
 *
 * Like SelectFilter, options may be a Closure for deferred loading:
 *   MultiSelectFilter::make('category_id')
 *       ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->all())
 */
class MultiSelectFilter extends ArchitectFilter
{
    /** @var array<int|string, string>|\Closure(): array<int|string, string> */
    private array|\Closure $options = [];

    /** @var array<int|string, string>|null Resolved options cache */
    private ?array $resolvedOptions = null;

    /**
     * @param  array<int|string, string>|\Closure(): array<int|string, string>  $options
     */
    public function options(array|\Closure $options): static
    {
        $clone = clone $this;
        $clone->options = $options;
        $clone->resolvedOptions = null;

        return $clone;
    }

    /**
     * @return array<int|string, string>
     */
    public function getOptions(): array
    {
        if ($this->resolvedOptions !== null) {
            return $this->resolvedOptions;
        }

        $this->resolvedOptions = $this->options instanceof \Closure
            ? ($this->options)()
            : $this->options;

        return $this->resolvedOptions;
    }

    public function blade(): string
    {
        return 'architect::table.filters.multi-select';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if (! is_array($value) || count($value) === 0) {
            return;
        }

        $query->whereIn($this->name(), $value);
    }
}
