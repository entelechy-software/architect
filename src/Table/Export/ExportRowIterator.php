<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Export;

use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\SupportsExportStreaming;
use Entelechy\Architect\Table\QueryContext;

/**
 * Internal helper that yields rows for an export honouring the data
 * model's capability advertisement.
 *
 * If the data model implements SupportsExportStreaming we delegate to
 * forExportCursor() which iterates lazily (one row at a time, no
 * COUNT, no per-page overhead). Otherwise we fall back to the legacy
 * forList() pagination loop so older data models keep working.
 *
 * Exposed as a callable static rather than an instance service because
 * exporters already receive their dependencies as constructor-less
 * stateless utilities.
 */
final class ExportRowIterator
{
    /**
     * Maximum rows yielded across both code paths. Matches the per-
     * exporter caps so the iterator stops the same place either way.
     */
    public const PAGE_SIZE = 500;

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public static function iterate(
        ArchitectDataModel $dataModel,
        QueryContext $context,
        int $maxRows,
    ): \Generator {
        $emitted = 0;

        if ($dataModel instanceof SupportsExportStreaming) {
            foreach ($dataModel->forExportCursor($context) as $row) {
                if ($emitted >= $maxRows) {
                    return;
                }
                /** @var array<string, mixed> $row */
                yield (array) $row;
                $emitted++;
            }

            return;
        }

        // Legacy path: page through forList() in PAGE_SIZE chunks.
        $page = 1;
        while ($emitted < $maxRows) {
            $pageContext = new QueryContext(
                search: $context->search,
                filters: $context->filters,
                sortColumn: $context->sortColumn,
                sortDirection: $context->sortDirection,
                page: $page,
                perPage: self::PAGE_SIZE,
                includeArchived: $context->includeArchived,
                scope: $context->scope,
                filterDefinitions: $context->filterDefinitions,
            );

            $paginator = $dataModel->forList($pageContext);
            $items = $paginator->items();
            if ($items === []) {
                return;
            }

            foreach ($items as $rawRow) {
                if ($emitted >= $maxRows) {
                    return;
                }
                yield (array) $rawRow;
                $emitted++;
            }

            if ($paginator->currentPage() >= $paginator->lastPage()) {
                return;
            }
            $page++;
        }
    }
}
