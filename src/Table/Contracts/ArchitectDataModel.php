<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

use Entelechy\Architect\Table\QueryContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract every module's data layer must implement to plug into the
 * TableBuilder engine. The engine never touches a model directly — every
 * read and mutation goes through this interface.
 *
 * Implementations live alongside the table definition class, e.g.:
 *   app/Modules/Activities/Models/CommitteesTableModel.php
 *
 * The engine invokes:
 *   - forList() to populate the index page (filters/sort/pagination applied)
 *   - forForm() to load a single record for the create or edit form
 *   - create() / modify() / archive() / restore() for mutations
 *   - canActOn() for row-level scope checks (Layer 3 of the permission model)
 *
 * Implementations are responsible for:
 *   - Casting input shapes (e.g. Lookup {val,txt} payloads) to scalar IDs
 *   - Returning a paginator wired to the correct connection
 *   - Soft-delete semantics for archive() / restore() when the module is
 *     declared `->archivable()` on its definition
 */
interface ArchitectDataModel
{
    /**
     * Paginated, filtered, sorted list of records for the index view.
     *
     * Each row is returned as an associative array — the engine does not
     * receive Eloquent models directly, allowing the data model to flatten
     * joins and computed columns into a single shape. Field visibility
     * stripping is applied by the engine after this method returns.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function forList(QueryContext $context): LengthAwarePaginator;

    /**
     * Load a single record for the edit form.
     *
     * Returns the record's form-shape (Lookup fields as {val,txt}, dates
     * as 'd/m/Y', etc.) ready for hydration into the form panel. Returns
     * null when the record does not exist or is not visible to the caller.
     *
     * @return array<string, mixed>|null
     */
    public function forForm(int $id): ?array;

    /**
     * Create a new record from validated form input.
     *
     * @param  array<string, mixed>  $input
     * @return int The primary key of the created record.
     */
    public function create(array $input): int;

    /**
     * Update an existing record from validated form input.
     *
     * @param  array<string, mixed>  $input
     */
    public function modify(int $id, array $input): void;

    /**
     * Soft-delete (archive) a record.
     *
     * Only invoked for definitions declared `->archivable()`.
     * When the definition declares `->requiresDeletionReason()` the
     * engine collects a free-text reason from the user and passes it
     * here; otherwise `$reason` is null. Implementations decide where
     * (if anywhere) to persist the reason.
     */
    public function archive(int $id, ?string $reason = null): void;

    /**
     * Restore a previously archived record.
     */
    public function restore(int $id): void;

    /**
     * Permanently delete a record.
     *
     * When the definition declares `->deletable(reasonRequired: true)` the
     * engine collects a free-text reason from the user and passes it
     * here; otherwise `$reason` is null. Implementations decide where
     * (if anywhere) to persist the reason for audit purposes.
     */
    public function delete(int $id, ?string $reason = null): void;

    /**
     * Layer 3 (data scope) gate: may this user act on this record?
     *
     * The engine calls this before every single-record operation
     * (edit, archive, restore, custom row action). Returning false
     * yields a 403 with no data leak. Layer 1 / Layer 2 (node access)
     * are checked by the engine before this method is reached.
     */
    public function canActOn(Model $user, int $id): bool;

    /**
     * Optional: return the underlying Eloquent model class for this
     * data model. Used by the streaming exporter to build the query
     * and by the engine to resolve archive vs. delete semantics.
     *
     * @return class-string<Model>
     */
    public function modelClass(): string;
}
