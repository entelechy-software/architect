<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * AJAX-backed multi-select filter — renders a Lookup input with multiple
 * selection enabled. Fetches options from a JSON lookup endpoint on demand;
 * selected items appear as removable tags (chips) inside the input.
 *
 * The submitted value is an array of selected IDs (as strings). An empty
 * array or null is always a no-op. By default applies WHERE IN; override
 * with applyUsing() for custom logic.
 *
 * API:
 *   MultiTagFilter::make('committee_position_id')
 *       ->label('Position')
 *       ->source(LookupController::urlFor(self::class, 'position_name'))
 *
 * For custom query logic:
 *   MultiTagFilter::make('tag_ids')
 *       ->source('/tags/lookup')
 *       ->applyUsing(fn (Builder $q, $v) => $q->whereHas('tags', fn ($q) => $q->whereIn('id', $v)))
 */
class MultiTagFilter extends ArchitectFilter
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
        return 'architect::table.filters.multi-tag';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if (! is_array($value) || count($value) === 0) {
            return;
        }

        $query->whereIn($this->name(), $value);
    }
}
