<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Http;

use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Generic lookup endpoint for all TableBuilder lookup columns.
 *
 * Served at `/_module-table/lookup` (registered in ModuleTableServiceProvider,
 * protected by `auth:admin`). Replaces per-module lookup route closures:
 *
 *   - Slide-out / page-mode create form (form-input.blade.php)
 *   - Import wizard preview lookup error cells (_step-preview.blade.php)
 *
 * Column declarations supply all the metadata the endpoint needs:
 *
 *   ->rules('required|exists:activities,id')   ← table + key column
 *   ->editAs('activity_id')                    ← FK to write
 *   ->importResolveBy                          ← default label/search col
 *   ->searchColumns([...])                     ← multi-column search
 *   ->labelUsing(fn ($r) => ...)               ← custom label formatter
 *   ->cascadeFrom('activity_id')               ← ?parent= filter
 *
 * @see Column::source()  Explicit URL override (external APIs only)
 */
final class LookupController
{
    public function __invoke(Request $request): JsonResponse
    {
        $definitionClass = (string) $request->query('definition', '');
        $columnKey = (string) $request->query('column', '');
        $term = trim((string) $request->query('q', ''));
        $parent = $request->query('parent');
        // `extra` is forwarded by columns using ->extraDataFrom() but only
        // consumed by custom ->source() endpoints; the auto-lookup ignores it.
        $autoload = (bool) $request->query('autoload', false);
        $limitParam = $request->query('limit');
        $limit = is_numeric($limitParam) ? max(1, min(500, (int) $limitParam)) : 50;

        if ($definitionClass === '' || ! class_exists($definitionClass)) {
            throw new HttpException(404, 'Unknown definition');
        }

        $instance = app($definitionClass);
        $def = $instance::definition();
        $column = $this->findColumn($def, $columnKey);

        $plan = $this->planFor($column);
        $connection = $this->resolveConnection($def);

        $labelCol = $column->getImportResolveBy() ?? 'name';
        if ($labelCol === 'id') {
            // 'id' means "no resolution" for the importer — for display we fall back to 'name'
            $labelCol = 'name';
        }
        $searchCols = $column->getSearchColumns() ?? [$labelCol];
        $labelCallback = $column->getLabelUsing();
        $optGroupsCol = $column->getOptGroupsColumn();

        // When a labelUsing callback derives the display text from multiple
        // columns (e.g. members: first_name + last_name), $labelCol may not
        // exist on the table at all (members has no 'name' column). Only
        // include it in SELECT / ORDER BY when no callback is present.
        $baseCols = $labelCallback !== null
            ? array_merge(['id'], $searchCols)
            : array_merge(['id', $labelCol], $searchCols);
        if ($optGroupsCol !== null) {
            $baseCols[] = $optGroupsCol;
        }
        $selectCols = array_values(array_unique($baseCols));
        $orderCol = $labelCallback !== null ? ($searchCols[0] ?? 'id') : $labelCol;

        // Multi-item reverse-lookup — used by MultiTagFilter to restore selected
        // option labels when re-opening the filter panel with active values.
        // A single request handles all missing IDs: ?ids[]=1&ids[]=3
        $idsLookup = $request->query('ids');
        if (is_array($idsLookup) && count($idsLookup) > 0) {
            $ids = array_values(array_map('intval', array_filter($idsLookup, 'is_numeric')));
            $rows = DB::connection($connection)
                ->table($plan['table'])
                ->whereIn('id', $ids)
                ->get($selectCols);

            $results = $rows->map(function (object $row) use ($labelCallback, $labelCol): array {
                $text = $labelCallback !== null
                    ? (string) ($labelCallback)($row)
                    : (string) ($row->{$labelCol} ?? '');

                return ['id' => (int) $row->id, 'text' => $text];
            })->values();

            return response()->json($results);
        }

        // Single-item reverse-lookup — used by LookupFilter to restore the
        // selected option label when re-opening the filter panel with an active value.
        $idLookup = $request->query('id');
        if ($idLookup !== null && is_numeric($idLookup)) {
            $row = DB::connection($connection)
                ->table($plan['table'])
                ->where('id', (int) $idLookup)
                ->first($selectCols);

            if (! $row) {
                return response()->json([]);
            }

            $text = $labelCallback !== null
                ? (string) ($labelCallback)($row)
                : (string) ($row->{$labelCol} ?? '');

            return response()->json([['id' => (int) $row->id, 'text' => $text]]);
        }

        $query = DB::connection($connection)->table($plan['table']);

        // Skip the LIKE clause when autoload is requested with no search term:
        // the dropdown opened without typing and we just want the latest N rows.
        if ($term !== '') {
            // Honour the column's opt-in search mode (default 'contains' for
            // back-compat). 'starts_with' lets MySQL use a B-tree index and
            // turns a full table scan into an index range seek.
            $likePattern = $column->getLookupSearchMode() === 'starts_with'
                ? $term.'%'
                : '%'.$term.'%';

            $query->where(function ($q) use ($searchCols, $likePattern): void {
                foreach ($searchCols as $col) {
                    $q->orWhere($col, 'like', $likePattern);
                }
            });
        }

        // Cascading: parent FK restricts results (e.g. positions per activity).
        // $childColumn: null = use parent key name; false = frontend-only (skip WHERE).
        $cascadeFrom = $column->getCascadeFrom();
        $childColumn = $column->getCascadeChildColumn() ?? $cascadeFrom;
        if ($cascadeFrom !== null && is_string($childColumn) && $parent !== null && is_numeric($parent)) {
            $query->where($childColumn, (int) $parent);
        }

        // For autoload, sort newest first so the user sees the most
        // recently created records by default; otherwise sort by label.
        if ($autoload && $term === '') {
            $query->orderByDesc('id');
        } else {
            if ($optGroupsCol !== null) {
                $query->orderBy($optGroupsCol);
            }
            $query->orderBy($orderCol);
        }

        $rows = $query->limit($limit)->get($selectCols);

        $mapped = $rows->map(function (object $row) use ($labelCallback, $labelCol, $optGroupsCol): array {
            $text = $labelCallback !== null
                ? (string) ($labelCallback)($row)
                : (string) ($row->{$labelCol} ?? '');

            return [
                'id' => (int) $row->id,
                'text' => $text,
                'group' => $optGroupsCol !== null ? (string) ($row->{$optGroupsCol} ?? '') : null,
            ];
        });

        // optGroups: emit nested grouped results — [{text, children: [...]}, ...]
        if ($optGroupsCol !== null) {
            $grouped = $mapped
                ->groupBy('group')
                ->map(fn ($items, $groupName) => [
                    'text' => (string) $groupName,
                    'children' => $items->map(fn ($i) => ['id' => $i['id'], 'text' => $i['text']])
                        ->values()
                        ->all(),
                ])
                ->values();

            return response()->json($grouped);
        }

        $results = $mapped->map(fn ($i) => ['id' => $i['id'], 'text' => $i['text']])->values();

        return response()->json($results);
    }

