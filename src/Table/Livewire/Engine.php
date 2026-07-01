<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Livewire;

use Entelechy\Architect\Supersearch\Contracts\HasSupersearchHook;
use Entelechy\Architect\Table\Actions\BulkStatusAction;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\HasViewAll;
use Entelechy\Architect\Table\Export\CsvStreamExporter;
use Entelechy\Architect\Table\Export\ExcelExporter;
use Entelechy\Architect\Table\Export\ExportRowIterator;
use Entelechy\Architect\Table\Export\HtmlExporter;
use Entelechy\Architect\Table\Permissions\FieldVisibilityFilter;
use Entelechy\Architect\Table\Permissions\PermissionGate;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TableBuilder Livewire engine — index ("list") view.
 *
 * Mounts a ArchitectTableDefinition (passed by FQCN of the
 * ::definition() static class) and renders the searchable, sortable,
 * paginated index table the user sees first when navigating into a
 * module screen.
 *
 * Phase 5.1 commit 6 scope: list rendering, search, filter, sort,
 * paginate, archived-row toggle. Create / modify / archive actions
 * land in commits 7 (slide-over form panel) and 8 (page-mode form).
 *
 * Permission flow on every render():
 *   1. PermissionGate::assertCanRead() — Layer 2 (action gate).
 *   2. FieldVisibilityFilter::visibleColumns() — Layer 4 (column visibility).
 *   3. FieldVisibilityFilter::stripRow()      — Layer 4 (row-level keys).
 * Layer 3 (per-record canActOn) is enforced on row actions, not on read.
 */
#[Layout('layouts.app')]
class Engine extends Component
{
    /**
     * FQCN of the class whose ::definition() returns the
     * ArchitectTableDefinition this engine drives. Stored as a string so
     * Livewire can rehydrate it across requests.
     */
    public string $definitionClass = '';

    /**
     * URL-derived parent IDs (e.g. ['activity_id' => 42] when this
     * engine is mounted under /activities/{activity}/committees).
     *
     * Marked #[Reactive] so a Livewire parent component can pass a
     * dynamic scope array; when the parent updates the prop the child
     * Engine re-queries automatically without explicit events.
     *
     * @var array<string, int|string>
     */
    #[Reactive]
    public array $scope = [];

    /**
     * When true, the Engine is being rendered inside another view
     * rather than as its own full-page route. Embedded mode disables
     * URL-state synchronisation (since multiple tables on one page
     * would stomp each other's query params) and starts every prop at
     * its declared default regardless of the current URL.
     *
     * The same definition can never be embedded twice on a page —
     * Alpine duplicate-detection in the blade renders an error banner
     * if attempted.
     */
    public bool $embedded = false;

    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @var array<string, mixed>
     */
    #[Url(as: 'f')]
    public array $filters = [];

    #[Url(as: 'sort')]
    public ?string $sortColumn = null;

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    #[Url(as: 'page')]
    public int $page = 1;

    public int $perPage = 25;

    #[Url(as: 'archived')]
    public bool $includeArchived = false;

    /**
     * Archive flow state.
     *
     * pendingArchiveId is set by confirmArchive($id) when a definition
     * declares ->requiresDeletionReason(); the view binds a modal to
     * this property and submits archive($id, $reason). For definitions
     * without the reason flag, archive($id) is dispatched directly via
     * a wire:confirm button.
     */
    public ?int $pendingArchiveId = null;

    public string $archiveReason = '';

    public ?string $archiveError = null;

    /** Phrase the user has typed into the archive confirmation input. */
    public string $archivePhraseInput = '';

    /** The exact phrase the user must type to confirm archiving (null = phrase check disabled). */
    public ?string $pendingArchiveRequiredPhrase = null;

    /**
     * Delete flow state.
     *
     * Similar to archive flow but for permanent deletion.
     * pendingDeleteId is set by confirmDelete($id) when a definition
     * declares ->deletable(reasonRequired: true).
     */
    public ?int $pendingDeleteId = null;

    public string $deleteReason = '';

    public ?string $deleteError = null;

    /** Phrase the user has typed into the delete confirmation input. */
    public string $deletePhraseInput = '';

    /** The exact phrase the user must type to confirm deletion (null = phrase check disabled). */
    public ?string $pendingDeleteRequiredPhrase = null;

    /**
     * Bulk delete flow state.
     *
     * When an action declares requiresReason() = true, clicking the button
     * opens a reason-entry modal instead of acting immediately.
     * The modal binds to bulkActionReason and submits via submitPendingBulkAction().
     */
    public ?string $pendingBulkActionKey = null;

    public string $bulkActionReason = '';

    public ?string $bulkActionError = null;

    /** Phrase the user has typed into the bulk-action phrase input. */
    public string $bulkPhraseInput = '';

    /** The exact phrase required for the pending bulk action (null = phrase check disabled). */
    public ?string $pendingBulkRequiredPhrase = null;

    /**
     * Selected row ids for bulk actions. Populated only when the
     * definition opts in via ->selectableRows(). Stored as an
     * array of int ids so JSON round-trips through Livewire are stable.
     *
     * @var array<int, int>
     */
    public array $selected = [];

    /** Bound to the page-level "select all visible" checkbox. */
    public bool $selectAllOnPage = false;

