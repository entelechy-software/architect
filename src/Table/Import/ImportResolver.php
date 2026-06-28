<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import;

use Entelechy\Architect\Table\Column;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fuzzy-match user-typed CSV values into FK ids.
 *
 * For every column that uses `->editAs('foo_id')` (and hasn't opted
 * out via `editAs('foo_id', 'id')`) the resolver:
 *
 *   1. Infers the candidate table + match column from the column's
 *      `exists:table,col` rule (or the explicit second arg of
 *      `editAs`).
 *   2. Loads `[id => candidate_value]` once per import batch.
 *   3. For each cell:
 *        - exact (case-insensitive, ASCII-folded) → swap to id silently.
 *        - fuzzy ≥ AUTO_THRESHOLD                 → swap + record note.
 *        - fuzzy ≥ SUGGEST_THRESHOLD              → leave value, attach
 *                                                   top-3 suggestions.
 *        - else                                    → leave value (the
 *                                                   normal `exists:`
 *                                                   validator will fail it).
 *
 * Cascading lookups (e.g. positions filtered by activity) honour the
 * column's `cascadeFrom` setting: candidates are restricted to those
 * whose parent FK matches the row's already-resolved parent id.
 *
 * Output schema (returned from `resolve()`):
 *
 *   [
 *     'rows'        => list<array{values: array<string,string>, errors: array<string,list<string>>}>,
 *     'notes'       => array<int, array<string, string>>,            // [rowIdx][colKey] = "Auto-corrected: …"
 *     'suggestions' => array<int, array<string, list<string>>>,      // [rowIdx][colKey] = ["Swimming", "Swim Club", …]
 *   ]
 */
final class ImportResolver
{
    /** Score ≥ this auto-accepts a fuzzy match (no user confirmation). */
    public const AUTO_THRESHOLD = 90;

    /** Score in [SUGGEST_THRESHOLD, AUTO_THRESHOLD) shows hints but doesn't swap. */
    public const SUGGEST_THRESHOLD = 60;

    /** Maximum suggestions surfaced per cell. */
    private const MAX_SUGGESTIONS = 3;

    /**
     * @param  list<array{values: array<string,string>, errors: array<string,list<string>>, display_values?: array<string,string>}>  $rows
     * @param  array<string, Column>  $importColumns  Keyed by column key, in declared order.
     * @param  string|null  $connection  DB connection where lookup tables live (e.g. 'customer').
     * @return array{
     *     rows: list<array{values: array<string,string>, errors: array<string,list<string>>, display_values: array<string,string>}>,
     *     notes: array<int, array<string,string>>,
     *     suggestions: array<int, array<string,list<string>>>,
     * }
     */
    public function resolve(array $rows, array $importColumns, ?string $connection = null): array
    {
        $notes = [];
        $suggestions = [];

        // Plan resolvable columns + cache candidate sets.
        // $plans[$colKey] = ['table' => …, 'matchColumn' => …, 'parentColumn' => ?, 'parentColKey' => ?, 'candidates' => null]
        $plans = [];
        foreach ($importColumns as $key => $column) {
            if (! $column->shouldResolveImport()) {
                continue;
            }
            $plan = $this->planFor($column, $importColumns);
            if ($plan === null) {
                continue;
            }
            $plans[$key] = $plan;
        }

        if ($plans === []) {
            $rows = $this->hydrateDisplayValues($rows, $importColumns, $connection);

            return ['rows' => $rows, 'notes' => [], 'suggestions' => []];
        }

        foreach ($rows as $rowIdx => $row) {
            foreach ($plans as $colKey => $plan) {
                $raw = trim((string) ($row['values'][$colKey] ?? ''));
                if ($raw === '') {
                    continue; // let the `required` rule complain
                }

                // Skip if cell is already a numeric id — user may have
                // pre-resolved it themselves, or this is a re-validate
                // after the resolver already swapped on an earlier pass.
                if (ctype_digit($raw)) {
                    continue;
                }

                // Determine cascade filter (resolved parent id, if any).
                $parentId = null;
                if ($plan['parentColKey'] !== null) {
                    $parentRaw = (string) ($rows[$rowIdx]['values'][$plan['parentColKey']] ?? '');
                    if (ctype_digit(trim($parentRaw))) {
                        $parentId = (int) $parentRaw;
                    }
                }

                $candidates = $this->candidatesFor($plan, $connection, $parentId);
                if ($candidates === []) {
                    continue;
                }

                $match = $this->matchOne($raw, $candidates);

                if ($match['kind'] === 'exact' || $match['kind'] === 'auto') {
                    $rows[$rowIdx]['values'][$colKey] = (string) $match['id'];
                    // Preserve the human-readable label so the preview
                    // table can show text instead of the raw FK id.
                    $rows[$rowIdx]['display_values'][$colKey] = $match['name'];
                    if ($match['kind'] === 'auto') {
                        $notes[$rowIdx][$colKey] = sprintf(
                            'Auto-corrected: "%s" → "%s"',
                            $raw,
                            $match['name'],
                        );
                    }
                } elseif ($match['kind'] === 'suggest') {
                    $suggestions[$rowIdx][$colKey] = $match['suggestions'];
                }
                // 'none' → fall through; normal validator will report missing value.
            }
        }

        $rows = $this->hydrateDisplayValues($rows, $importColumns, $connection);

        return ['rows' => $rows, 'notes' => $notes, 'suggestions' => $suggestions];
    }

