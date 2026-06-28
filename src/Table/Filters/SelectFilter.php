<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

class SelectFilter extends ArchitectFilter
{
    /** @var array<int|string, string>|\Closure(): array<int|string, string> */
    private array|\Closure $options = [];

    /** @var array<int|string, string>|null Resolved options cache */
    private ?array $resolvedOptions = null;

    /**
     * @param  array<int|string, string>|\Closure(): array<int|string, string>  $options
     */
    public function options(array|\Closure $options): self
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
        return 'architect::table.filters.select';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        if (is_array($value)) {
            $query->whereIn($this->name(), $value);

            return;
        }

        $query->where($this->name(), '=', $value);
    }
}