    /** Optional success/error banner from the most recent bulk action. */
    public ?string $bulkMessage = null;

    public ?string $bulkError = null;

    /**
     * Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6.
     */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    /**
     * Per-request memoisation slots for immutable lookups. Private so
     * Livewire does not attempt to serialise them between round-trips
     * (each new request rehydrates the component and these are repopulated
     * lazily on first access).
     */
    private ?ArchitectTableDefinition $cachedDefinition = null;

    private ?ArchitectDataModel $cachedDataModel = null;

    private ?Authenticatable $cachedUser = null;

    /** Sentinel so a legitimately-null user (guest) is not re-resolved every call. */
    private bool $cachedUserResolved = false;

    /**
     * @param  array<string, int|string>  $scope
     */
    public function mount(string $definitionClass, array $scope = [], bool $embedded = false): void
    {
        $this->definitionClass = $definitionClass;
        $this->scope = $scope;
        $this->embedded = $embedded;

        $def = $this->definition();

        // Embedded mode: discard any URL-bound state hydrated by Livewire
        // so embedded tables always start clean. Two tables on one page
        // would otherwise stomp each other's ?q=, ?f[]=, ?sort=, ?page=.
        if ($this->embedded) {
            $this->search = '';
            $this->filters = [];
            $this->sortColumn = null;
            $this->sortDirection = 'asc';
            $this->page = 1;
            $this->includeArchived = false;
        }

        $this->filters = array_merge($def->defaultFilters, $this->filters);

        // Seed the per-page value from the definition's default.
        // URL-hydrated value wins if present (Livewire restores it before mount).
        if ($this->perPage === 25) {
            // 25 is the Livewire default; only override it when the definition
            // specifies a different default so URL-persisted values are respected.
            $this->perPage = $def->defaultPerPage;
        }

        // Non-paginated (static) tables load all records in a single pass.
        // Only use for small datasets — no LIMIT cap is applied to the query.
        if (! $def->isPaginated) {
            $this->perPage = PHP_INT_MAX;
        }

        // Layer 2: must hold the table's read node to even land here.
        app(PermissionGate::class)->assertCanRead($this->currentUser(), $def);

        // Apply default sort if no sort specified in URL
        if ($this->sortColumn === null) {
            $this->applyDefaultSort($def);
        }

        // Notify SupersearchEngine of this table's contextual hook (if any).
        if (is_a($this->definitionClass, HasSupersearchHook::class, true)) {
            $this->dispatch('architect:supersearch:hook-mounted', [
                'componentId' => $this->getId(),
                'definitionClass' => $this->definitionClass,
            ]);
        }
    }

    /**
     * Apply default sorting from column definitions when no user sort is active.
     * Respects priority: lower numbers = higher priority (sorted first).
     */
    private function applyDefaultSort(ArchitectTableDefinition $def): void
    {
        $columnsWithDefaults = array_filter(
            $def->columns,
            fn ($col) => $col->hasDefaultSort()
        );

        if (empty($columnsWithDefaults)) {
            return;
        }

        // Sort by priority (null priority goes last)
        usort($columnsWithDefaults, function ($a, $b) {
            $aPriority = $a->getDefaultSortPriority() ?? PHP_INT_MAX;
            $bPriority = $b->getDefaultSortPriority() ?? PHP_INT_MAX;

            return $aPriority <=> $bPriority;
        });

        // Apply the highest priority (first) default sort
        // Note: Multi-column sorting would require QueryContext changes
        $primarySort = $columnsWithDefaults[0];
        $this->sortColumn = $primarySort->getKey();
        $this->sortDirection = $primarySort->getDefaultSortDirection() ?? 'asc';
    }

    public function toggleSort(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->page = 1;
    }

    public function setFilter(string $name, mixed $value): void
    {
        if ($value === null || $value === '') {
            unset($this->filters[$name]);
        } else {
            $this->filters[$name] = $value;
        }
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->search = '';
        $this->page = 1;

        // Notify the filter offcanvas to reset its inputs imperatively.
        // The offcanvas lives inside wire:ignore + x-teleport so Alpine
        // x-effect reactivity on $wire.filters is not reliable there;
        // an explicit event is the safe alternative.
        $this->dispatch('architect:filters-cleared', instanceKey: md5($this->definitionClass));
    }

