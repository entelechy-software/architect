<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import;

use Entelechy\Architect\Table\Column;
use LogicException;

/**
 * Per-table CSV import configuration.
 *
 * Created indirectly via TableBuilder::importable(...) and frozen in
 * the parent ArchitectTableDefinition. Owns every concern that varies
 * between tables: which columns may be imported, the permission
 * required to invoke the wizard, the per-batch row cap, file size
 * cap, duplicate-detection strategy, rate limits and reversal
 * window.
 *
 * Defaults are deliberately conservative — adding ->importable()
 * with only the required arguments produces an import that is safe
 * but possibly stricter than the table requires; per-table overrides
 * relax the limits where appropriate.
 */
final readonly class ImportDefinition
{
    /**
     * @param  list<string>  $columns
     *                                 Ordered list of write-column keys (matching TableBuilder
     *                                 column keys) that may be imported. The order here is the
     *                                 order presented to the user in the template, the preview
     *                                 table and the reversal audit.
     * @param  string  $permission
     *                              Permission node required to open the wizard. Typically the
     *                              same as the table's create permission (e.g. `activity_committees.create`).
     * @param  int  $maxRecordsPerBatch
     *                                   Hard cap on rows the wizard will accept in a single
     *                                   upload. Rows beyond this number are rejected at upload time.
     * @param  int  $maxFileSizeKb
     *                              Hard cap on uploaded file size in kilobytes. Defends
     *                              against accidental binary uploads and DoS-by-large-file.
     * @param  bool  $allowPartialImport
     *                                    When false (default), the Commit button stays disabled
     *                                    until every row passes validation. When true, the user
     *                                    may commit a batch that contains failed rows; failed rows
     *                                    are recorded against the batch with status=failed.
     * @param  list<string>  $duplicateCheckColumns
     *                                               Subset of $columns whose combined values uniquely identify
     *                                               an existing tenant record. When non-empty the processor
     *                                               queries the data model for matches and flags duplicates in
     *                                               the preview; the user may then choose to skip them.
     * @param  array{attempts: int, period: string}  $rateLimitUser
     *                                                               Maximum batches per period for a single admin user.
     *                                                               `period` is parsed by Carbon::parse('-' . $period).
     * @param  array{attempts: int, period: string}  $rateLimitUnion
     *                                                                Maximum batches per period for the whole tenant
     *                                                                (regardless of which admin uploaded them).
     * @param  int  $reversalWindowMinutes
     *                                      Minutes after `created_at` during which the original
     *                                      uploader may reverse the batch. Superadmins bypass this.
     */
    public function __construct(
        public array $columns,
        public string $permission,
        public int $maxRecordsPerBatch = 500,
        public int $maxFileSizeKb = 2048,
        public bool $allowPartialImport = false,
        public array $duplicateCheckColumns = [],
        public array $rateLimitUser = ['attempts' => 3, 'period' => '24 hours'],
        public array $rateLimitUnion = ['attempts' => 10, 'period' => '24 hours'],
        public int $reversalWindowMinutes = 120,
    ) {}

    /**
     * Resolve the importable columns against the table's full column
     * set, preserving the order declared in $columns.
     *
     * Throws if any declared key is missing from the table — the
     * builder calls this at build() time so the error surfaces at
     * boot rather than when an admin first opens the wizard.
     *
     * @param  array<string, Column>  $allColumns  Keyed by column key.
     * @return array<string, Column> Keyed by column key, in declared order.
     */
    public function getImportColumns(array $allColumns): array
    {
        $resolved = [];

        foreach ($this->columns as $key) {
            if (! isset($allColumns[$key])) {
                throw new LogicException(
                    "ImportDefinition references unknown column [{$key}]. ".
                    'Add it to the table or remove it from the importable() column list.'
                );
            }

            $resolved[$key] = $allColumns[$key];
        }

        return $resolved;
    }
}
