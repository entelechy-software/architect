<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Import\Models\ImportBatch;
use Entelechy\Architect\Table\Import\Models\ImportBatchItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

/**
 * The pure business logic of the TableBuilder CSV import flow.
 *
 * Owns five responsibilities, each exposed as one public method so
 * the Livewire wizard can drive the workflow step-by-step:
 *
 *   parse()           — Read uploaded CSV bytes into [headers, rows].
 *   validateRows()    — Run column validation rules across every row.
 *   validateRow()     — Re-validate a single row after inline edit.
 *   detectDuplicates()— Flag rows whose key columns match existing records.
 *   commit()          — Insert valid rows via dataModel->create()
 *                       and persist the audit batch + items.
 *
 * The class is stateless — every method takes the input it needs as
 * arguments and returns plain arrays. The wizard component holds the
 * conversational state (parsed rows, validation results, etc.).
 */
final class ImportProcessor
{
    /**
     * Parse uploaded CSV into normalised header + row structures.
     *
     * Each cell is sanitised against CSV-injection on ingest. Rows
     * that contain invalid UTF-8 are recorded as such (status=invalid)
     * but the parser does not abort — the wizard surfaces them to the
     * user alongside any other validation problems.
     *
     * @param  string  $csvContents  Raw bytes from the uploaded file.
     * @param  array<string, string>  $expectedHeaders  Map of write key => human label, in declared order.
     *                                                  The label is what we expect to find in the CSV header row;
     *                                                  the key is what we use to key the resulting values arrays.
     * @return array{
     *     headers: list<string>,
     *     rows: list<array{values: array<string, string>, errors: array<string, list<string>>}>,
     *     globalErrors: list<string>,
     * }
     */
    public function parse(string $csvContents, array $expectedHeaders, int $maxRows): array
    {
        $expectedLabels = array_values($expectedHeaders);
        $expectedKeys = array_keys($expectedHeaders);
        $globalErrors = [];

        if (! CsvSanitizer::isValidUtf8($csvContents)) {
            return [
                'headers' => [],
                'rows' => [],
                'globalErrors' => ['File is not valid UTF-8. Re-save the spreadsheet as "CSV UTF-8" and try again.'],
            ];
        }

        // Strip BOM if present.
        if (str_starts_with($csvContents, "\xEF\xBB\xBF")) {
            $csvContents = substr($csvContents, 3);
        }

        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Failed to open in-memory CSV stream.');
        }
        fwrite($handle, $csvContents);
        rewind($handle);

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return [
                'headers' => [],
                'rows' => [],
                'globalErrors' => ['CSV is empty.'],
            ];
        }

        $headers = array_map(
            fn ($h) => CsvSanitizer::sanitize((string) $h),
            $headerRow,
        );

        // Verify headers match exactly (order-sensitive — keeps the
        // template authoritative and avoids ambiguity around
        // duplicate or reordered columns). We compare against the
        // human labels because that is what the user sees in the
        // template and in their spreadsheet.
        if ($headers !== $expectedLabels) {
            $globalErrors[] = sprintf(
                'CSV headers do not match the template. Expected: [%s]. Got: [%s]. '.
                'Download a fresh template from the wizard and paste your data into it.',
                implode(', ', $expectedLabels),
                implode(', ', $headers),
            );
        }

        $rows = [];
        $rowNumber = 0;
        while (($cells = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($rowNumber > $maxRows) {
                $globalErrors[] = sprintf(
                    'CSV contains more than %d rows. Split the file and import in batches.',
                    $maxRows,
                );
                break;
            }

            // Pad/truncate to header count so we always end up with
            // the right shape, even from sloppy CSVs.
            $cells = array_pad(array_slice($cells, 0, count($expectedKeys)), count($expectedKeys), '');

            $values = [];
            foreach ($expectedKeys as $idx => $key) {
                $values[$key] = CsvSanitizer::sanitize((string) ($cells[$idx] ?? ''));
            }

            $rows[] = [
                'values' => $values,
                'errors' => [],
            ];
        }

        fclose($handle);

        if ($rows === [] && $globalErrors === []) {
            $globalErrors[] = 'CSV contains a header row but no data rows.';
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'globalErrors' => $globalErrors,
        ];
    }

    /**
     * Run validation against every row in the parsed set.
     *
     * Mutates each row's `errors` key in place. A row with non-empty
     * errors is considered invalid and may not be committed (unless
     * the table opts into allowPartialImport).
     *
     * @param  list<array{values: array<string, string>, errors: array<string, list<string>>}>  $rows
     * @param  array<string, Column>  $importColumns  Keyed by column key, in declared order.
     * @param  string|null  $connection  Database connection name to use for `exists`/`unique` rules.
     *                                   Pass the data model's connection (e.g. 'customer') so the
     *                                   rules query the tenant database, not platform_crm.
     * @param  array<int, array<string, list<string>>>  $suggestions  Per-row fuzzy suggestions
     *                                                                produced by ImportResolver;
     *                                                                appended to the relevant
     *                                                                error message as "Did you mean: …".
     * @return list<array{values: array<string, string>, errors: array<string, list<string>>}>
     */
    public function validateRows(
        array $rows,
        array $importColumns,
        ?string $connection = null,
        array $suggestions = [],
    ): array {
        foreach ($rows as $idx => $row) {
            $rows[$idx]['errors'] = $this->validateRow(
                $row['values'],
                $importColumns,
                $connection,
                $suggestions[$idx] ?? [],
            );
        }

        return $rows;
    }

    /**
     * Validate a single row's values against the import columns.
     *
     * Used both during initial bulk validation and after each inline
     * edit in the wizard preview.
     *
     * @param  array<string, string>  $values
     * @param  array<string, Column>  $importColumns
     * @param  string|null  $connection  Database connection for presence rules.
     * @param  array<string, list<string>>  $suggestions  Fuzzy-match suggestions per column key
     *                                                    (top candidate display values).
     * @return array<string, list<string>> Errors keyed by column key (empty array == row is valid).
     */
    public function validateRow(
        array $values,
        array $importColumns,
        ?string $connection = null,
        array $suggestions = [],
    ): array {
        $rules = [];
        $attributes = [];

        foreach ($importColumns as $key => $column) {
            $rule = $column->getRules();
            if ($rule !== null && $rule !== '') {
                $rules[$key] = $connection !== null
                    ? $this->qualifyPresenceRules($rule, $connection)
                    : $rule;

                // Accept common CSV boolean literals by normalizing them
                // before Laravel's boolean rule is applied.
                if (str_contains('|'.strtolower($rule).'|', '|boolean|')) {
                    $values[$key] = $this->normalizeBooleanCsvValue((string) ($values[$key] ?? ''));
                }
            }
            $attributes[$key] = $column->getLabel();
        }

        if ($rules === []) {
            return [];
        }

        $validator = Validator::make($values, $rules, [], $attributes);

        if ($validator->passes()) {
            return [];
        }

        /** @var array<string, list<string>> $bag */
        $bag = $validator->errors()->toArray();

        // Sweeten the error messages for columns where the resolver
        // found close-but-not-quite matches, so the user has something
        // to copy-paste back into the cell.
        foreach ($suggestions as $colKey => $hints) {
            if ($hints === [] || ! isset($bag[$colKey])) {
                continue;
            }
            $bag[$colKey][] = 'Did you mean: '.implode(', ', $hints).'?';
        }

        return $bag;
    }

    /**
     * Normalize common CSV boolean literals to Laravel-friendly values.
     */
    private function normalizeBooleanCsvValue(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'on', 'yes', 'y' => '1',
            '0', 'false', 'off', 'no', 'n', '' => '0',
            // Checkbox-backed import fields only have true/false states.
            // Any unrecognised literal is treated as unchecked/false so
            // the preview UI and stored value stay aligned.
            default => '0',
        };
    }

    /**
     * Rewrite `exists:table,col` and `unique:table,col` rules so the
     * table is qualified with the data model's connection name
     * (e.g. `exists:customer.activities,id`).
     *
     * Laravel's Validator forcibly calls setConnection() on the
     * presence verifier with whatever connection it parsed from the
     * rule string just before each query — so injecting a
     * pre-configured verifier is overridden. The only reliable way
     * to point validation rules at a non-default connection is to
     * encode the connection in the rule itself.
     */
    private function qualifyPresenceRules(string $rule, string $connection): string
    {
        $segments = explode('|', $rule);
        foreach ($segments as $i => $segment) {
            if (! preg_match('/^(exists|unique):(.+)$/i', $segment, $m)) {
                continue;
            }
            $verb = strtolower($m[1]);
            $params = $m[2];
            // First parameter is the table reference. Skip if the
            // user already qualified it (`db.table`) or used a model
            // class (contains backslashes).
            $parts = explode(',', $params, 2);
            $table = $parts[0];
            if (str_contains($table, '.') || str_contains($table, '\\')) {
                continue;
            }
            $parts[0] = $connection.'.'.$table;
            $segments[$i] = $verb.':'.implode(',', $parts);
        }

        return implode('|', $segments);
    }

    /**
     * Mark rows that match an existing record on $duplicateCheckColumns.
     *
     * Adds a `duplicate` boolean to each row. The wizard uses this
     * to render an amber row state and offer a "Skip duplicates"
     * toggle at commit time.
     *
     * Strategy: one IN-style query per duplicate column, joined by
     * AND in PHP — keeps the query indexable on each individual
     * column rather than relying on a composite index that may not
     * exist on the target table.
     *
     * @param  list<array{values: array<string, string>, errors: array<string, list<string>>, duplicate?: bool}>  $rows
     * @param  list<string>  $duplicateCheckColumns
     * @return list<array{values: array<string, string>, errors: array<string, list<string>>, duplicate: bool}>
     */
    public function detectDuplicates(
        array $rows,
        ArchitectTableDefinition $definition,
        array $duplicateCheckColumns,
    ): array {
        if ($duplicateCheckColumns === []) {
            return array_map(
                fn (array $row): array => $row + ['duplicate' => false],
                $rows,
            );
        }

        $dataModel = app($definition->dataModelClass);
        $modelClass = $dataModel->modelClass();

        // Map display keys (as configured / present in $row['values'])
        // to the underlying DB column names via Column::getEditKey().
        // This is necessary for FK columns where the display column
        // (e.g. activity_name) does not exist on the target table —
        // the real column is the edit key (e.g. activity_id), and by
        // the time we get here the values have already been resolved
        // to ids by ImportResolver / user correction.
        $editKeyMap = [];
        foreach ($definition->columns as $column) {
            $editKeyMap[$column->getKey()] = $column->getEditKey();
        }

        // Build a map of (composite-key string) => true for fast lookup.
        // Composite key is JSON of the duplicate values, normalised.
        $existingKeys = [];

        // Collect every distinct value per check column from the rows
        // so we can fetch only the candidate records, not the entire
        // table.
        $valueLookups = [];
        $dbColumns = [];
        foreach ($duplicateCheckColumns as $col) {
            $dbCol = $editKeyMap[$col] ?? $col;
            $dbColumns[$col] = $dbCol;
            $valueLookups[$dbCol] = array_unique(array_map(
                fn (array $row): string => (string) ($row['values'][$col] ?? ''),
                $rows,
            ));
        }

        /** @var class-string<Model> $modelClass */
        $query = $modelClass::query();
        foreach ($valueLookups as $dbCol => $values) {
            $query->whereIn($dbCol, $values);
        }

        $dbColumnList = array_values($dbColumns);
        foreach ($query->get($dbColumnList) as $existing) {
            $key = $this->compositeKey($dbColumnList, $existing->toArray());
            $existingKeys[$key] = true;
        }

        return array_map(
            function (array $row) use ($duplicateCheckColumns, $dbColumns, $existingKeys): array {
                // Translate row values (keyed by display key) into
                // a temporary map keyed by db column for compositeKey.
                $rowDb = [];
                foreach ($duplicateCheckColumns as $col) {
                    $rowDb[$dbColumns[$col]] = $row['values'][$col] ?? '';
                }
                $key = $this->compositeKey(array_values($dbColumns), $rowDb);

                return $row + ['duplicate' => isset($existingKeys[$key])];
            },
            $rows,
        );
    }

    /**
     * Commit valid rows: persist the batch, persist each item, and
     * call dataModel->create() to insert the underlying record.
     *
     * Wrapped in a transaction on the platform connection so the
     * audit batch + items succeed or fail atomically. The tenant
     * inserts are NOT in the same transaction (they run on a
     * different connection); each create() call is itself
     * transactional inside the data model. Rare partial-failure
     * mode: a failed create() leaves the batch row recording the
     * failure and the tenant database untouched for that row.
     *
     * @param  list<array{values: array<string, string>, errors: array<string, list<string>>, duplicate?: bool}>  $rows
     * @param  array<string, Column>  $importColumns
     * @return ImportBatch Refreshed with imported_rows / failed_rows / status.
     */
    public function commit(
        ArchitectTableDefinition $definition,
        array $rows,
        array $importColumns,
        string $filename,
        int $userId,
        bool $skipDuplicates,
        string $definitionClass = '',
    ): ImportBatch {
        $importDef = $definition->importDefinition;
        if ($importDef === null) {
            throw new RuntimeException('Cannot commit import: definition has no importDefinition.');
        }

        // Filter rows we actually want to insert.
        $eligible = array_filter($rows, function (array $row) use ($skipDuplicates, $importDef): bool {
            if ($row['errors'] !== []) {
                // Failed validation — only included if partial allowed
                // (and even then, recorded as failed).
                return $importDef->allowPartialImport;
            }

            if ($skipDuplicates && ($row['duplicate'] ?? false)) {
                return false;
            }

            return true;
        });

        $tenantIdentifier = app(TenantResolver::class)->currentIdentifier();

        $dataModel = app($definition->dataModelClass);
        $dataModelClass = $definition->dataModelClass;

        $importConnection = config('architect.import.connection') ?: config('database.default');

        $batch = DB::connection($importConnection)->transaction(function () use (
            $tenantIdentifier,
            $userId,
            $definition,
            $definitionClass,
            $filename,
            $rows,
        ): ImportBatch {
            return ImportBatch::create([
                'tenant_identifier' => $tenantIdentifier,
                'user_id' => $userId,
                'definition_class' => $definitionClass !== '' ? $definitionClass : get_class($definition),
                'filename' => $filename,
                'total_rows' => count($rows),
                'imported_rows' => 0,
                'failed_rows' => 0,
                'status' => 'processing',
            ]);
        });

        $imported = 0;
        $failed = 0;
        $rowNumber = 0;

        foreach ($eligible as $row) {
            $rowNumber++;
            $values = $row['values'];

            // If the row has validation errors and we're in partial
            // mode, record the failure but skip the create() call.
            if ($row['errors'] !== []) {
                ImportBatchItem::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $values,
                    'tenant_record_id' => null,
                    'data_model_class' => $dataModelClass,
                    'status' => 'failed',
                    'errors' => $row['errors'],
                ]);
                $failed++;

                continue;
            }

            try {
                $newId = $dataModel->create($this->mapToEditKeys($values, $importColumns));

                ImportBatchItem::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $values,
                    'tenant_record_id' => $newId,
                    'data_model_class' => $dataModelClass,
                    'status' => 'imported',
                    'errors' => null,
                ]);
                $imported++;
            } catch (Throwable $e) {
                ImportBatchItem::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $values,
                    'tenant_record_id' => null,
                    'data_model_class' => $dataModelClass,
                    'status' => 'failed',
                    'errors' => ['create' => [$e->getMessage()]],
                ]);
                $failed++;
            }
        }

        $batch->update([
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'status' => $failed === 0 ? 'complete' : 'partial',
        ]);

        return $batch->fresh() ?? $batch;
    }

    /**
     * Build a deterministic composite-key string for duplicate detection.
     *
     * Normalises by trimming + lowercasing each component so
     * "Swimming" matches "  swimming  ".
     *
     * @param  list<string>  $columns
     * @param  array<string, mixed>  $values
     */
    private function compositeKey(array $columns, array $values): string
    {
        $parts = [];
        foreach ($columns as $col) {
            $parts[] = mb_strtolower(trim((string) ($values[$col] ?? '')));
        }

        return implode('|', $parts);
    }

    /**
     * Translate a row's values from CSV display keys to the keys the
     * data model's create() expects.
     *
     * Columns declared with `->editAs('foo_id')` carry the user-facing
     * display key (e.g. `activity_name`) in the CSV header but the data
     * model's create() takes the FK key (`activity_id`). The fuzzy
     * resolver has already swapped the cell value to the integer id;
     * this just renames the array key.
     *
     * Columns without an `editAs` keep their original key.
     *
     * @param  array<string, string>  $values
     * @param  array<string, Column>  $importColumns
     * @return array<string, string>
     */
    private function mapToEditKeys(array $values, array $importColumns): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $editKey = isset($importColumns[$key]) ? $importColumns[$key]->getEditKey() : $key;
            $out[$editKey] = $value;
        }

        return $out;
    }
}