    /**
     * Back-fill display_values for every lookup FK cell that already
     * holds a numeric id but has no display label yet.
     *
     * This handles two cases the main resolve loop misses:
     *   1. Columns with importResolveBy('id') (e.g. member_name) —
     *      the resolver is skipped entirely; the CSV must supply an id.
     *   2. Columns that the resolver DID handle, but whose cells already
     *      contained a numeric id on entry — the ctype_digit guard skips
     *      those cells without writing a label.
     *
     * One DB query is made per FK column (not per cell or per row).
     *
     * @param  list<array{values: array<string,string>, errors: array<string,list<string>>, display_values?: array<string,string>}>  $rows
     * @param  array<string, Column>  $importColumns
     * @return list<array{values: array<string,string>, errors: array<string,list<string>>, display_values: array<string,string>}>
     */
    private function hydrateDisplayValues(array $rows, array $importColumns, ?string $connection): array
    {
        foreach ($importColumns as $colKey => $column) {
            if (! in_array($column->getType(), ['lookup', 'select'], true)) {
                continue;
            }
            // Derive table from exists: rule.
            $rules = (string) $column->getRules();
            if (! preg_match('/(?:^|\|)exists:([^|,]+)/i', $rules, $m)) {
                continue;
            }
            $table = trim($m[1]);
            if (str_contains($table, '.')) {
                $table = substr($table, (int) strpos($table, '.') + 1);
            }
            if (str_contains($table, '\\')) {
                continue;
            }

            // Collect ids that still need a label across all rows.
            $needsLabel = [];
            foreach ($rows as $rowIdx => $row) {
                $val = trim((string) ($row['values'][$colKey] ?? ''));
                if ($val === '' || ! ctype_digit($val)) {
                    continue;
                }
                if (isset($row['display_values'][$colKey])) {
                    continue; // already resolved by the main loop
                }
                $needsLabel[$rowIdx] = (int) $val;
            }

            if ($needsLabel === []) {
                continue;
            }

            $labelCallback = $column->getLabelUsing();
            $searchCols = $column->getSearchColumns();
            $labelCol = $column->getImportResolveBy() ?? 'name';
            if ($labelCol === 'id') {
                $labelCol = 'name';
            }

            // Determine which columns to SELECT.
            $selectCols = $labelCallback !== null
                ? array_values(array_unique(array_merge(['id'], $searchCols ?? [])))
                : ['id', $labelCol];

            try {
                $dbRows = DB::connection($connection)
                    ->table($table)
                    ->whereIn('id', array_unique(array_values($needsLabel)))
                    ->get($selectCols)
                    ->keyBy('id');
            } catch (\Throwable) {
                continue;
            }

            foreach ($needsLabel as $rowIdx => $id) {
                /** @var \stdClass|null $dbRow */
                $dbRow = $dbRows->get($id);
                if ($dbRow === null) {
                    continue;
                }
                $label = $labelCallback !== null
                    ? (string) ($labelCallback)($dbRow)
                    : (string) ($dbRow->{$labelCol} ?? $id);

                $rows[$rowIdx]['display_values'][$colKey] = $label;
            }
        }

        // Ensure every row has the key so blade isset() checks are safe.
        foreach ($rows as $idx => $row) {
            if (! isset($row['display_values'])) {
                $rows[$idx]['display_values'] = [];
            }
        }

        /** @var list<array{values: array<string,string>, errors: array<string,list<string>>, display_values: array<string,string>}> $rows */
        return $rows;
    }