    public function toggleArchived(): void
    {
        $this->includeArchived = ! $this->includeArchived;
        $this->page = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Livewire pagination compatibility shim for Filament's pagination blade.
     *
     * The TableBuilder engine stores page state in its own public $page property
     * instead of using Livewire's WithPagination trait. Filament's pagination
     * component emits the standard Livewire pagination actions, so expose the
     * same method names and map them onto the existing state model.
     */
    public function previousPage(string $pageName = 'page'): void
    {
        $this->setPage($this->page - 1, $pageName);
    }

    /**
     * Livewire pagination compatibility shim for Filament's pagination blade.
     */
    public function nextPage(string $pageName = 'page'): void
    {
        $this->setPage($this->page + 1, $pageName);
    }

    /**
     * Livewire pagination compatibility shim for Filament's pagination blade.
     */
    public function resetPage(string $pageName = 'page'): void
    {
        $this->setPage(1, $pageName);
    }

    /**
     * Livewire pagination compatibility shim for Filament's pagination blade.
     *
     * TableBuilder only supports the default page channel. Ignore alternate page
     * names so embedded third-party pagination controls cannot mutate unrelated
     * state on the component.
     */
    public function setPage(int|string $page, string $pageName = 'page'): void
    {
        if ($pageName !== 'page') {
            return;
        }

        $pageNumber = is_numeric($page) ? (int) $page : 1;

        $this->gotoPage($pageNumber);
    }

    /**
     * Change the number of records shown per page.
     *
     * The value is validated against the definition's allowlist; unknown
     * values are silently ignored so a crafted request cannot cause a
     * runaway query.  Resets to page 1 so the user always sees the start
     * of the new page size.
     */
    public function setPerPage(int $perPage): void
    {
        $def = $this->definition();

        // Allow any value when the selector is hidden (no options defined).
        if ($def->perPageOptions !== [] && ! in_array($perPage, $def->perPageOptions, true)) {
            return;
        }

        $this->perPage = $perPage;
        $this->page = 1;
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    /**
     * Reset to page 1 when the per-page value changes via Filament's
     * wire:model.live binding on the pagination component.
     */
    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    /**
     * Open the archive confirmation modal. Always opens a modal so the user
     * can confirm the action — even when no reason or phrase is required.
     * The phraseHint is the record name/title supplied by the blade for
     * definitions that use confirmationPhrase: null (record-specific phrase).
     */
    public function confirmArchive(int $id, string $phraseHint = ''): void
    {
        $def = $this->definition();

        if (! $def->archivable) {
            throw new \LogicException('TableBuilder engine: archive requested on a non-archivable definition');
        }

        $this->pendingArchiveId = $id;
        $this->archiveReason = '';
        $this->archiveError = null;
        $this->archivePhraseInput = '';
        $this->pendingArchiveRequiredPhrase = $def->archivablePhraseRequired
            ? ($def->archivablePhrase ?? ($phraseHint !== '' ? $phraseHint : 'this record'))
            : null;
    }

    public function cancelArchive(): void
    {
        $this->pendingArchiveId = null;
        $this->archiveReason = '';
        $this->archiveError = null;
        $this->archivePhraseInput = '';
        $this->pendingArchiveRequiredPhrase = null;
    }

    /**
     * Modal-driven archive submit. Validates phrase (if required) and
     * reason (if required) before delegating to archive().
     */
    public function submitArchive(): void
    {
        if ($this->pendingArchiveId === null) {
            return;
        }

        $def = $this->definition();

        if ($this->pendingArchiveRequiredPhrase !== null) {
            if ($this->archivePhraseInput !== $this->pendingArchiveRequiredPhrase) {
                $this->archiveError = 'Please type the exact phrase to confirm.';

                return;
            }
        }

        if ($def->requiresDeletionReason && trim($this->archiveReason) === '') {
            $this->archiveError = 'A reason is required to archive this record.';

            return;
        }

        $this->archive($this->pendingArchiveId, $this->archiveReason);
    }

    /**
     * Archive (soft-delete) a single record.
     *
     * Layer 2 (remove node) and Layer 3 (canActOn) gates are enforced
     * via PermissionGate::assertCanActOnRecord. When the definition
     * declares ->requiresDeletionReason() a non-empty reason is
     * mandatory; the engine validates here so the data model can rely
     * on its presence.
     */
    public function archive(int $id, ?string $reason = null): void
    {
        $def = $this->definition();

        if (! $def->archivable) {
            throw new \LogicException('TableBuilder engine: archive requested on a non-archivable definition');
        }

        $reason = $reason !== null ? trim($reason) : null;

        if ($def->requiresDeletionReason && ($reason === null || $reason === '')) {
            $this->archiveError = 'A reason is required to archive this record.';

            return;
        }

        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'remove',
            $id,
        );

        $this->dataModel()->archive($id, $reason !== '' ? $reason : null);

        $this->pendingArchiveId = null;
        $this->archiveReason = '';
        $this->archiveError = null;
    }

    /**
     * Restore a previously archived record. Permission node is the
     * same as archive (modify): if you can archive, you can restore.
     */
    public function restore(int $id): void
    {
        $def = $this->definition();

        if (! $def->archivable) {
            throw new \LogicException('TableBuilder engine: restore requested on a non-archivable definition');
        }

        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'modify',
            $id,
        );

        $this->dataModel()->restore($id);
    }

    /**
     * Open the delete confirmation modal. Always opens a modal so the user
     * must confirm — even when no reason or phrase is required.
     * The phraseHint is the record name/title supplied by the blade for
     * definitions that use confirmationPhrase: null.
     */
    public function confirmDelete(int $id, string $phraseHint = ''): void
    {
        $def = $this->definition();

        if (! $def->deletable) {
            throw new \LogicException('TableBuilder engine: delete requested on a non-deletable definition');
        }

        $this->pendingDeleteId = $id;
        $this->deleteReason = '';
        $this->deleteError = null;
        $this->deletePhraseInput = '';
        $this->pendingDeleteRequiredPhrase = $def->deletablePhraseRequired
            ? ($def->deletablePhrase ?? ($phraseHint !== '' ? $phraseHint : 'this record'))
            : null;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
        $this->deleteReason = '';
        $this->deleteError = null;
        $this->deletePhraseInput = '';
        $this->pendingDeleteRequiredPhrase = null;
    }

