<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Navigator\ArchitectNavigatorDefinition;
use Entelechy\Architect\Table\Actions\BulkArchiveAction;
use Entelechy\Architect\Table\Actions\BulkCopyAction;
use Entelechy\Architect\Table\Actions\BulkDeleteAction;
use Entelechy\Architect\Table\Actions\BulkEmailAction;
use Entelechy\Architect\Table\Actions\BulkExportAction;
use Entelechy\Architect\Table\Actions\BulkRestoreAction;
use Entelechy\Architect\Table\Actions\BulkStatusAction;
use Entelechy\Architect\Table\Actions\HeaderAction;
use Entelechy\Architect\Table\Actions\RowAction;
use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\ArchitectField;
use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Entelechy\Architect\Table\Contracts\ArchitectRowAction;
use Entelechy\Architect\Table\Import\ImportDefinition;

/**
 * Fluent builder for ArchitectTableDefinition.
 *
 * Usage:
 *   TableBuilder::make()
 *       ->title('Activity Committees')
 *       ->model(CommitteesTableModel::class)
 *       ->permissions(read: '...', create: '...', modify: '...', remove: '...')
 *       ->column(...)
 *       ->field(...)
 *       ->build()
 *
 * The builder is mutable and chainable. Calling build() produces the
 * immutable definition. Definitions returned by ::definition() in module
 * classes can either return the builder directly (the engine will call
 * build() implicitly) or call ->build() themselves — both shapes are
 * supported via the toDefinition() helper on the engine.
 */
final class TableBuilder
{
    private ?string $title = null;

    private ?string $pageTitle = null;

    /** @var array<int, array{title: string, url?: string|false}> */
    private array $breadcrumbs = [];

    /** @var class-string<ArchitectDataModel>|null */
    private ?string $dataModelClass = null;

    private ?PermissionMap $permissions = null;

    /** Whether the New button / create flow is enabled. Defaults to on. */
    private bool $creatable = true;

    /** Whether the Edit button / modify flow is enabled. Defaults to on. */
    private bool $modifiable = true;

    /** Dispatch architect:open-record instead of the default create panel. */
    private bool $createOpenInTab = false;

    private ?string $createTabType = null;

    /** Fallback URL when no ModuleTabsManager is present. */
    private ?string $createUrl = null;

    /** Dispatch architect:open-record instead of the default edit panel. */
    private bool $modifyOpenInTab = false;

    private ?string $modifyTabType = null;

    /** Fallback URL when no ModuleTabsManager is present. */
    private ?string $modifyUrl = null;

    private string $formMode = 'slide-over';

    private bool $filterPersistence = false;

    private bool $filterBookmarkFilters = false;

    /** @var list<Column> */
    private array $columns = [];

    /** @var list<ArchitectField> */
    private array $fields = [];

    /** @var list<ArchitectFilter> */
    private array $filters = [];

    /** @var array<string, mixed> */
    private array $defaultFilters = [];

    private bool $archivable = false;

    private bool $requiresDeletionReason = false;

    private bool $allowUnarchive = true;

    private bool $allowDelete = false;

    private bool $deletable = false;

    private bool $deletableReasonRequired = false;

    private bool $deletablePhraseRequired = false;

    private ?string $deletablePhrase = null;

    private bool $archivablePhraseRequired = false;

    private ?string $archivablePhrase = null;

    private bool $animateRows = true;

    private bool $animatePanels = true;

    private bool $animateErrors = true;

    private bool $animateButtons = true;

    private bool $selectableRows = false;

    private ?string $createMode = null;

    /** @var list<string>|null Column names editable in create mode (null = all) */
    private ?array $createColumns = null;

    private ?string $modifyMode = null;

    /** @var list<string>|null Column names editable in modify mode (null = all) */
    private ?array $modifyColumns = null;

    private ?CustomForm $customCreateForm = null;

    private ?CustomForm $customModifyForm = null;

    /** @var list<ArchitectBulkAction> */
    private array $bulkActions = [];

    /** @var list<string> */
    private array $exportFormats = [];

    /** @var list<RowAction> */
    private array $rowActions = [];

    /** @var list<ArchitectRowAction> */
    private array $customRowActions = [];

    /** @var list<HeaderAction> */
    private array $headerActions = [];

    private bool $clonable = false;

    /** @var list<string> */
    private array $clonableExcept = [];

    private bool $auditable = false;

    private ?string $documentationUrl = null;

    private bool $viewable = false;

    private ?string $viewUrl = null;

    private bool $hideId = false;

    private ?ArchitectNavigatorDefinition $navigator = null;

    private ?ImportDefinition $importDefinition = null;

    /**
     * Custom page-level alert banners rendered above the table.
     *
     * @var list<array{type: string, message: string}>
     */
    private array $alerts = [];

    /** When true, the engine's auto-detected default banners (e.g. read-only) are suppressed. */
    private bool $suppressAutoAlerts = false;

    private bool $hideSearchBar = false;

    /** Auto-refresh interval in seconds; null disables polling. */
    private ?int $autoRefreshSeconds = null;

    /** When auto-refresh is enabled, render a live countdown badge next to the refresh button. */
    private bool $autoRefreshCountdown = false;

