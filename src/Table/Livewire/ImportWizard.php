<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Livewire;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Import\ImportProcessor;
use Entelechy\Architect\Table\Import\ImportRateLimiter;
use Entelechy\Architect\Table\Import\ImportResolver;
use Entelechy\Architect\Table\Import\Models\ImportBatch;
use Entelechy\Architect\Table\TableBuilder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TableBuilder CSV Import Wizard.
 *
 * Modal Livewire component shared by every importable TableBuilder.
 * Renders nothing until the host engine dispatches
 * `architect:open-import` with a definitionClass payload.
 *
 * Wizard steps:
 *   1 — Upload (download template + choose file)
 *   2 — Preview (color-coded rows, inline editing of invalid rows,
 *       skip-duplicates toggle)
 *   3 — Confirm (final summary + commit)
 *   4 — Result (imported / failed counts + history link)
 *
 * History panel — toggled from any step — lists prior batches and
 * offers in-window reversal.
 *
 * State machine is deliberately explicit (the `$step` int) rather
 * than derived from props; this makes the back-step flow trivial
 * and ensures the modal can be reopened on the previous step
 * without losing parsed-row state.
 */
class ImportWizard extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public bool $historyOpen = false;

    /** 1=upload, 2=preview, 3=confirm, 4=result */
    public int $step = 1;

    /** FQCN of the ArchitectTableDefinition driving this import. */
    public string $definitionClass = '';

    /** Uploaded file (managed by WithFileUploads). */
    public mixed $file = null;

    /** "Skip duplicates" toggle on the preview step. */
    public bool $skipDuplicates = true;

    /**
     * Parsed and validated rows.
     *
     * Shape per element:
     *   [
     *     'values' => [colKey => string],
     *     'errors' => [colKey => [msg]],
     *     'duplicate' => bool,
     *     'ignored' => bool,
     *     'is_example' => bool,
     *     'display_values' => [colKey => string]
     *   ]
     *
     * Live-bound to inline edit inputs in the preview table; a
     * change to any cell triggers updatedParsedRows() which
     * re-validates that single row and refreshes counts.
     *
     * @var list<array{values: array<string, string>, errors: array<string, list<string>>, duplicate: bool, ignored?: bool, is_example?: bool, display_values?: array<string, string>}>
     */
    public array $parsedRows = [];

    /**
     * Per-row notes from the fuzzy resolver, e.g. "Auto-corrected …".
     * Shape: [rowIdx => [colKey => message]].
     *
     * @var array<int, array<string, string>>
     */
    public array $resolverNotes = [];

    /**
     * Per-row suggestion lists from the fuzzy resolver. Surfaced in
     * the preview-step error tooltip so the user knows what to type.
     * Shape: [rowIdx => [colKey => [name1, name2, name3]]].
     *
     * @var array<int, array<string, list<string>>>
     */
    public array $resolverSuggestions = [];

    /**
     * Global parse-time errors (header mismatch, too many rows, etc.).
     *
     * @var list<string>
     */
    public array $globalErrors = [];

    /** Counts for the preview-step status bar. */
    public int $validCount = 0;

    public int $invalidCount = 0;

    public int $duplicateCount = 0;

    /** ID of the most recently committed batch (for the Result step). */
    public ?int $lastBatchId = null;

    public int $lastImported = 0;

    public int $lastFailed = 0;

    /**
     * History batches loaded for the History panel.
     *
     * @var list<array<string, mixed>>
     */
    public array $batches = [];

    /** Banner messages for the wizard. */
    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    /**
     * Open the wizard for a particular table.
     *
     * Triggered by the engine toolbar's Import button:
     *   $dispatch('architect:open-import', { definitionClass: '...' })
     */
    #[On('architect:open-import')]
    public function openWizard(string $definitionClass): void
    {
        if (! $this->permissionGate($definitionClass)) {
            return;
        }

        $this->reset(['step', 'file', 'parsedRows', 'globalErrors', 'validCount', 'invalidCount', 'duplicateCount', 'lastBatchId', 'lastImported', 'lastFailed', 'errorMessage', 'successMessage']);
        $this->step = 1;
        $this->definitionClass = $definitionClass;
        $this->open = true;
        $this->historyOpen = false;
    }

    public function closeWizard(): void
    {
        $this->open = false;
        $this->historyOpen = false;
        // Refresh the underlying table so newly-imported rows appear.
        $this->dispatch('$refresh')->to('architect-engine');
    }

    public function showHistory(): void
    {
        $this->loadHistory();
        $this->historyOpen = true;
    }

    public function hideHistory(): void
    {
        $this->historyOpen = false;
    }

    /**
     * Stream the CSV template back to the browser.
     *
     * Header row = importable column labels. Example row = each
     * column's placeholder (or empty string when none set). The
     * download is a Symfony StreamedResponse so very wide
     * templates don't bloat memory.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $def = $this->definition();
        $importDef = $def->importDefinition;

        if ($importDef === null) {
            abort(404);
        }

        /** @var array<string, Column> $columns */
        $columns = $importDef->getImportColumns($this->columnsKeyed($def));

        $filename = sprintf('import-template-%s.csv', class_basename($def->dataModelClass));

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                throw new \RuntimeException('Failed to open php://output');
            }

            // Headers — use the human label so users recognise the
            // columns in their spreadsheet. The wizard's parser
            // matches headers against labels and re-keys cells by
            // column key internally.
            fputcsv($out, array_map(fn (Column $c): string => $c->getLabel(), $columns));

            // One example row built from each column's placeholder.
            $example = array_map(fn (Column $c): string => $c->getPlaceholder(), $columns);
            if (array_filter($example) !== []) {
                fputcsv($out, $example);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * User has chosen a file and clicked Upload.
     *
     * Runs rate-limit + size checks, then parses + validates +
     * detects duplicates and advances to step 2.
     */
    public function processUpload(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $def = $this->definition();
        $importDef = $def->importDefinition;
        if ($importDef === null) {
            $this->errorMessage = 'This table does not support import.';

            return;
        }

        if ($this->file === null) {
            $this->errorMessage = 'Please select a CSV file to upload.';

            return;
        }

        // File size cap (kilobytes).
        $sizeKb = (int) ceil($this->file->getSize() / 1024);
        if ($sizeKb > $importDef->maxFileSizeKb) {
            $this->errorMessage = sprintf(
                'File is %d KB; the maximum allowed is %d KB.',
                $sizeKb,
                $importDef->maxFileSizeKb,
            );

            return;
        }

        // Rate limits.
        $user = $this->currentUser();
        if ($user === null) {
            $this->errorMessage = 'You must be signed in to import.';

            return;
        }

        $userId = (int) $user->getAttribute('id');
        if ($userId <= 0) {
            $this->errorMessage = 'Could not resolve your user id. Please reload the page and try again.';

            return;
        }

        $rateLimiter = app(ImportRateLimiter::class);
        $rateError = $rateLimiter->check($userId, $this->definitionClass, $importDef);
        if ($rateError !== null) {
            $this->errorMessage = $rateError;

            return;
        }

        // Parse + validate + duplicate-detect.
        $processor = app(ImportProcessor::class);
        $columnsKeyed = $this->columnsKeyed($def);
        $importColumns = $importDef->getImportColumns($columnsKeyed);

        $contents = file_get_contents($this->file->getRealPath());
        if ($contents === false) {
            $this->errorMessage = 'Could not read uploaded file.';

            return;
        }

        // Build the key => label map the parser uses to match the
        // CSV header row and re-key cells by column key.
        $headerMap = [];
        foreach ($importColumns as $key => $col) {
            $headerMap[$key] = $col->getLabel();
        }

        $parsed = $processor->parse($contents, $headerMap, $importDef->maxRecordsPerBatch);
        $this->globalErrors = $parsed['globalErrors'];

        if ($this->globalErrors !== [] && $parsed['rows'] === []) {
            // Stay on step 1 to surface the parse error.
            return;
        }

        $exampleRowIndexes = [];
        foreach ($parsed['rows'] as $rowIndex => $rowData) {
            if ($this->looksLikeExampleRow($rowData['values'], $importColumns)) {
                $exampleRowIndexes[$rowIndex] = true;
            }
        }

        $connection = $this->dataModelConnection($def);

        // Fuzzy-resolve user-typed values into FK ids before validation.
        // The resolver swaps high-confidence cells in-place and surfaces
        // suggestions for ambiguous ones, which validateRows then folds
        // into the per-cell error messages.
        $resolver = app(ImportResolver::class);
        $resolved = $resolver->resolve($parsed['rows'], $importColumns, $connection);

        $this->resolverNotes = $resolved['notes'];
        $this->resolverSuggestions = $resolved['suggestions'];

        $rows = $processor->validateRows(
            $resolved['rows'],
            $importColumns,
            $connection,
            $resolved['suggestions'],
        );
        $rows = $processor->detectDuplicates($rows, $def, $importDef->duplicateCheckColumns);

        foreach ($rows as $rowIndex => $rowData) {
            $isExample = isset($exampleRowIndexes[$rowIndex]);
            $rows[$rowIndex]['is_example'] = $isExample;
            // Example rows start unselected and are intentionally non-importable.
            $rows[$rowIndex]['ignored'] = $isExample;
        }

        $this->parsedRows = $rows;
        $this->recomputeCounts();
        $this->step = 2;
    }

    /**
     * Re-validate a single row whenever any of its values changes
     * via the inline edit inputs. Triggered by Livewire's wire:model
     * mutation hook.
     */
    public function updatedParsedRows(mixed $value, string $key): void
    {
        // $key looks like "0.values.member_name" — first segment is row index.
        $segments = explode('.', $key);
        if (! ctype_digit($segments[0])) {
            return;
        }

        $idx = (int) $segments[0];
        if (! isset($this->parsedRows[$idx])) {
            return;
        }

        // Example rows are display-only in preview.
        if ((bool) ($this->parsedRows[$idx]['is_example'] ?? false)) {
            return;
        }

        // Ignored rows are intentionally excluded from validation gates.
        if ((bool) ($this->parsedRows[$idx]['ignored'] ?? false)) {
            return;
        }

        $def = $this->definition();
        $importDef = $def->importDefinition;
        if ($importDef === null) {
            return;
        }

        $processor = app(ImportProcessor::class);
        $importColumns = $importDef->getImportColumns($this->columnsKeyed($def));
        $connection = $this->dataModelConnection($def);

        // Re-run the resolver on the edited row so a corrected typo
        // ("Swimmin" → "Swimming") is auto-resolved on the fly.
        $resolver = app(ImportResolver::class);
        $row = $this->parsedRows[$idx];
        $resolved = $resolver->resolveRow(
            $row['values'],
            $importColumns,
            $connection,
        );
        $row['values'] = $resolved['values'];
        $row['display_values'] = array_merge(
            $row['display_values'] ?? [],
            $resolved['display_values'],
        );
        $this->resolverNotes[$idx] = $resolved['notes'];
        $this->resolverSuggestions[$idx] = $resolved['suggestions'];

        $row['errors'] = $processor->validateRow(
            $row['values'],
            $importColumns,
            $connection,
            $resolved['suggestions'],
        );
        $this->parsedRows[$idx] = $row;

        $this->parsedRows[$idx]['ignored'] = (bool) ($this->parsedRows[$idx]['ignored'] ?? false);

        $this->recomputeCounts();
    }

    public function toggleIgnoreRow(int $rowIndex): void
    {
        if (! isset($this->parsedRows[$rowIndex])) {
            return;
        }

        if ((bool) ($this->parsedRows[$rowIndex]['is_example'] ?? false)) {
            $this->parsedRows[$rowIndex]['ignored'] = true;

            return;
        }

        $current = (bool) ($this->parsedRows[$rowIndex]['ignored'] ?? false);
        $this->parsedRows[$rowIndex]['ignored'] = ! $current;
        $this->recomputeCounts();
    }

    public function updateBooleanCell(int $rowIndex, string $columnKey, bool $checked): void
    {
        if (! isset($this->parsedRows[$rowIndex])) {
            return;
        }

        if ((bool) ($this->parsedRows[$rowIndex]['is_example'] ?? false)) {
            return;
        }

        $this->parsedRows[$rowIndex]['values'][$columnKey] = $checked ? '1' : '0';

        if ((bool) ($this->parsedRows[$rowIndex]['ignored'] ?? false)) {
            return;
        }

        $def = $this->definition();
        $importDef = $def->importDefinition;
        if ($importDef === null) {
            return;
        }

        $processor = app(ImportProcessor::class);
        $importColumns = $importDef->getImportColumns($this->columnsKeyed($def));
        $connection = $this->dataModelConnection($def);

        $this->parsedRows[$rowIndex]['errors'] = $processor->validateRow(
            $this->parsedRows[$rowIndex]['values'],
            $importColumns,
            $connection,
            $this->resolverSuggestions[$rowIndex] ?? [],
        );

        $this->recomputeCounts();
    }

    public function updateLookupCell(int $rowIndex, string $columnKey, string $value): void
    {
        if (! isset($this->parsedRows[$rowIndex])) {
            return;
        }

        if ((bool) ($this->parsedRows[$rowIndex]['is_example'] ?? false)) {
            return;
        }

        $row = $this->parsedRows[$rowIndex];
        $row['values'][$columnKey] = $value;
        unset($row['display_values'][$columnKey]);

        if ((bool) ($row['ignored'] ?? false)) {
            $this->parsedRows[$rowIndex] = $row;

            return;
        }

        $def = $this->definition();
        $importDef = $def->importDefinition;
        if ($importDef === null) {
            $this->parsedRows[$rowIndex] = $row;

            return;
        }

        $processor = app(ImportProcessor::class);
        $importColumns = $importDef->getImportColumns($this->columnsKeyed($def));
        $connection = $this->dataModelConnection($def);

        $resolver = app(ImportResolver::class);
        $resolved = $resolver->resolveRow(
            $row['values'],
            $importColumns,
            $connection,
        );

        $row['values'] = $resolved['values'];
        $row['display_values'] = array_merge(
            $row['display_values'],
            $resolved['display_values'],
        );
        $this->resolverNotes[$rowIndex] = $resolved['notes'];
        $this->resolverSuggestions[$rowIndex] = $resolved['suggestions'];

        $row['errors'] = $processor->validateRow(
            $row['values'],
            $importColumns,
            $connection,
            $resolved['suggestions'],
        );
        $this->parsedRows[$rowIndex] = $row;

        $this->recomputeCounts();
    }

    public function setAllRowSelections(bool $selected): void
    {
        foreach ($this->parsedRows as $idx => $row) {
            if ((bool) ($row['is_example'] ?? false)) {
                $this->parsedRows[$idx]['ignored'] = true;

                continue;
            }

            $this->parsedRows[$idx]['ignored'] = ! $selected;
        }

        $this->recomputeCounts();
    }

    /**
     * Move from preview to confirm.
     */
    public function goToConfirm(): void
    {
        if (! $this->canCommit()) {
            $this->errorMessage = 'Fix the highlighted rows before continuing.';

            return;
        }

        $this->errorMessage = null;
        $this->step = 3;
    }

    public function backToPreview(): void
    {
        $this->step = 2;
    }

    /**
     * Persist the import.
     *
     * Re-checks rate limit + canCommit at commit time so a slow user
     * can't bypass the gate by leaving the modal open.
     */
    public function commitImport(): void
    {
        $def = $this->definition();
        $importDef = $def->importDefinition;
        if ($importDef === null) {
            return;
        }

        $user = $this->currentUser();
        if ($user === null) {
            $this->errorMessage = 'You must be signed in to import.';

            return;
        }

        if (! $this->canCommit()) {
            $this->errorMessage = 'Fix the highlighted rows before committing.';

            return;
        }

        $userId = (int) $user->getAttribute('id');
        if ($userId <= 0) {
            $this->errorMessage = 'Could not resolve your user id. Please reload the page and try again.';

            return;
        }

        $rateLimiter = app(ImportRateLimiter::class);
        $rateError = $rateLimiter->check($userId, $this->definitionClass, $importDef);
        if ($rateError !== null) {
            $this->errorMessage = $rateError;

            return;
        }

        $processor = app(ImportProcessor::class);
        $importColumns = $importDef->getImportColumns($this->columnsKeyed($def));

        $rowsToCommit = array_values(array_filter(
            $this->parsedRows,
            static fn (array $row): bool => ! ((bool) ($row['ignored'] ?? false))
        ));

        if ($rowsToCommit === []) {
            $this->errorMessage = 'All rows are currently ignored. Unignore at least one valid row to continue.';

            return;
        }

        $batch = $processor->commit(
            definition: $def,
            rows: $rowsToCommit,
            importColumns: $importColumns,
            filename: is_object($this->file) && method_exists($this->file, 'getClientOriginalName')
                ? $this->file->getClientOriginalName()
                : 'upload.csv',
            userId: $userId,
            skipDuplicates: $this->skipDuplicates,
            definitionClass: $this->definitionClass,
        );

        $this->lastBatchId = $batch->id;
        $this->lastImported = $batch->imported_rows;
        $this->lastFailed = $batch->failed_rows;
        $this->successMessage = sprintf('Imported %d row(s).', $batch->imported_rows);
        $this->step = 4;
    }

    /**
     * Reverse a previously committed batch.
     *
     * Authorisation:
     *   - Original importer: only within reversalWindowMinutes of created_at.
     *   - Superadmin: always.
     *   - Anyone else: forbidden.
     */
    public function reverseBatch(int $batchId): void
    {
        $batch = ImportBatch::find($batchId);
        if ($batch === null) {
            $this->errorMessage = 'Batch not found.';

            return;
        }

        if (! $this->canReverse($batch)) {
            $this->errorMessage = 'You are not allowed to reverse this batch (or the reversal window has expired).';

            return;
        }

        $userId = (int) ($this->currentUser()?->getKey() ?? 0);
        $count = $batch->reverse($userId);

        $this->successMessage = sprintf('Reversed %d record(s) from batch #%d.', $count, $batch->id);
        $this->loadHistory();
    }

    /**
     * Returns true when this user may reverse this batch right now.
     *
     * Public for use from the view (history table) so the Reverse
     * button can be disabled rather than triggering the error path.
     */
    public function canReverse(ImportBatch $batch): bool
    {
        if ($batch->status === 'reversed') {
            return false;
        }

        // Superadmin override — they can reverse at any time.
        if (auth('super_admin')->check()) {
            return true;
        }

        $user = $this->currentUser();
        if ($user === null) {
            return false;
        }

        // Only the original uploader can reverse (within the window).
        if ((int) $batch->user_id !== (int) $user->getAttribute('id')) {
            return false;
        }

        $importDef = $this->definition()->importDefinition;
        if ($importDef === null) {
            return false;
        }

        $deadline = Carbon::parse($batch->created_at)->addMinutes($importDef->reversalWindowMinutes);

        return now()->lt($deadline);
    }

    /**
     * (Re)load the history list for the open table.
     *
     * Scoped to the current tenant via TenantResolver.
     * No global scope is applied — the package model has no TenantScope.
     */
    private function loadHistory(): void
    {
        $tenantIdentifier = app(TenantResolver::class)->currentIdentifier();

        $query = ImportBatch::query()
            ->where('definition_class', $this->definitionClass)
            ->orderByDesc('created_at')
            ->limit(50);

        if ($tenantIdentifier !== '') {
            $query->where('tenant_identifier', $tenantIdentifier);
        }

        $this->batches = array_values($query->get()
            ->map(function (ImportBatch $b): array {
                return [
                    'id' => $b->id,
                    'filename' => $b->filename,
                    'user_id' => $b->user_id,
                    'total_rows' => $b->total_rows,
                    'imported_rows' => $b->imported_rows,
                    'failed_rows' => $b->failed_rows,
                    'status' => $b->status,
                    'created_at' => $b->created_at->format('d/m/Y H:i'),
                    'reversed_at' => $b->reversed_at?->format('d/m/Y H:i'),
                    'can_reverse' => $this->canReverse($b),
                ];
            })
            ->all());
    }

    /**
     * Commit gate. Non-reactive in the strict sense — the view reads
     * this each render so adding a #[Computed] is overkill.
     */
    #[Computed]
    public function canCommit(): bool
    {
        $importDef = $this->definition()->importDefinition;
        if ($importDef === null) {
            return false;
        }

        if ($this->parsedRows === []) {
            return false;
        }

        if ($this->selectedCount() === 0) {
            return false;
        }

        if ($importDef->allowPartialImport) {
            // Partial mode only requires that *some* row is valid.
            return $this->validCount > 0;
        }

        return $this->invalidCount === 0;
    }

    #[Computed]
    public function selectedCount(): int
    {
        $count = 0;

        foreach ($this->parsedRows as $row) {
            if (! ((bool) ($row['ignored'] ?? false))) {
                $count++;
            }
        }

        return $count;
    }

    #[Computed]
    public function allSelectableRowsSelected(): bool
    {
        $selectable = 0;
        $selected = 0;

        foreach ($this->parsedRows as $row) {
            if ((bool) ($row['is_example'] ?? false)) {
                continue;
            }

            $selectable++;
            if (! ((bool) ($row['ignored'] ?? false))) {
                $selected++;
            }
        }

        return $selectable > 0 && $selected === $selectable;
    }

    #[Computed]
    public function hasSelectableRows(): bool
    {
        foreach ($this->parsedRows as $row) {
            if (! ((bool) ($row['is_example'] ?? false))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the current ArchitectTableDefinition.
     *
     * Definition classes follow the convention of exposing a static
     * ::definition() factory; we call that and ->build() if needed
     * to mirror the engine's behaviour.
     */
    private function definition(): ArchitectTableDefinition
    {
        $class = $this->definitionClass;

        if (! class_exists($class) || ! method_exists($class, 'definition')) {
            throw new \LogicException(
                "ImportWizard: '{$class}' must expose a static ::definition() method"
            );
        }

        $def = $class::definition();

        if ($def instanceof TableBuilder) {
            $def = $def->build();
        }

        /** @var ArchitectTableDefinition $def */
        return $def;
    }

    /**
     * @return array<string, Column>
     */
    private function columnsKeyed(ArchitectTableDefinition $def): array
    {
        $out = [];
        foreach ($def->columns as $col) {
            $out[$col->getKey()] = $col;
        }

        return $out;
    }

    private function currentUser(): ?Authenticatable
    {
        return auth()->user();
    }

    /**
     * Resolve the database connection name used by this table's
     * data model. Used to point validation `exists` / `unique`
     * rules at the tenant database (typically `customer`) instead
     * of the platform default.
     */
    private function dataModelConnection(ArchitectTableDefinition $def): ?string
    {
        try {
            $dataModel = app($def->dataModelClass);
            if ($dataModel instanceof ArchitectDataModel) {
                $modelClass = $dataModel->modelClass();
                $instance = new $modelClass;

                return $instance->getConnectionName();
            }
        } catch (\Throwable) {
            // fall through
        }

        return null;
    }

    /**
     * Layer-2 permission gate at wizard open time. Returns false
     * (and silently refuses to open) when the user lacks the import
     * permission — defence-in-depth alongside the toolbar check.
     */
    private function permissionGate(string $definitionClass): bool
    {
        try {
            if (! class_exists($definitionClass) || ! method_exists($definitionClass, 'definition')) {
                return false;
            }
            $def = $definitionClass::definition();
            if ($def instanceof TableBuilder) {
                $def = $def->build();
            }
            $importDef = $def->importDefinition;
            if ($importDef === null) {
                return false;
            }

            return app(PermissionResolver::class)->can($this->currentUser(), $importDef->permission);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Refresh the three counters from $parsedRows.
     */
    private function recomputeCounts(): void
    {
        $valid = 0;
        $invalid = 0;
        $dupes = 0;

        foreach ($this->parsedRows as $row) {
            if ((bool) ($row['ignored'] ?? false)) {
                continue;
            }

            if ($row['errors'] !== []) {
                $invalid++;
            } else {
                $valid++;
            }
            if ($row['duplicate']) {
                $dupes++;
            }
        }

        $this->validCount = $valid;
        $this->invalidCount = $invalid;
        $this->duplicateCount = $dupes;
    }

    /**
     * Heuristic example-row detector used to keep placeholder rows
     * out of imports when users leave them in the CSV.
     *
     * Flags rows where all non-empty placeholders match exactly.
     * Requires at least one non-empty placeholder to avoid false
     * positives on normal blank-ish rows.
     *
     * @param  array<string, string>  $values
     * @param  array<string, Column>  $importColumns
     */
    private function looksLikeExampleRow(array $values, array $importColumns): bool
    {
        $sawPlaceholder = false;

        foreach ($importColumns as $key => $column) {
            $placeholder = trim((string) $column->getPlaceholder());
            if ($placeholder === '') {
                continue;
            }

            $sawPlaceholder = true;
            if (trim((string) ($values[$key] ?? '')) !== $placeholder) {
                return false;
            }
        }

        return $sawPlaceholder;
    }

    public function render(): View
    {
        $importColumns = [];
        if ($this->open && $this->definitionClass !== '') {
            try {
                $def = $this->definition();
                if ($def->importDefinition !== null) {
                    $importColumns = $def->importDefinition->getImportColumns($this->columnsKeyed($def));
                }
            } catch (\Throwable) {
                // swallow — view will render empty when definition can't resolve
            }
        }

        return view('architect::table.import-wizard', [
            'importColumns' => $importColumns,
        ]);
    }
}