    public function cancelPendingBulkAction(): void
    {
        $this->pendingBulkActionKey = null;
        $this->bulkActionReason = '';
        $this->bulkActionError = null;
        $this->bulkPhraseInput = '';
        $this->pendingBulkRequiredPhrase = null;
    }

    /**
     * Modal-driven bulk action submit (for actions with requiresReason() or requiresPhrase() = true).
     * Validates phrase (if required) and reason (if required), then delegates to the action's handle().
     */
    public function submitPendingBulkAction(): void
    {
        if ($this->pendingBulkActionKey === null) {
            return;
        }

        $def = $this->definition();
        $key = $this->pendingBulkActionKey;
        $action = null;

        foreach ($def->bulkActions as $candidate) {
            if ($candidate->getKey() === $key) {
                $action = $candidate;
                break;
            }
        }

        if ($action === null) {
            return;
        }

        $reason = trim($this->bulkActionReason);

        if ($this->pendingBulkRequiredPhrase !== null) {
            if ($this->bulkPhraseInput !== $this->pendingBulkRequiredPhrase) {
                $this->bulkActionError = 'Please type the exact phrase to confirm.';

                return;
            }
        }

        if ($action->requiresReason() && $reason === '') {
            $this->bulkActionError = 'A reason is required to proceed.';

            return;
        }

        $user = $this->currentUser();
        $gate = app(PermissionGate::class);
        $gate->userCan($user, $def->permissions->remove);

        foreach ($this->selected as $id) {
            $gate->assertCanActOnRecord($user, $def, $this->dataModel(), 'remove', $id);
        }

        $count = count($this->selected);
        $result = $action->handle($this->selected, $this->dataModel(), $reason);

        $this->bulkMessage = $result['success'] ? $result['message'] : null;
        $this->bulkError = $result['success'] ? null : $result['message'];

        $this->selected = [];
        $this->selectAllOnPage = false;
        $this->pendingBulkActionKey = null;
        $this->bulkActionReason = '';
        $this->bulkActionError = null;
        $this->bulkPhraseInput = '';
        $this->pendingBulkRequiredPhrase = null;
    }

    /**
     * Modal-driven delete submit. Validates phrase (if required) and
     * reason (if required) before delegating to delete().
     */
    public function submitDelete(): void
    {
        if ($this->pendingDeleteId === null) {
            return;
        }

        $def = $this->definition();

        if ($this->pendingDeleteRequiredPhrase !== null) {
            if ($this->deletePhraseInput !== $this->pendingDeleteRequiredPhrase) {
                $this->deleteError = 'Please type the exact phrase to confirm.';

                return;
            }
        }

        if ($def->deletableReasonRequired && trim($this->deleteReason) === '') {
            $this->deleteError = 'A reason is required to delete this record.';

            return;
        }

        $this->delete($this->pendingDeleteId, $this->deleteReason);
    }

    /**
     * Permanently delete a single record.
     *
     * Layer 2 (remove node) and Layer 3 (canActOn) gates are enforced
     * via PermissionGate::assertCanActOnRecord. When the definition
     * declares ->deletable(reasonRequired: true) a non-empty reason is
     * mandatory.
     */
    public function delete(int $id, ?string $reason = null): void
    {
        $def = $this->definition();

        if (! $def->deletable && ! $def->allowDelete) {
            throw new \LogicException('TableBuilder engine: delete requested on a non-deletable definition');
        }

        $reason = $reason !== null ? trim($reason) : null;

        if ($def->deletableReasonRequired && ($reason === null || $reason === '')) {
            $this->deleteError = 'A reason is required to delete this record.';

            return;
        }

        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'remove',
            $id,
        );

        $this->dataModel()->delete($id, $reason !== '' ? $reason : null);

