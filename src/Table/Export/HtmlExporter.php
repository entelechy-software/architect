<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Export;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Permissions\FieldVisibilityFilter;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a self-contained HTML page with table styling of a
 * TableBuilder's current view. Useful for printing or saving a
 * human-readable snapshot.
 *
 * Mirrors CsvStreamExporter for context/permission/cap handling.
 */
final class HtmlExporter
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
        $columns = $visibility->visibleColumns($user, $definition);
        $allowedFlip = $visibility->allowedKeysForRow($columns);
        $selectedFlip = $selectedIds !== null && $selectedIds !== []
            ? array_flip($selectedIds)
            : null;

        $filename = self::filename($definition);
        $title = $definition->title ?? 'Export';
        $generatedAt = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s').' UTC';

        $response = new StreamedResponse(function () use (
            $context, $dataModel, $visibility, $columns, $allowedFlip, $selectedFlip, $title, $generatedAt
        ): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                throw new \RuntimeException('HtmlExporter: failed to open php://output');
            }

            // Document chrome.
            fwrite($out, '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">');
            fwrite($out, '<title>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</title>');
            fwrite($out, '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            fwrite($out, '<style>body{padding:1.5rem;font-family:system-ui,sans-serif}h1{font-size:1.4rem}.meta{color:#6c757d;font-size:.85rem;margin-bottom:1rem}@media print{a{color:#000;text-decoration:none}}</style>');
            fwrite($out, '</head><body>');
            fwrite($out, '<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>');
            fwrite($out, '<div class="meta">Generated '.htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8').'</div>');
            fwrite($out, '<table class="table table-striped table-bordered table-sm"><thead><tr>');
            foreach ($columns as $column) {
                fwrite($out, '<th>'.htmlspecialchars($column->getLabel(), ENT_QUOTES, 'UTF-8').'</th>');
            }
            fwrite($out, '</tr></thead><tbody>');

            $written = 0;

            foreach (ExportRowIterator::iterate($dataModel, $context, self::MAX_ROWS) as $row) {
                $id = isset($row['id']) ? (int) $row['id'] : 0;
                if ($selectedFlip !== null && ! isset($selectedFlip[$id])) {
                    continue;
                }

                $stripped = $visibility->stripRowUsingAllowed($row, $allowedFlip);

                fwrite($out, '<tr>');
                foreach ($columns as $column) {
                    $value = $stripped[$column->getKey()] ?? '';
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    }
                    fwrite($out, '<td>'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'</td>');
                }
                fwrite($out, '</tr>');
                $written++;
                if ($written >= self::MAX_ROWS) {
                    break;
                }
            }

            fwrite($out, '</tbody></table>');
            if ($written >= self::MAX_ROWS) {
                fwrite($out, '<p class="text-muted small">Output truncated at '.self::MAX_ROWS.' rows.</p>');
            }
            fwrite($out, '</body></html>');

            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
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
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($definition->title ?? 'export')) ?? 'export';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'export';
        }

        return $slug.'-'.CarbonImmutable::now('UTC')->format('Ymd-His').'.html';
    }
}