    /**
     * Single-row variant for re-resolving after inline edits.
     *
     * @param  array<string,string>  $values
     * @param  array<string, Column>  $importColumns
     * @return array{
     *     values: array<string,string>,
     *     display_values: array<string,string>,
     *     notes: array<string,string>,
     *     suggestions: array<string,list<string>>,
     * }
     */
    public function resolveRow(array $values, array $importColumns, ?string $connection = null): array
    {
        $wrapped = [['values' => $values, 'errors' => [], 'display_values' => []]];
        $out = $this->resolve($wrapped, $importColumns, $connection);
        $row = $out['rows'][0];

        return [
            'values' => $row['values'],
            'display_values' => $row['display_values'],
            'notes' => $out['notes'][0] ?? [],
            'suggestions' => $out['suggestions'][0] ?? [],
        ];
    }

    /**
     * Build the lookup plan for a column from its `editAs` and rules.
     *
     * @param  array<string, Column>  $importColumns
     * @return array{table: string, matchColumn: string, parentColumn: ?string, parentColKey: ?string}|null
     */
    private function planFor(Column $column, array $importColumns): ?array
    {
        // Find the `exists:table,col` segment to discover the table.
        $rules = (string) $column->getRules();
        if (! preg_match('/(?:^|\|)exists:([^|]+)/i', $rules, $m)) {
            return null;
        }
        $params = $m[1];
        $parts = explode(',', $params);
        $table = trim($parts[0]);
        // Strip connection prefix if present (`customer.activities` → `activities`).
        if (str_contains($table, '.')) {
            $table = substr($table, (int) strpos($table, '.') + 1);
        }
        // Skip model-class refs.
        if (str_contains($table, '\\')) {
            return null;
        }

        // Determine the candidate match column. Honour explicit
        // `editAs(..., 'foo')` if given, else default to 'name'.
        $matchColumn = $column->getImportResolveBy() ?? 'name';

        // Cascade filter: honour column cascade config.
        // - getCascadeFrom() identifies the parent edit key used to look up
        //   the parent value in the current row.
        // - getCascadeChildColumn() controls whether and how the resolver
        //   should apply backend filtering on the child lookup table:
        //     false => frontend-only cascade; do not filter candidates.
        //     null  => use parent key name as child FK column.
        //     str   => explicit child FK column name.
        $parentKey = $column->getCascadeFrom();
        $childCascadeColumn = $column->getCascadeChildColumn();
        $parentColumn = null;
        if ($parentKey !== null && $childCascadeColumn !== false) {
            $parentColumn = is_string($childCascadeColumn) ? $childCascadeColumn : $parentKey;
        }

        // Find the import column whose editAs matches the parent key so we can
        // read the resolved parent id from the same CSV row.
        $parentColKey = null;
        if ($parentKey !== null) {
            foreach ($importColumns as $otherKey => $otherCol) {
                if ($otherCol->getEditAs() === $parentKey) {
                    $parentColKey = $otherKey;
                    break;
                }
            }
        }

        return [
            'table' => $table,
            'matchColumn' => $matchColumn,
            'parentColumn' => $parentColumn,
            'parentColKey' => $parentColKey,
        ];
    }

    /**
     * Fetch and cache `[id => name]` for a planned lookup, optionally
     * filtered by parent FK for cascading columns.
     *
     * Cache is per-resolver-instance and keyed by table+filter, so
     * loading positions for activity #1 doesn't pollute a later load
     * for activity #2.
     *
     * @param  array{table: string, matchColumn: string, parentColumn: ?string, parentColKey: ?string}  $plan
     * @return array<int, string>
     */
    private function candidatesFor(array $plan, ?string $connection, ?int $parentId): array
    {
        $cacheKey = $plan['table'].'|'.$plan['matchColumn'].'|'.($plan['parentColumn'] ?? '').'|'.($parentId ?? '');
        if (isset($this->candidateCache[$cacheKey])) {
            return $this->candidateCache[$cacheKey];
        }

        try {
            $query = DB::connection($connection)->table($plan['table']);
            if ($plan['parentColumn'] !== null && $parentId !== null) {
                $query->where($plan['parentColumn'], $parentId);
            }
            $rows = $query->select(['id', $plan['matchColumn']])->get();
        } catch (\Throwable) {
            return $this->candidateCache[$cacheKey] = [];
        }

        $out = [];
        foreach ($rows as $r) {
            /** @var \stdClass $r */
            $name = (string) ($r->{$plan['matchColumn']} ?? '');
            if ($name === '') {
                continue;
            }
            $out[(int) $r->id] = $name;
        }

        return $this->candidateCache[$cacheKey] = $out;
    }

