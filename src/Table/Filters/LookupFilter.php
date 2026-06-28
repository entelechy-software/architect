<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * AJAX-backed single-select lookup filter.
 *
 * The endpoint must return items in the standard lookup shape used
 * throughout this application: [{val: "1", txt: "Name"}, ...]
 */
class LookupFilter extends ArchitectFilter
{
    private string $sourceUrl = '';

    public function source(string $url): static
    {
        $clone = clone $this;
        $clone->sourceUrl = $url;

        return $clone;
    }

    public function getSource(): string
    {
        return $this->sourceUrl;
    }

    public function blade(): string
    {
        return 'architect::table.filters.lookup-filter';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $query->where($this->name(), '=', $value);
    }
}
