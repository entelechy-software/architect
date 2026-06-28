<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Date range filter — renders a Flatpickr range-mode picker.
 *
 * The submitted value is an associative array:
 *   ['from' => 'Y-m-d', 'to' => 'Y-m-d']
 *
 * Either key may be absent; when only one bound is supplied the filter
 * applies a single >= or <= constraint. When both are present a WHERE
 * BETWEEN is applied. Empty/null values are always a no-op.
 *
 * API:
 *   DateRangeFilter::make('created_at')
 *       ->label('Created')
 *       ->format('d/m/Y')        // display format passed to Flatpickr
 */
class DateRangeFilter extends ArchitectFilter
{
    private string $format = 'Y-m-d';

    public function format(string $format): static
    {
        $clone = clone $this;
        $clone->format = $format;

        return $clone;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function blade(): string
    {
        return 'architect::table.filters.date-range';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if (! is_array($value) || ($value['from'] ?? '') === '' && ($value['to'] ?? '') === '') {
            return;
        }

        $from = isset($value['from']) && $value['from'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $value['from'])?->startOfDay()
            : null;

        $to = isset($value['to']) && $value['to'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $value['to'])?->endOfDay()
            : null;

        if ($from !== null && $to !== null) {
            $query->whereBetween($this->name(), [$from, $to]);
        } elseif ($from !== null) {
            $query->where($this->name(), '>=', $from);
        } elseif ($to !== null) {
            $query->where($this->name(), '<=', $to);
        }
    }
}
