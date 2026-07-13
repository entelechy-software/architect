<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Export;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Permissions\FieldVisibilityFilter;
use Entelechy\Architect\Table\Permissions\RedactionFilter;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV export of a TableBuilder's current view.
 *
 * Strategy:
 *   - Honour the QueryContext (search/filters/sort/scope/archived) so
 *     "what you see is what you export".
 *   - When $selectedIds is non-empty, post-filter rows to that set so
 *     the floating bulk action's "Export selected" only emits chosen
 *     rows; null/empty exports the entire visible page set.
 *   - Apply FieldVisibilityFilter::visibleColumns + stripRow so layer-4
 *     column visibility is preserved in the CSV.
 *   - Page through the data model 500 rows at a time; cap at MAX_ROWS
 *     to keep the CSV bounded — over-cap exports should go through a
 *     queued job (Phase 6 — left as a TODO here).
 *
 * Returns a Symfony StreamedResponse so Laravel can flush the file
 * download incrementally without buffering it in memory.
 */
final class CsvStreamExporter
{
    public const MAX_ROWS = 5000;

    public const PAGE_SIZE = 500;

    /**
     * @param  array<int, int>|null  $selectedIds
     */
    public function stream(
        ArchitectTableDefinition $definition,
        QueryContext $context,
        ?array $selectedIds,
        ?Authenticatable $user,
    ): StreamedResponse {
        $dataModel = app($definition->dataModelClass);
        if (! $dataModel instanceof ArchitectDataModel) {
            throw new \LogicException(
                'TableBuilder export: dataModelClass must implement ArchitectDataModel'
            );
        }

        $visibility = app(FieldVisibilityFilter::class);
        $redaction = app(RedactionFilter::class);
        $columns = $visibility->visibleColumns($user, $definition);
        $allowedFlip = $visibility->allowedKeysForRow($columns);
        $selectedFlip = $selectedIds !== null && $selectedIds !== []
            ? array_flip($selectedIds)
            : null;

        $filename = self::filename($definition);

        $response = new StreamedResponse(function () use (
            $context, $dataModel, $visibility, $redaction, $columns, $allowedFlip, $selectedFlip, $user
        ): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                throw new \RuntimeException('CsvStreamExporter: failed to open php://output');
            }

            // Header row.
            $headers = [];
            foreach ($columns as $column) {
                $headers[] = $column->getLabel();
            }
            fputcsv($out, $headers);

            $written = 0;

            foreach (ExportRowIterator::iterate($dataModel, $context, self::MAX_ROWS) as $row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                if ($selectedFlip !== null && ! isset($selectedFlip[$id])) {
                    continue;
                }

                $stripped = $redaction->redactRow($user, $columns, $visibility->stripRowUsingAllowed($row, $allowedFlip));

                $line = [];
                foreach ($columns as $column) {
                    $value = $stripped[$column->getKey()] ?? '';
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $line[] = (string) $value;
                }
                fputcsv($out, $line);
                $written++;
                if ($written >= self::MAX_ROWS) {
                    break;
                }
            }

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="'.$filename.'"',
        );
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private static function filename(ArchitectTableDefinition $definition): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($definition->pageTitle ?? $definition->title ?? 'export')) ?? 'export';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'export';
        }

        return $slug.'-'.CarbonImmutable::now('UTC')->format('Ymd-His').'.csv';
    }
}