        $this->pendingDeleteId = null;
        $this->deleteReason = '';
        $this->deleteError = null;
    }

    /**
     * Save an inline cell edit submitted from Alpine (client-side edit state).
     *
     * Alpine tracks which cell is being edited and passes the value here
     * on commit. The server validates, permission-checks, and persists,
     * then dispatches browser events for success / error feedback.
     *
     * @param  int  $rowId  Primary key of the row being edited.
     * @param  string  $columnKey  Column key (display key, not editAs key).
     * @param  mixed  $value  New value submitted by the client.
     */
    public function saveEdit(int $rowId, string $columnKey, mixed $value): void
    {
        $def = $this->definition();

        if ($def->modifyMode !== 'inline') {
            return;
        }

        $column = $def->column($columnKey);

        if ($column === null) {
            $this->dispatch('inline-edit:error', message: 'Column not found.');

            return;
        }

        // Column-level opt-out.
        if ($column->getModifyInline() === false) {
            $this->dispatch('inline-edit:error', message: 'Column is not inline-editable.');

            return;
        }

        // Validate if rules are specified.
        if ($column->getRules() !== null) {
            $validator = Validator::make(
                [$column->getEditKey() => $value],
                [$column->getEditKey() => $column->getRules()],
            );

            if ($validator->fails()) {
                $this->dispatch('inline-edit:error', message: $validator->errors()->first($column->getEditKey()));

                return;
            }
        }

        // Permission check.
        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'modify',
            $rowId,
        );

        // Persist.
        $this->dataModel()->modify(
            $rowId,
            [$column->getEditKey() => $value],
        );

        $this->dispatch('edit-saved');
        $this->dispatch('architect:row-saved', id: $rowId);
    }

    /**
     * Save a multi-cell row edit submitted from Alpine row-mode.
     *
     * Validates ALL fields together so cross-field rules (after:start_date,
     * same:other, etc.) fire correctly, then persists in a single modify()
     * call. Returns per-field errors keyed by editKey on validation failure.
     *
     * @param  int  $rowId  Primary key of the row being edited.
     * @param  array<string, mixed>  $values  Map of editKey => new value.
     */
    public function saveRow(int $rowId, array $values): void
    {
        $def = $this->definition();

        if ($def->modifyMode !== 'inline') {
            return;
        }

        // Build the validation rule set from the editable columns whose
        // editKey is present in the submitted values map.
        $rules = [];
        $coerced = [];
        foreach ($def->columns as $column) {
            if (! $column->isEditable() || $column->getModifyInline() === false) {
                continue;
            }
            $editKey = $column->getEditKey();
            if (! array_key_exists($editKey, $values)) {
                continue;
            }
            $coerced[$editKey] = $values[$editKey];
            $colRules = $column->getRules();
            if ($colRules !== null && $colRules !== '') {
                $rules[$editKey] = $colRules;
            }
        }

        if ($rules !== []) {
            $validator = Validator::make($coerced, $rules);

            if ($validator->fails()) {
                $this->dispatch(
                    'row-edit:errors',
                    rowId: $rowId,
                    errors: $validator->errors()->toArray(),
                );

                return;
            }
        }

        // Permission check.
        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'modify',
            $rowId,
        );

        // Persist all fields in a single call.
        $this->dataModel()->modify($rowId, $coerced);

        $this->dispatch('edit-saved');
        $this->dispatch('architect:row-saved', id: $rowId);
    }

    /**
     * Toggle a single row id in the selection set. No-op when the
     * definition doesn't opt in to checkbox selection.
     */
    public function toggleSelect(int $id): void
    {
        if ($this->definition()->bulkActions === []) {
            return;
        }

        $idx = array_search($id, $this->selected, true);
        if ($idx === false) {
            $this->selected[] = $id;
        } else {
            unset($this->selected[$idx]);
            // Re-pack to a list so the wire payload stays a JSON array,
            // not an object. array_values() is the idiomatic one-liner.
            $this->selected = array_values($this->selected);
        }

        $this->dispatchSelectionChanged();
    }

    /**
     * Select / deselect every id passed in (typically all visible rows
     * on the current page). Idempotent.
     *
     * @param  array<int, int>  $ids
     */
    public function setSelection(array $ids, bool $select): void
    {
        if ($this->definition()->bulkActions === []) {
            return;
        }

        $current = array_flip($this->selected);
        foreach ($ids as $id) {
            if ($select) {
                $current[$id] = true;
            } else {
                unset($current[$id]);
            }
        }

        $this->selected = array_map('intval', array_keys($current));

        $this->dispatchSelectionChanged();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAllOnPage = false;
        $this->bulkMessage = null;
        $this->bulkError = null;

        $this->dispatchSelectionChanged();
    }

    /**
     * Broadcast the current selection to the page so external Alpine
     * components, sibling Livewire components, etc. can react without
     * polling the Alpine store. Always carries the instanceKey so
     * listeners can target a specific table.
     */
    private function dispatchSelectionChanged(): void
    {
        $this->dispatch(
            'architect:selection-changed',
            instanceKey: md5($this->definitionClass),
            selected: array_values($this->selected),
        );
    }

    /**
     * Invoke a bulk action by key against the current selection.
     *
     * Permission flow:
     *   - The action's permissionNode() (or a per-key default) gates
     *     the user's ability to invoke it at all.
     *   - For destructive actions (archive) the engine additionally
     *     enforces canActOn for every selected id so layer-3 scope
     *     rules are honoured per record.
     *
     * Export is special-cased: the action's handle() returns metadata
     * only; the engine dispatches a 'architect:export' browser
     * event with the selected ids so the client can navigate to the
     * streaming export endpoint (added in commit 11).
     */
    public function bulkAction(string $key): void
    {
        $def = $this->definition();
        $action = null;
        foreach ($def->bulkActions as $candidate) {
            if ($candidate->getKey() === $key) {
                $action = $candidate;
                break;
            }
        }

        if ($action === null) {
            throw new \LogicException("TableBuilder engine: unknown bulk action [{$key}]");
        }

        if ($this->selected === []) {
            $this->bulkError = 'Select at least one row first.';

            return;
        }

        $node = $action->permissionNode() ?? $this->defaultBulkPermissionNode($key);
        $user = $this->currentUser();
        $gate = app(PermissionGate::class);

        if (! $gate->userCan($user, $node)) {
            throw new AuthorizationException(
                "TableBuilder bulk action [{$key}] requires permission [{$node}]."
            );
        }

        // For destructive actions, enforce per-record scope before proceeding.
        if (in_array($key, ['archive', 'delete', 'restore'], true)) {
            foreach ($this->selected as $id) {
                $gate->assertCanActOnRecord($user, $def, $this->dataModel(), 'remove', $id);
            }
        }

        if ($key === 'export') {
            $this->dispatch('architect:export', ids: $this->selected, definitionClass: $this->definitionClass);
            $this->bulkMessage = 'Export started for '.count($this->selected).' record(s).';

            return;
        }

        if ($key === 'copy') {
            $this->dispatch('architect:copy', ids: $this->selected, definitionClass: $this->definitionClass);

            return;
        }

        if ($key === 'email') {
            $this->dispatch('architect:bulk-email', ids: $this->selected, definitionClass: $this->definitionClass);

            return;
        }

        if ($key === 'status') {
            /** @var BulkStatusAction $action */
            $this->dispatch('architect:bulk-status', ids: $this->selected, options: $action->options(), definitionClass: $this->definitionClass);

            return;
        }

        // For actions that require a reason or phrase, open the generic modal.
        if ($action->requiresReason() || $action->requiresPhrase()) {
            $this->pendingBulkActionKey = $key;
            $this->bulkActionReason = '';
            $this->bulkActionError = null;
            $this->bulkPhraseInput = '';
            $this->pendingBulkRequiredPhrase = $action->requiresPhrase() ? ($action->getPhrase() ?? 'confirm') : null;

            return;
        }

        $result = $action->handle($this->selected, $this->dataModel());

        $this->bulkMessage = $result['success'] ? $result['message'] : null;
        $this->bulkError = $result['success'] ? null : $result['message'];

        $this->selected = [];
        $this->selectAllOnPage = false;
    }

    /**
     * Default permission node when a bulk action declines to declare
     * its own. archive → remove; export → read; everything else
     * inherits modify (the broad CRUD gate).
     */
    private function defaultBulkPermissionNode(string $key): string
    {
        $perms = $this->definition()->permissions;

        return match ($key) {
            'archive', 'delete' => $perms->remove,
            'restore' => $perms->modify,
            'export', 'copy' => $perms->read,
            default => $perms->modify,
        };
    }

    /**
     * Export the dataset to the specified format (CSV, Excel, PDF, or HTML).
     *
     * Respects the read permission. When ids is provided, exports only
     * those specific rows (driven by bulk selection).
     *
     * @param  string  $format  One of: 'csv', 'excel', 'pdf', 'html'
     * @param  array<int, int>|null  $ids
     */
    public function export(string $format = 'csv', ?array $ids = null): StreamedResponse|Response|View
    {
        $def = $this->definition();

        if (count($def->exportFormats) === 0) {
            throw new \LogicException('TableBuilder engine: export requested on a non-exportable definition');
        }

        if (! in_array($format, $def->exportFormats, true)) {
            throw new \InvalidArgumentException("Export format '{$format}' not enabled (allowed: ".implode(', ', $def->exportFormats).')');
        }

        $user = $this->currentUser();
        app(PermissionGate::class)->assertCanRead($user, $def);

        $context = $this->buildQueryContext(page: 1, perPage: CsvStreamExporter::PAGE_SIZE);

        return match ($format) {
            'csv' => app(CsvStreamExporter::class)->stream($def, $context, $ids, $user),
            'excel' => app(ExcelExporter::class)->stream($def, $context, $ids, $user),
            'html' => app(HtmlExporter::class)->stream($def, $context, $ids, $user),
            'print' => $this->print(),
            // TODO: implement a real PdfExporter (e.g. using Browsershot or
            // DomPDF). For now, fall back to the HTML exporter so users can
            // use the browser's built-in Print → Save as PDF.
            'pdf' => app(HtmlExporter::class)->stream($def, $context, $ids, $user),
            default => throw new \LogicException("Export format '{$format}' not yet implemented"),
        };
    }

    /**
     * Flip the boolean value of a toggleable column for a single record.
     *
     * Called by wire:click from the toggle switch cell. Reads the current
     * value from the row and inverts it, then delegates to modify().
     * Permission defaults to the table's modify node unless the column
     * declares its own ->toggleable(permission: '...').
     */
    public function toggleColumn(string $columnKey, int $id): void
    {
        $def = $this->definition();

        $column = $def->column($columnKey);

        if ($column === null || ! $column->isToggleable()) {
            throw new \LogicException("Column '{$columnKey}' is not toggleable");
        }

        $permissionNode = $column->getTogglePermission() ?? $def->permissions->modify;

        if (! app(PermissionGate::class)->userCan($this->currentUser(), $permissionNode)) {
            abort(403, 'Insufficient permissions to toggle this column');
        }

        app(PermissionGate::class)->assertCanActOnRecord(
            $this->currentUser(),
            $def,
            $this->dataModel(),
            'modify',
            $id,
        );

        // Single atomic UPDATE that flips the value in-place. Replaces the
        // previous read + exists + update sequence (3 round-trips → 1).
        // Note: forList() cannot be used here because the QueryContext does not
        // carry filterDefinitions, so filters: ['id' => $id] would be silently
        // ignored by ModuleTableFilterPipeline and the wrong row would be read.
        $modelClass = $this->dataModel()->modelClass();

        /** @var Builder<Model> $query */
        $query = $modelClass::whereKey($id);
        $affected = $query->update([
            $columnKey => DB::raw(
                'CASE WHEN '.$query->getQuery()->getGrammar()->wrap($columnKey).' = 1 THEN 0 ELSE 1 END'
            ),
        ]);

        if ($affected === 0) {
            abort(404, 'Record not found');
        }
    }

    /**
     * Handle a custom row action (e.g., 'clone', 'email', 'view-audit').
     *
     * Validates permission and visibility checks from the RowAction
     * definition, then dispatches a browser event for client-side / redirect handling.
     */
    public function handleRowAction(string $key, int $id): void
    {
        $def = $this->definition();
        $action = collect($def->rowActions)->first(fn ($a) => $a->getKey() === $key);

        if ($action === null) {
            throw new \LogicException("Unknown row action '{$key}'");
        }

        $user = $this->currentUser();

        // Permission check via PermissionGate — consistent with blade-side rendering.
        $permission = $action->getPermission();
        if ($permission !== null) {
            if (! app(PermissionGate::class)->userCan($user, $permission)) {
                abort(403, 'Insufficient permissions for this action');
            }
        }

        // Visibility check: fetch the actual row to confirm it exists.
        $row = $this->dataModel()->forList(new QueryContext(
            search: '',
            filters: ['id' => $id],
            sortColumn: null,
            sortDirection: 'asc',
            page: 1,
            perPage: 1,
            includeArchived: true,
            scope: $this->scope,
        ))->items()[0] ?? null;

        if ($row === null || ! $action->isVisibleFor($this->ensureArray($row))) {
            abort(404, 'Record not found or action not available');
        }

        // If the action declares a custom panel view, open the panel in
        // 'custom' mode rather than dispatching a generic browser event.
        if ($action->getPanelBlade() !== null) {
            $this->dispatch('architect:open-custom',
                definitionClass: $this->definitionClass,
                title: $action->getPanelTitle() ?? $action->getLabel(),
                blade: $action->getPanelBlade(),
                data: $this->ensureArray($row),
            );

            return;
        }

        // 'view' action: if the Eloquent model implements HasViewAll, open
        // the panel in view mode instead of dispatching the browser event.
        if ($action->getKey() === 'view') {
            $modelClass = $this->dataModel()->modelClass();
            if (is_a($modelClass, HasViewAll::class, true)) {
                $this->dispatch('architect:open-view',
                    definitionClass: $this->definitionClass,
                    id: $id,
                );

                return;
            }
        }

        // Default: dispatch browser event for client-side / redirect handling.
        $this->dispatch('row-action:'.$key, id: $id);
    }

    /**
     * Generate a print-friendly view of the current table.
     */
    public function print(): View
    {
        $def = $this->definition();
        $user = $this->currentUser();

        app(PermissionGate::class)->assertCanRead($user, $def);

        $visibility = app(FieldVisibilityFilter::class);
        $columns = $visibility->visibleColumns($user, $def);
        $allowedFlip = $visibility->allowedKeysForRow($columns);

        // Print view caps at 1000 rows — beyond that the rendered HTML
        // would exhaust browser memory anyway. Heavier exports should
        // use the CSV/Excel paths which truly stream.
        $printCap = 1000;

        $context = $this->buildQueryContext(
            page: 1,
            perPage: ExportRowIterator::PAGE_SIZE,
        );

        // Use the same iterator the exporters use — honours
        // SupportsExportStreaming when the data model implements it,
        // otherwise pages forList() in PAGE_SIZE chunks. Materialise
        // into a plain array so the Blade template can foreach + count.
        $rows = [];
        foreach (ExportRowIterator::iterate(
            $this->dataModel(),
            $context,
            $printCap,
        ) as $row) {
            $rows[] = $visibility->stripRowUsingAllowed($this->ensureArray($row), $allowedFlip);
        }

        return view('architect::table.print', [
            'definition' => $def,
            'columns' => $columns,
            'rows' => $rows,
            'truncated' => count($rows) >= $printCap,
            'rowCap' => $printCap,
        ]);
    }

    /**
     * Re-render trigger fired by sibling FormPanel after a successful
     * create / modify. The handler body is intentionally empty —
     * Livewire re-renders the component on any public action call.
     *
     * The optional $instanceKey payload lets the emitter target a
     * specific Engine instance when several embedded tables share the
     * page. An empty key is treated as a legacy broadcast and refreshes
     * every listening Engine (preserves backwards compatibility for any
     * caller that hasn't been updated yet).
     */
    #[On('architect:refresh')]
    public function refresh(string $instanceKey = ''): void
    {
        if ($instanceKey !== '' && $instanceKey !== md5($this->definitionClass)) {
            $this->skipRender();
        }
    }

    /**
     * External fire-and-forget filter setter — lets any component on the
     * page (Alpine button, sibling Livewire component, plain JS) drive
     * a specific Engine instance's filter state without holding a wire
     * reference. Guarded by instanceKey so multiple embedded tables on
     * the same page each only respond to their own targeted events.
     */
    #[On('architect:set-filter')]
    public function externalSetFilter(string $instanceKey, string $name, mixed $value): void
    {
        if ($instanceKey !== md5($this->definitionClass)) {
            $this->skipRender();

            return;
        }

        $this->setFilter($name, $value);
    }

    public function render(): View
    {
        $def = $this->definition();
        $user = $this->currentUser();

        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            // Re-assert read on every render — defence in depth in case
            // session permissions changed between mount and re-render.
            app(PermissionGate::class)->assertCanRead($user, $def);

            $visibility = app(FieldVisibilityFilter::class);
            $columns = $visibility->visibleColumns($user, $def);
            // Compute the allow-key map once and reuse for every row in the
            // page; previously stripRow() recomputed visibleColumns() twice
            // per row, which dominated render time on large pages.
            $allowedFlip = $visibility->allowedKeysForRow($columns);

            $context = $this->buildQueryContext();

            $paginator = $this->dataModel()->forList($context);

            $rows = array_map(
                fn (mixed $row): array => $visibility->stripRowUsingAllowed($this->ensureArray($row), $allowedFlip),
                $paginator->items()
            );

            // When viewing the archived set of an archivable table, every row returned
            // by the query is by definition archived. Inject the flag so the blade does
            // not rely on each data model opting-in to setting 'archived' in toListRow().
            if ($def->archivable && $this->includeArchived) {
                $rows = array_map(
                    fn (array $row): array => array_merge(['archived' => true], $row),
                    $rows,
                );
            }
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = 'You do not have permission to view this table.';
            $this->dispatch('architect:unauthorized');
            $columns = [];
            $rows = [];
            $paginator = new LengthAwarePaginator([], 0, $this->perPage, $this->page);
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this table. Please try again.';
            report($e);
            $columns = [];
            $rows = [];
            $paginator = new LengthAwarePaginator([], 0, $this->perPage, $this->page);
        } finally {
            $this->isLoading = false;
        }

        // Share definition with layout so pageTitle and breadcrumbs are available.
        // Skip when embedded (e.g. inside SPA tabs) so we don't overwrite the
        // parent component's SpaSharedDefinition which owns breadcrumb inheritance.
        if (! $this->embedded) {
            view()->share('definition', $def);
        }

        return view('architect::table.engine', [
            'definition' => $def,
            'columns' => $columns,
            'rows' => $rows,
            'paginator' => $paginator,
            // Stable per-instance identifier used for offcanvas IDs,
            // localStorage keys, the Alpine state-mirror store, and
            // event payload guarding (set-filter, refresh, etc.).
            'instanceKey' => md5($this->definitionClass),
            // Pre-computed total so the blade's Alpine state mirror
            // can publish it without re-reading the paginator.
            'total' => $paginator->total(),
        ]);
    }

    public function definition(): ArchitectTableDefinition
    {
        if ($this->cachedDefinition !== null) {
            return $this->cachedDefinition;
        }

        $class = $this->definitionClass;

        if (! class_exists($class) || ! method_exists($class, 'definition')) {
            throw new \LogicException(
                "TableBuilder engine: '{$class}' must expose a static ::definition() method"
            );
        }

        /** @var ArchitectTableDefinition $def */
        $def = $class::definition();

        return $this->cachedDefinition = $def;
    }

    private function dataModel(): ArchitectDataModel
    {
        if ($this->cachedDataModel !== null) {
            return $this->cachedDataModel;
        }

        /** @var ArchitectDataModel $instance */
        $instance = app($this->definition()->dataModelClass);

        return $this->cachedDataModel = $instance;
    }

    private function currentUser(): ?Authenticatable
    {
        if ($this->cachedUserResolved) {
            return $this->cachedUser;
        }

        $this->cachedUser = auth()->user();
        $this->cachedUserResolved = true;

        return $this->cachedUser;
    }

    /**
     * Paginators built from query-builder reads return stdClass items;
     * paginators built from collection mappings (as in
     * CommitteesTableModel) return arrays. Normalise here so the
     * visibility filter sees a uniform array shape.
     *
     * @return array<string, mixed>
     */
    private function ensureArray(mixed $row): array
    {
        if (is_array($row)) {
            /** @var array<string, mixed> $row */
            return $row;
        }

        if (is_object($row)) {
            /** @var array<string, mixed> $vars */
            $vars = get_object_vars($row);

            return $vars;
        }

        throw new \RuntimeException(
            'TableBuilder engine: paginator row must be array or object, got '.get_debug_type($row)
        );
    }

    /**
     * Build the QueryContext used by render(), export() and print().
     *
     * All three call sites previously inlined an identical 9-arg
     * QueryContext constructor; centralising the assembly removes the
     * repetition and guarantees future fields (e.g. an additional
     * scope channel) are added in one place. Defaults reflect the
     * common "current page" view; override $page/$perPage for
     * full-result-set callers (export, print).
     *
     * NOTE: handleRowAction() is deliberately NOT routed through this
     * helper — it issues a degenerate id-lookup query with empty
     * search/filters, perPage=1 and includeArchived=true, sharing none
     * of the user-facing context.
     */
    private function buildQueryContext(?int $page = null, ?int $perPage = null): QueryContext
    {
        $def = $this->definition();

        return new QueryContext(
            search: $this->search,
            filters: $this->filters,
            sortColumn: $this->sortColumn,
            sortDirection: $this->sortDirection === 'desc' ? 'desc' : 'asc',
            page: $page ?? $this->page,
            perPage: $perPage ?? $this->perPage,
            includeArchived: $this->includeArchived,
            scope: $this->scope,
            filterDefinitions: $def->filtersByName(),
        );
    }
}