    /**
     * Build a lookup URL for the given definition and column.
     *
     * The endpoint is protected by `auth:admin` middleware. No signed
     * URL is needed: the definition class must exist in the codebase
     * (`class_exists` check) and the endpoint is only reachable by an
     * authenticated admin.
     */
    public static function urlFor(string $definitionClass, string $columnKey): string
    {
        return route('architect.lookup', [
            'definition' => $definitionClass,
            'column' => $columnKey,
        ]);
    }

    private function findColumn(ArchitectTableDefinition $def, string $key): Column
    {
        foreach ($def->columns as $column) {
            if ($column->getKey() === $key) {
                return $column;
            }
        }

        throw new HttpException(404, 'Unknown column "'.$key.'"');
    }

    /**
     * @return array{table: string}
     */
    private function planFor(Column $column): array
    {
        $rules = (string) $column->getRules();

        if (! preg_match('/(?:^|\|)exists:([^|]+)/i', $rules, $m)) {
            throw new HttpException(422, 'Column has no exists: rule — cannot auto-derive lookup table');
        }

        $params = explode(',', $m[1]);
        $table = trim($params[0]);

        // Strip connection prefix if present (`customer.activities` → `activities`).
        if (str_contains($table, '.')) {
            $table = substr($table, (int) strpos($table, '.') + 1);
        }

        if (str_contains($table, '\\')) {
            throw new HttpException(422, 'Model-class exists rules are not supported by the auto lookup endpoint');
        }

        return ['table' => $table];
    }

    private function resolveConnection(ArchitectTableDefinition $def): ?string
    {
        try {
            $dataModel = app($def->dataModelClass);
            $modelClass = $dataModel->modelClass();

            /** @var Model $model */
            $model = new $modelClass;

            return $model->getConnectionName();
        } catch (\Throwable) {
            return null;
        }
    }
}
