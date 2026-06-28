<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

use Entelechy\Architect\Table\QueryContext;

/**
 * Optional capability advertised by ArchitectDataModel implementations
 * that can stream rows for export/print without materialising the whole
 * result set in memory.
 *
 * Background:
 *   The mandatory forList() contract returns a LengthAwarePaginator,
 *   which is appropriate for the index page (one page at a time, total
 *   count needed for the footer) but wasteful for exports — every page
 *   triggers a fresh COUNT(*) and a fresh query construction. For a
 *   5,000-row CSV that's 10 round-trips per page * (5000 / 500) =
 *   100 redundant round-trips and the cumulative paginator overhead.
 *
 * Implementations should yield each row as an associative array in the
 * same shape returned by forList() ($paginator->items()), honouring
 * the QueryContext (search/filters/sort/scope/archived). The yielded
 * iterator is consumed exactly once; implementations are free to use
 * Eloquent's lazy()/cursor() under the hood.
 *
 * Exporters detect this capability via `instanceof` and fall back to
 * the paginated forList() path when it is not implemented, so adopting
 * the interface is purely additive.
 */
interface SupportsExportStreaming
{
    /**
     * Yield rows one at a time honouring the supplied QueryContext.
     *
     * The page/perPage values on the context are ignored by the
     * streaming path — implementations should iterate the entire
     * matching set lazily and let the consumer decide when to stop
     * (typically after MAX_ROWS rows).
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function forExportCursor(QueryContext $context): iterable;
}