    /** Optional column/key used for auto-refresh fingerprint checks. */
    private ?string $autoRefreshFingerprintOn = null;

    /** When true, the create form stays open and clears its fields after a successful save. */
    private bool $persistOnCreate = false;

    /**
     * Records-per-page selector shown in the card-footer.
     *
     * $defaultPerPage is the initial perPage value loaded by the engine.
     * $perPageOptions is the ordered list of values shown in the selector;
     * the default value must be a member of this list.
     *
     * Set to an empty array to hide the selector and use a fixed page size.
     *
     * @var list<int>
     */
    private array $perPageOptions = [25, 50, 100];

    private int $defaultPerPage = 25;

    /** Scroll mode; null = derived automatically from whether paginate() was called. */
    private ?string $scrollMode = null;

    /** For container scroll: number of visible body rows before overflow. */
    private ?int $visibleRows = null;

    /** Whether paginate() was explicitly configured. */
    private bool $isPaginated = false;

    public static function make(): self
    {
        return new self;
    }

    public function title(?string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function pageTitle(?string $pageTitle): self
    {
        $clone = clone $this;
        $clone->pageTitle = $pageTitle;

        return $clone;
    }

    /**
     * Set breadcrumb navigation items.
     *
     * Matches singleTable pattern. Each breadcrumb is an array
     * with 'title' (string) and optional 'url' (string|false).
     *
     * Example:
     *   ->breadcrumbs([
     *       ['title' => 'Activities', 'url' => '/activities'],
     *       ['title' => 'Committees'],
     *   ])
     *
     * @param  array<int, array{title: string, url?: string|false}>  $breadcrumbs
     */
    public function breadcrumbs(array $breadcrumbs): self
    {
        $clone = clone $this;
        $clone->breadcrumbs = $breadcrumbs;

        return $clone;
    }

    /**
     * @param  class-string<ArchitectDataModel>  $class
     */
    public function model(string $class): self
    {
        $clone = clone $this;
        $clone->dataModelClass = $class;

        return $clone;
    }

    public function permissions(string $read, string $create, string $modify, string $remove): self
    {
        $clone = clone $this;
        $clone->permissions = new PermissionMap($read, $create, $modify, $remove);

        return $clone;
    }

    /**
     * Disable (or re-enable) both the create and modify flows in one call.
     *
     * Shortcut for ->creatable(!$readOnly)->modifiable(!$readOnly). Archiving
     * and deletion are unaffected — control those independently via
     * ->archivable() / ->deletable().
     */
    public function readOnly(bool $readOnly = true): self
    {
        $clone = clone $this;
        $clone->creatable = ! $readOnly;
        $clone->modifiable = ! $readOnly;

        return $clone;
    }

    /**
     * Enable or disable the New button / create flow. Enabled by default.
     */
    public function creatable(bool $creatable = true): self
    {
        $clone = clone $this;
        $clone->creatable = $creatable;

        return $clone;
    }

    /**
     * Enable or disable the Edit button / modify flow. Enabled by default.
     */
    public function modifiable(bool $modifiable = true): self
    {
        $clone = clone $this;
        $clone->modifiable = $modifiable;

        return $clone;
    }

    /**
     * Configure the form-rendering mode for create and modify actions.
     *
     * Valid create modes: 'slide-over' | 'modal' | 'wizard'
     * Valid modify modes: 'slide-over' | 'modal' | 'wizard' | 'inline'
     *
     * 'inline' modify uses the new smart inline-edit engine — clicking a
     * cell with dependencies opens the whole row; clicking a cell with
     * no dependencies (or marked ->modifyInline()) opens just that cell.
     *
     * Both legacy single-string and named-arg invocations are accepted:
     *   ->formMode('slide-over')                              (legacy)
     *   ->formMode(create: 'slide-over', modify: 'inline')   (consolidated)
     */
    public function formMode(
        string $create = 'slide-over',
        string $modify = 'slide-over',
    ): self {
        $clone = clone $this;
        $panelModes = ['slide-over', 'modal', 'wizard'];

        if (! in_array($create, $panelModes, true)) {
            throw new \InvalidArgumentException(
                'create form mode must be one of: '.implode(', ', $panelModes).". Got '{$create}'"
            );
        }

        $modifyValid = array_merge($panelModes, ['inline']);
        if (! in_array($modify, $modifyValid, true)) {
            throw new \InvalidArgumentException(
                'modify form mode must be one of: '.implode(', ', $modifyValid).". Got '{$modify}'"
            );
        }

        // Panel-render style — fall back to create's style when modify is inline.
        $clone->formMode = $modify === 'inline' ? $create : $modify;

        // Auto-enable create + modify (legacy ->create()/->modify() flags).
        $clone->createMode = 'slide-out';
        $clone->modifyMode = $modify === 'inline' ? 'inline' : 'slide-out';

        return $clone;
    }

    /**
     * Attach a custom Forms Core definition to either create or modify flow.
     *
     * @param  'create'|'modify'  $for
     * @param  class-string  $definitionClass
     */
    public function customForm(
        string $for,
        string $definitionClass,
        string $mode = 'modal',
        ?string $url = null,
        ?string $tabType = null,
        ?string $callbackQueryKey = 'architect_refresh',
        bool $postMessageRefresh = true,
    ): self {
        if (! in_array($for, ['create', 'modify'], true)) {
            throw new \InvalidArgumentException("customForm for must be 'create' or 'modify', got '{$for}'");
        }

        $clone = clone $this;
        $config = new CustomForm(
            definitionClass: $definitionClass,
            mode: $mode,
            url: $url,
            tabType: $tabType,
            callbackQueryKey: $callbackQueryKey,
            postMessageRefresh: $postMessageRefresh,
        );

        if ($for === 'create') {
            $clone->customCreateForm = $config;
            $clone->creatable = true;
        } else {
            $clone->customModifyForm = $config;
            $clone->modifiable = true;
        }

        return $clone;
    }

    /**
     * Configure the filter slide-over panel.
     *
     * @param  bool  $persistence  Offer a "Remember filters" toggle that persists
     *                             the active filter set to localStorage per user,
     *                             restoring it automatically on next visit.
     * @param  bool  $bookmarkFilters  Allow users to snapshot and name their current
     *                                 filter criteria, storing up to 10 named sets in
     *                                 localStorage per user for one-click re-application.
     */
    public function filterPanel(bool $persistence = false, bool $bookmarkFilters = false): self
    {
        $clone = clone $this;
        $clone->filterPersistence = $persistence;
        $clone->filterBookmarkFilters = $bookmarkFilters;

        return $clone;
    }

    public function column(Column $column): self
    {
        $this->columns[] = $column;

        return $this;
    }

    public function field(ArchitectField $field): self
    {
        $this->fields[] = $field;

        return $this;
    }

    public function filter(ArchitectFilter $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Register a custom filter control.
     *
     * This is a semantic alias of filter() that improves readability when
     * the filter uses a bespoke UI and/or custom apply logic.
     */
    public function customFilter(ArchitectFilter $filter): self
    {
        return $this->filter($filter);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function defaultFilters(array $values): self
    {
        $clone = clone $this;
        $clone->defaultFilters = $values;

        return $clone;
    }

    /**
     * Enable archiving (soft deletion) for this table.
     *
     * @param  bool  $archivable  Whether archiving is enabled.
     * @param  bool  $reasonRequired  Whether a free-text reason must be provided when archiving.
     * @param  bool  $allowUnarchive  Show an Unarchive button on archived rows (default true).
     * @param  bool  $allowDelete  Show a permanent-delete button on archived rows (default false).
     *                             Independent of ->deletable() — does not require it to be set.
     */
    public function archivable(
        bool $archivable = true,
        bool $reasonRequired = false,
        bool $allowUnarchive = true,
        bool $allowDelete = false,
        bool $phraseRequired = false,
        ?string $confirmationPhrase = null,
    ): self {
        $clone = clone $this;
        $clone->archivable = $archivable;
        $clone->requiresDeletionReason = $reasonRequired;
        $clone->allowUnarchive = $allowUnarchive;
        $clone->allowDelete = $allowDelete;
        $clone->archivablePhraseRequired = $phraseRequired;
        $clone->archivablePhrase = $confirmationPhrase;

        return $clone;
    }

    /**
     * Require a deletion reason when archiving records.
     *
     * @deprecated Use archivable(reasonRequired: true) instead
     */
    public function requiresDeletionReason(bool $required = true): self
    {
        $clone = clone $this;
        $clone->requiresDeletionReason = $required;

        return $clone;
    }

    /**
     * Enable permanent deletion (hard delete) for this table.
     *
     * @param  bool  $deletable  Whether deletion is enabled
     * @param  bool  $reasonRequired  Whether a reason must be provided (opens a textarea)
     * @param  bool  $phraseRequired  Whether the user must type a phrase to confirm
     * @param  string|null  $confirmationPhrase  Fixed phrase to type; null = use the record's name/title
     */
    public function deletable(
        bool $deletable = true,
        bool $reasonRequired = false,
        bool $phraseRequired = false,
        ?string $confirmationPhrase = null,
    ): self {
        $clone = clone $this;
        $clone->deletable = $deletable;
        $clone->deletableReasonRequired = $reasonRequired;
        $clone->deletablePhraseRequired = $phraseRequired;
        $clone->deletablePhrase = $confirmationPhrase;

        return $clone;
    }

    /** @deprecated Use bulkActions() instead. */
    public function selectableRows(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->selectableRows = $enabled;

        return $clone;
    }

    /**
     * Declare all bulk actions for this table in a single call.
     *
     * Modern named-argument style (recommended):
     *   ->bulkActions(
     *       delete: true,
     *       archive: ['reasonRequired' => true],
     *       restore: true,
     *       export: ['options' => ['csv' => true, 'excel' => true]],
     *       copy: ['options' => ['clipboard' => true, 'markdown' => true]],
     *       email: true,
     *       status: ['options' => ['open', 'closed', 'pending']],
     *   )
     *
     * Legacy array style is still supported:
     *   ->bulkActions([
     *       'delete' => true,
     *       'archive' => ['reasonRequired' => true],
     *       'export' => ['options' => ['csv' => true, 'excel' => true]],
     *   ])
     *
     * False/null values are skipped. Implies selectableRows().
     * Unknown keys throw InvalidArgumentException.
     *
     * @param  array<string, bool|array<string, mixed>>|null  $config  Legacy positional array config
     * @param  bool|array<string, mixed>|null  $delete
     * @param  bool|array<string, mixed>|null  $archive
     * @param  bool|array<string, mixed>|null  $restore
     * @param  bool|array<string, mixed>|null  $export
     * @param  bool|array<string, mixed>|null  $copy
     * @param  bool|array<string, mixed>|null  $email
     * @param  bool|array<string, mixed>|null  $status
     */
    public function bulkActions(
        ?array $config = null,
        bool|array|null $delete = null,
        bool|array|null $archive = null,
        bool|array|null $restore = null,
        bool|array|null $export = null,
        bool|array|null $copy = null,
        bool|array|null $email = null,
        bool|array|null $status = null,
    ): self {
        $clone = clone $this;
        $clone->selectableRows = true;

        $normalized = $config ?? [];

        if ($delete !== null) {
            $normalized['delete'] = $delete;
        }
        if ($archive !== null) {
            $normalized['archive'] = $archive;
        }
        if ($restore !== null) {
            $normalized['restore'] = $restore;
        }
        if ($export !== null) {
            $normalized['export'] = $export;
        }
        if ($copy !== null) {
            $normalized['copy'] = $copy;
        }
        if ($email !== null) {
            $normalized['email'] = $email;
        }
        if ($status !== null) {
            $normalized['status'] = $status;
        }

        foreach ($normalized as $key => $options) {
            if ($options === false) {
                continue;
            }

            $opts = is_array($options) ? $options : [];
            $reasonRequired = (bool) ($opts['reasonRequired'] ?? false);

            $action = match ($key) {
                'delete' => $reasonRequired
                                 ? BulkDeleteAction::make()->withReasonRequired()
                                 : BulkDeleteAction::make(),
                'archive' => $reasonRequired
                                 ? BulkArchiveAction::make()->withReasonRequired()
                                 : BulkArchiveAction::make(),
                'restore' => BulkRestoreAction::make(),
                'export' => isset($opts['options'])
                                 ? BulkExportAction::make()->withOptions((array) $opts['options'])
                                 : BulkExportAction::make(),
                'copy' => isset($opts['options'])
                                 ? BulkCopyAction::make()->withOptions((array) $opts['options'])
                                 : BulkCopyAction::make(),
                'email' => BulkEmailAction::make(),
                'status' => isset($opts['options'])
                                 ? BulkStatusAction::make()->withOptions(array_values((array) $opts['options']))
                                 : BulkStatusAction::make(),
                default => throw new \InvalidArgumentException(
                    "TableBuilder: unknown bulk action key [{$key}]. "
                    .'Use customBulkAction() for custom actions.'
                ),
            };

            $clone->bulkActions[] = $action;
        }

        return $clone;
    }

    /**
     * Register a one-off custom bulk action.
     *
     * Implement ArchitectBulkAction for full control over key, label, icon,
     * colour, permission node, confirmation, and execution logic.
     * Implies selectableRows().
     */
    public function customBulkAction(ArchitectBulkAction $action): self
    {
        $clone = clone $this;
        $clone->selectableRows = true;
        $clone->bulkActions[] = $action;

        return $clone;
    }

    /**
     * Enable bulk permanent deletion for this table.
     *
     * @param  bool  $reasonRequired  Whether a reason must be provided
     * @param  bool  $phraseRequired  Whether the user must type a phrase to confirm
     * @param  string|null  $confirmationPhrase  Fixed phrase to type; null = ignored for bulk
     */
    public function bulkDelete(bool $reasonRequired = false, bool $phraseRequired = false, ?string $confirmationPhrase = null): self
    {
        $action = BulkDeleteAction::make();
        if ($reasonRequired) {
            $action = $action->withReasonRequired();
        }
        if ($phraseRequired) {
            $action = $action->withPhraseRequired($confirmationPhrase);
        }

        $clone = clone $this;
        $clone->selectableRows = true;
        // Replace any existing delete bulk action rather than duplicating it
        $clone->bulkActions = array_values(
            array_filter($clone->bulkActions, fn ($a) => $a->getKey() !== 'delete')
        );
        $clone->bulkActions[] = $action;

        return $clone;
    }

    /**
     * @deprecated Use ->customBulkAction($action) for custom actions or
     *             ->bulkActions(archive: true, export: true, ...) for built-ins.
     */
    public function bulkAction(ArchitectBulkAction $action): self
    {
        return $this->customBulkAction($action);
    }

    /**
     * Enable export with specified formats.
     *
     * @param  array<int, string>|bool  $formats  ['csv', 'excel', 'pdf', 'html'] or true for default ['csv']
     */
    public function exportable(array|bool $formats = true): self
    {
        $clone = clone $this;
        if ($formats === true) {
            $clone->exportFormats = ['csv'];
        } elseif ($formats === false) {
            $clone->exportFormats = [];
        } else {
            $clone->exportFormats = array_values($formats);
        }

        return $clone;
    }

    public function rowAction(RowAction $action): self
    {
        $this->rowActions[] = $action;

        return $this;
    }

    /**
     * Register a one-off custom row action.
     *
     * Implement ArchitectRowAction for full control over key, label, icon,
     * colour, permission node, confirmation, visibility, and the actual
     * handle() logic executed against that single row. Unlike rowAction()
     * (presentation-only — link, custom panel, or a raw browser event),
     * a custom row action's handle() runs real PHP and returns a
     * success/message result surfaced as an inline banner.
     */
    public function customRowAction(ArchitectRowAction $action): self
    {
        $clone = clone $this;
        $clone->customRowActions[] = $action;

        return $clone;
    }

    public function headerAction(HeaderAction $action): self
    {
        $this->headerActions[] = $action;

        return $this;
    }

    /**
     * @param  list<string>  $except  Field names to NOT carry over when cloning a record.
     */
    public function clonable(array $except = []): self
    {
        $clone = clone $this;
        $clone->clonable = true;
        $clone->clonableExcept = $except;

        return $clone;
    }

    public function auditable(bool $auditable = true): self
    {
        $clone = clone $this;
        $clone->auditable = $auditable;

        return $clone;
    }

    /**
     * Enable view/details navigation button.
     *
     * Adds a "View" row action that navigates to the record detail page.
     * Defaults: read permission, info color, external-link icon.
     *
     * @param  string|null  $url  Custom URL pattern (defaults to table-specific view route)
     */
    public function viewable(?string $url = null): self
    {
        $clone = clone $this;
        $clone->viewable = true;
        $clone->viewUrl = $url;

        return $clone;
    }

    public function documentation(string $url): self
    {
        $clone = clone $this;
        $clone->documentationUrl = $url;

        return $clone;
    }

    /**
     * Attach a ModuleNavigator to this table.
     *
     * The navigator is rendered above the table card (when position = 'top')
     * or in the appropriate position relative to it.
     * Pass null to remove a previously-set navigator.
     */
    public function navigator(?ArchitectNavigatorDefinition $navigator): self
    {
        $clone = clone $this;
        $clone->navigator = $navigator;

        return $clone;
    }

    /**
     * Suppress the automatic ID column that is otherwise prepended to every table.
     *
     * By default TableBuilder prepends a hidden-but-present `id` column so row
     * actions and edit flows always have the primary key available without
     * every definition having to declare it. Call ->hideId() on the rare
     * occasions where you want the column omitted entirely.
     */
    public function hideId(): self
    {
        $clone = clone $this;
        $clone->hideId = true;

        return $clone;
    }

    /**
     * Configure record creation mode and editable columns.
     *
     * @param  string  $mode  'inline' for in-place editing, 'slide-out' for slide-over panel, 'modal' for centered modal panel
     * @param  list<string>|null  $columns  Column keys that are editable (null = all columns with ->type())
     * @param  bool  $openInTab  Dispatch architect:open-record instead of the default create panel.
     * @param  string|null  $tabType  DynamicTabType key (required when $openInTab is true).
     * @param  string|null  $url  Fallback URL when no ModuleTabsManager is present.
     */
    public function create(
        string $mode,
        ?array $columns = null,
        bool $openInTab = false,
        ?string $tabType = null,
        ?string $url = null,
    ): self {
        $clone = clone $this;
        if (! in_array($mode, ['inline', 'slide-out', 'modal'], true)) {
            throw new \InvalidArgumentException("Create mode must be 'inline', 'slide-out', or 'modal', got '{$mode}'");
        }

        if ($mode === 'modal') {
            $clone->formMode = 'modal';
            $clone->createMode = 'slide-out';
        } else {
            $clone->createMode = $mode;
        }
        $clone->createColumns = $columns;
        $clone->creatable = true;
        $clone->createOpenInTab = $openInTab;
        $clone->createTabType = $tabType;
        $clone->createUrl = $url;

        return $clone;
    }

    /**
     * Configure record modification mode and editable columns.
     *
     * @param  string  $mode  'inline' for in-place editing, 'slide-out' for slide-over panel, 'modal' for centered modal panel
     * @param  list<string>|null  $columns  Column keys that are editable (null = all columns with ->type())
     * @param  bool  $openInTab  Dispatch architect:open-record instead of the default edit panel.
     * @param  string|null  $tabType  DynamicTabType key (required when $openInTab is true).
     * @param  string|null  $url  Fallback URL when no ModuleTabsManager is present.
     */
    public function modify(
        string $mode,
        ?array $columns = null,
        bool $openInTab = false,
        ?string $tabType = null,
        ?string $url = null,
    ): self {
        $clone = clone $this;
        if (! in_array($mode, ['inline', 'slide-out', 'modal'], true)) {
            throw new \InvalidArgumentException("Modify mode must be 'inline', 'slide-out', or 'modal', got '{$mode}'");
        }

        if ($mode === 'modal') {
            $clone->formMode = 'modal';
            $clone->modifyMode = 'slide-out';
        } else {
            $clone->modifyMode = $mode;
        }
        $clone->modifyColumns = $columns;
        $clone->modifiable = true;
        $clone->modifyOpenInTab = $openInTab;
        $clone->modifyTabType = $tabType;
        $clone->modifyUrl = $url;

        return $clone;
    }

    /**
     * Enable CSV import for this table.
     *
     * Renders an Import button in the engine toolbar (between Columns
     * and Export). Clicking it opens the ImportWizard Livewire modal.
     *
     * Each parameter maps directly to ImportDefinition; see that class
     * for the precise contract. The column keys listed in $columns must
     * exist on this table — build() throws LogicException otherwise so
     * mistakes surface at boot, not at first user interaction.
     *
     * @param  list<string>  $columns  Write keys eligible for import (declared order is the template order)
     * @param  string  $permission  Permission node required to open the wizard
     * @param  list<string>  $duplicateCheckColumns  Subset of $columns whose combined values define uniqueness
     * @param  array{attempts: int, period: string}  $rateLimitUser
     * @param  array{attempts: int, period: string}  $rateLimitUnion
     */
    public function importable(
        array $columns,
        string $permission,
        int $maxRecordsPerBatch = 500,
        int $maxFileSizeKb = 2048,
        bool $allowPartialImport = false,
        array $duplicateCheckColumns = [],
        array $rateLimitUser = ['attempts' => 3, 'period' => '24 hours'],
        array $rateLimitUnion = ['attempts' => 10, 'period' => '24 hours'],
        int $reversalWindowMinutes = 120,
    ): self {
        $this->importDefinition = new ImportDefinition(
            columns: $columns,
            permission: $permission,
            maxRecordsPerBatch: $maxRecordsPerBatch,
            maxFileSizeKb: $maxFileSizeKb,
            allowPartialImport: $allowPartialImport,
            duplicateCheckColumns: $duplicateCheckColumns,
            rateLimitUser: $rateLimitUser,
            rateLimitUnion: $rateLimitUnion,
            reversalWindowMinutes: $reversalWindowMinutes,
        );

        return $this;
    }

    /**
     * Render an alert banner above the table.
     *
     * Multiple calls stack vertically in declaration order. The engine
     * may also auto-emit defaults (e.g. "This table is view-only" when
     * neither create nor modify is enabled) — call ->suppressAutoAlerts()
     * to opt out of those.
     *
     * @param  string  $type  One of: info, success, warning, danger.
     */
    public function alert(string $type, string $message): self
    {
        if (! in_array($type, ['info', 'success', 'warning', 'danger'], true)) {
            throw new \InvalidArgumentException(
                "Alert type must be one of: info, success, warning, danger. Got '{$type}'"
            );
        }

        $this->alerts[] = ['type' => $type, 'message' => $message];

        return $this;
    }

    /**
     * Hide the search bar in the table toolbar.
     *
     * Useful for embedded tables where search is handled by the Supersearch
     * palette rather than the inline toolbar input.
     */
    public function hideSearchBar(bool $hide = true): self
    {
        $clone = clone $this;
        $clone->hideSearchBar = $hide;

        return $clone;
    }

    /**
     * Suppress auto-detected default alert banners (e.g. the read-only
     * notice rendered when neither create nor modify is enabled).
     */
    public function suppressAutoAlerts(bool $suppress = true): self
    {
        $clone = clone $this;
        $clone->suppressAutoAlerts = $suppress;

        return $clone;
    }

    /**
     * Poll the table at the given interval (seconds) so external changes
     * surface without a manual refresh.
     *
     * @param  int  $seconds  Poll interval; minimum 5s to avoid hammering the server.
     * @param  bool  $countdown  Render a live countdown badge next to the refresh control.
     * @param  string|null  $fingerprintOn  Optional column/key used to avoid full refresh when unchanged.
     */
    public function autoRefresh(int $seconds = 30, bool $countdown = false, ?string $fingerprintOn = null): self
    {
        $clone = clone $this;
        if ($seconds < 5) {
            throw new \InvalidArgumentException(
                "autoRefresh interval must be >= 5 seconds, got {$seconds}"
            );
        }

        if ($fingerprintOn !== null && trim($fingerprintOn) === '') {
            throw new \InvalidArgumentException('autoRefresh fingerprintOn must be a non-empty string when provided.');
        }

        $clone->autoRefreshSeconds = $seconds;
        $clone->autoRefreshCountdown = $countdown;
        $clone->autoRefreshFingerprintOn = $fingerprintOn !== null ? trim($fingerprintOn) : null;

        return $clone;
    }

    /**
     * Keep the create form panel open after a successful save and clear
     * its fields so the operator can immediately enter the next record.
     *
     * Has no effect on the modify form — edits always close the panel.
     */
    public function persistOnCreate(bool $persist = true): self
    {
        $clone = clone $this;
        $clone->persistOnCreate = $persist;

        return $clone;
    }

    /**
     * Configure the records-per-page selector in the pagination footer.
     *
     * @deprecated Use paginate() instead.
     *
     * @param  int  $default  Initial page size (must be in $options).
     * @param  list<int>  $options  Ordered list of selectable sizes.
     *                              Pass an empty array to hide the selector.
     */
    public function perPage(int $default = 25, array $options = [25, 50, 100]): self
    {
        $clone = clone $this;
        if ($options !== [] && ! in_array($default, $options, true)) {
            throw new \InvalidArgumentException(
                "perPage default ({$default}) must be present in the options list."
            );
        }

        $clone->defaultPerPage = $default;
        $clone->perPageOptions = $options;

        return $clone;
    }

    /**
     * Set the table scroll behaviour.
     *
     * - 'page'      — table scrolls with the page; no height constraint.
     * - 'container' — table body has a fixed visible height and scrolls
     *                 independently of the page. Default when paginate() is called.
     * - 'static'    — no scroll; all rows rendered inline. Default when
     *                 paginate() is not called.
     */
    public function scroll(string $mode): self
    {
        if (! in_array($mode, ['page', 'container', 'static'], true)) {
            throw new \InvalidArgumentException(
                "scroll() mode must be 'page', 'container', or 'static'. Got '{$mode}'"
            );
        }

        $clone = clone $this;
        $clone->scrollMode = $mode;

        return $clone;
    }

    /**
     * Configure pagination.
     *
     * When not called the table loads all records without a pagination footer
     * (equivalent to scroll mode 'static'). Only use for small datasets.
     *
     * @param  int  $perPage  Records fetched and shown per page.
     * @param  int|null  $visibleRows  For 'container' scroll: number of body rows
     *                                 visible before the container scrolls. Null = no cap.
     * @param  list<int>  $perPageOptions  Per-page selector options shown in the footer.
     *                                     Empty array (default) hides the selector.
     */
    public function paginate(int $perPage, ?int $visibleRows = null, array $perPageOptions = []): self
    {
        if ($perPageOptions !== [] && ! in_array($perPage, $perPageOptions, true)) {
            throw new \InvalidArgumentException(
                "paginate() default ({$perPage}) must be present in \$perPageOptions."
            );
        }

        $clone = clone $this;
        $clone->isPaginated = true;
        $clone->defaultPerPage = $perPage;
        $clone->perPageOptions = $perPageOptions;
        $clone->visibleRows = $visibleRows;

        // Default scroll to 'container' unless already explicitly set via scroll().
        if ($clone->scrollMode === null) {
            $clone->scrollMode = 'container';
        }

        return $clone;
    }

    /**
     * Control row-level animations: enter transition, save flash, and
     * pending-delete / pending-archive state highlights.
     *
     * Pass false to disable all row animations on this table.
     * Respects the global config('architect.animations') master switch.
     */
    public function animateRows(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->animateRows = $enabled;

        return $clone;
    }

    /**
     * Control form panel transition animations (slide-over and modal).
     *
     * Pass false to use instant open/close with no transition on this table.
     */
    public function animatePanels(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->animatePanels = $enabled;

        return $clone;
    }

    /**
     * Control whether validation error banners shake when they appear
     * or re-trigger (e.g. repeated failed submits).
     *
     * Pass false to render error banners without the shake animation.
     */
    public function animateErrors(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->animateErrors = $enabled;

        return $clone;
    }

    /**
     * Control the button press micro-interaction (scale-down on :active)
     * for all buttons rendered within this table component.
     *
     * Pass false to disable the press effect on this table's buttons.
     */
    public function animateButtons(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->animateButtons = $enabled;

        return $clone;
    }

    public function build(): ArchitectTableDefinition
    {
        if ($this->dataModelClass === null) {
            throw new \LogicException('TableBuilder: model() is required');
        }

        if ($this->permissions === null) {
            throw new \LogicException('TableBuilder: permissions() is required');
        }

        if ($this->formMode === 'wizard') {
            if ($this->creatable && $this->customCreateForm === null) {
                throw new \LogicException("TableBuilder: formMode('wizard') requires customForm(for: 'create', ...) when create flow is enabled.");
            }

            if ($this->modifiable && $this->customModifyForm === null) {
                throw new \LogicException("TableBuilder: formMode('wizard') requires customForm(for: 'modify', ...) when modify flow is enabled.");
            }
        }

        if ($this->customCreateForm !== null) {
            $this->assertCustomFormDefinitionCompatibility($this->customCreateForm, 'create');
        }

        if ($this->customModifyForm !== null) {
            $this->assertCustomFormDefinitionCompatibility($this->customModifyForm, 'modify');
        }

        /** @var class-string<ArchitectDataModel> $dataModelClass */
        $dataModelClass = $this->dataModelClass;
        /** @var PermissionMap $permissions */
        $permissions = $this->permissions;

        // Collect filters from columns (inline) and merge with standalone filters
        $allFilters = $this->filters;
        foreach ($this->columns as $column) {
            $filter = $column->getFilter();
            if ($filter !== null) {
                $allFilters[] = $filter;
            }
        }

        $this->assertUniqueFilterNames($allFilters);

        // Auto-add row actions for clonable and auditable features
        $rowActions = $this->rowActions;

        // Auto-add clone row action if clonable is enabled
        if ($this->clonable) {
            $rowActions[] = RowAction::make('clone')
                ->label('Clone Record')
                ->icon('fas fa-copy')
                ->permission($permissions->create)
                ->color('info');
        }

        // Auto-add audit trail row action if auditable is enabled
        if ($this->auditable) {
            $rowActions[] = RowAction::make('audit')
                ->label('View Audit Trail')
                ->icon('fas fa-history')
                ->permission($permissions->modify)
                ->newWindow();
        }

        // Auto-add view row action if viewable is enabled
        if ($this->viewable) {
            $viewAction = RowAction::make('view')
                ->label('View Details')
                ->icon('fas fa-external-link')
                ->permission($permissions->read)
                ->color('info');

            if ($this->viewUrl !== null) {
                $viewAction->url($this->viewUrl);
            }

            $rowActions[] = $viewAction;
        }

        // Prepend implicit ID column unless suppressed or already explicitly defined.
        $columns = $this->columns;
        $hasIdColumn = array_filter($columns, fn (Column $c): bool => $c->getKey() === 'id') !== [];
        if (! $this->hideId && ! $hasIdColumn) {
            array_unshift($columns, Column::make('id')->label('ID')->sortable('asc'));
        }

        // Validate import definition column references against the
        // table's actual columns. Doing this here gives a boot-time
        // failure for typos rather than a 500 the first time an admin
        // clicks the Import button.
        if ($this->importDefinition !== null) {
            $columnsKeyed = [];
            foreach ($columns as $col) {
                $columnsKeyed[$col->getKey()] = $col;
            }
            // Throws LogicException on unknown column key.
            $this->importDefinition->getImportColumns($columnsKeyed);
        }

        return new ArchitectTableDefinition(
            title: $this->title,
            pageTitle: $this->pageTitle,
            breadcrumbs: $this->breadcrumbs,
            dataModelClass: $dataModelClass,
            permissions: $permissions,
            creatable: $this->creatable,
            modifiable: $this->modifiable,
            createOpenInTab: $this->createOpenInTab,
            createTabType: $this->createTabType,
            createUrl: $this->createUrl,
            modifyOpenInTab: $this->modifyOpenInTab,
            modifyTabType: $this->modifyTabType,
            modifyUrl: $this->modifyUrl,
            formMode: $this->formMode,
            filterPersistence: $this->filterPersistence,
            filterBookmarkFilters: $this->filterBookmarkFilters,
            columns: $columns,
            fields: $this->fields,
            filters: $allFilters,
            defaultFilters: $this->defaultFilters,
            archivable: $this->archivable,
            requiresDeletionReason: $this->requiresDeletionReason,
            allowUnarchive: $this->allowUnarchive,
            allowDelete: $this->allowDelete,
            deletable: $this->deletable,
            deletableReasonRequired: $this->deletableReasonRequired,
            deletablePhraseRequired: $this->deletablePhraseRequired,
            deletablePhrase: $this->deletablePhrase,
            archivablePhraseRequired: $this->archivablePhraseRequired,
            archivablePhrase: $this->archivablePhrase,
            animateRows: $this->animateRows,
            animatePanels: $this->animatePanels,
            animateErrors: $this->animateErrors,
            animateButtons: $this->animateButtons,
            selectableRows: $this->selectableRows,
            bulkActions: $this->bulkActions,
            exportFormats: $this->exportFormats,
            clonable: $this->clonable,
            clonableExcept: $this->clonableExcept,
            auditable: $this->auditable,
            documentationUrl: $this->documentationUrl,
            rowActions: $rowActions,
            customRowActions: $this->customRowActions,
            headerActions: $this->headerActions,
            createMode: $this->createMode,
            createColumns: $this->createColumns,
            modifyMode: $this->modifyMode,
            modifyColumns: $this->modifyColumns,
            customCreateForm: $this->customCreateForm,
            customModifyForm: $this->customModifyForm,
            importDefinition: $this->importDefinition,
            alerts: $this->alerts,
            suppressAutoAlerts: $this->suppressAutoAlerts,
            hideSearchBar: $this->hideSearchBar,
            autoRefreshSeconds: $this->autoRefreshSeconds,
            autoRefreshCountdown: $this->autoRefreshCountdown,
            autoRefreshFingerprintOn: $this->autoRefreshFingerprintOn,
            persistOnCreate: $this->persistOnCreate,
            perPageOptions: $this->perPageOptions,
            defaultPerPage: $this->defaultPerPage,
            navigator: $this->navigator,
            headerSection: null,
            scrollMode: $this->scrollMode ?? 'static',
            visibleRows: $this->visibleRows,
            isPaginated: $this->isPaginated,
        );
    }

    private function assertCustomFormDefinitionCompatibility(CustomForm $customForm, string $for): void
    {
        $definitionClass = $customForm->definitionClass;
        $definition = $definitionClass::definition();

        if (! ($definition instanceof ArchitectFormDefinition || $definition instanceof ArchitectWizardDefinition)) {
            throw new \LogicException(
                "TableBuilder: customForm(for: '{$for}') definition [{$definitionClass}] must return ArchitectFormDefinition or ArchitectWizardDefinition from static definition()."
            );
        }

        if ($this->formMode === 'wizard' && ! ($definition instanceof ArchitectWizardDefinition)) {
            throw new \LogicException(
                "TableBuilder: formMode('wizard') requires customForm(for: '{$for}') definition [{$definitionClass}] to return ArchitectWizardDefinition."
            );
        }
    }

    /**
     * @param  list<ArchitectFilter>  $filters
     */
    private function assertUniqueFilterNames(array $filters): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($filters as $filter) {
            $name = $filter->name();

            if (isset($seen[$name])) {
                $duplicates[$name] = true;

                continue;
            }

            $seen[$name] = true;
        }

        if ($duplicates === []) {
            return;
        }

        throw new \InvalidArgumentException(
            'TableBuilder: duplicate filter names detected: '.implode(', ', array_keys($duplicates)).'. Filter names must be unique.'
        );
    }
}