    /** @var array<string, array<int, string>> */
    private array $candidateCache = [];

    /**
     * Score every candidate, return either:
     *   ['kind' => 'exact'|'auto', 'id' => int, 'name' => string]
     *   ['kind' => 'suggest', 'suggestions' => list<string>]
     *   ['kind' => 'none']
     *
     * @param  array<int, string>  $candidates
     * @return array{kind: 'exact'|'auto', id: int, name: string}|array{kind: 'suggest', suggestions: list<string>}|array{kind: 'none'}
     */
    private function matchOne(string $needle, array $candidates): array
    {
        $needleNorm = $this->normalise($needle);

        // Pass 1: exact (normalised). Disambiguate if multiple match.
        $exactIds = [];
        foreach ($candidates as $id => $name) {
            if ($this->normalise($name) === $needleNorm) {
                $exactIds[$id] = $name;
            }
        }
        if (count($exactIds) === 1) {
            $id = (int) array_key_first($exactIds);

            return ['kind' => 'exact', 'id' => $id, 'name' => $exactIds[$id]];
        }
        if (count($exactIds) > 1) {
            // Multiple exact matches — surface as suggestions, don't auto-pick.
            return [
                'kind' => 'suggest',
                'suggestions' => array_slice(array_values($exactIds), 0, self::MAX_SUGGESTIONS),
            ];
        }

        // Pass 2: fuzzy score every candidate.
        $scored = [];
        foreach ($candidates as $id => $name) {
            $scored[] = [
                'id' => $id,
                'name' => $name,
                'score' => $this->score($needleNorm, $this->normalise($name)),
            ];
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $scored[0] ?? null;
        if ($best === null) {
            return ['kind' => 'none'];
        }

        // Auto-accept only if the runner-up is meaningfully behind.
        // A 3pt gap with a 90+ score keeps typo correction responsive
        // while still avoiding most ambiguous picks.
        $runnerUp = $scored[1]['score'] ?? 0;
        if ($best['score'] >= self::AUTO_THRESHOLD && ($best['score'] - $runnerUp) >= 3) {
            return ['kind' => 'auto', 'id' => (int) $best['id'], 'name' => $best['name']];
        }

        if ($best['score'] >= self::SUGGEST_THRESHOLD) {
            $suggestions = [];
            foreach (array_slice($scored, 0, self::MAX_SUGGESTIONS) as $s) {
                if ($s['score'] >= self::SUGGEST_THRESHOLD) {
                    $suggestions[] = (string) $s['name'];
                }
            }

            return ['kind' => 'suggest', 'suggestions' => $suggestions];
        }

        return ['kind' => 'none'];
    }

    /** Lower-case + ASCII-fold + collapse whitespace for forgiving comparisons. */
    private function normalise(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = mb_strtolower($ascii);

        return trim((string) preg_replace('/\s+/', ' ', $lower));
    }

    /**
     * Combine similar_text() % and inverse-Levenshtein into a 0-100 score.
     * Weighted 60/40: similar_text rewards substring/word-order matches,
     * Levenshtein rewards single-character typos.
     */
    private function score(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        $pct = 0.0;
        similar_text($a, $b, $pct);

        $maxLen = max(strlen($a), strlen($b));
        // levenshtein() refuses strings > 255 chars; clip safely.
        $a255 = substr($a, 0, 255);
        $b255 = substr($b, 0, 255);
        $lev = levenshtein($a255, $b255);
        /** @phpstan-ignore-next-line */
        $levScore = $maxLen > 0 ? (1 - min($lev, $maxLen) / $maxLen) * 100 : 0.0;

        return (0.6 * $pct) + (0.4 * $levScore);
    }
}
