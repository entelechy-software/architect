<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Navigator\ArchitectNavigatorDefinition;
use Entelechy\Architect\Stats\ArchitectStatDefinition;
use Entelechy\Architect\Table\Actions\HeaderAction;
use Entelechy\Architect\Table\Actions\RowAction;
use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\ArchitectField;
use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Entelechy\Architect\Table\Contracts\ArchitectRowAction;
use Entelechy\Architect\Table\Import\ImportDefinition;

/**
 * Immutable result of the TableBuilder fluent builder.
 *
 * Once constructed, this object is the single source of truth that the
 * Livewire engine, the form panel, the controller endpoints, and the
 * exporter all read from. No setter methods exist — every property is
 * readonly. Mutating the table requires re-running the builder.
 */
final class ArchitectTableDefinition
{
    /**
     * O(1) lookup indexes built once in the constructor so hot paths
     * (column()/field()/filtersByName()) avoid repeated linear scans
     * across the public ordered arrays. Private + non-readonly because
     * they're populated after the readonly ctor parameters are bound.
     *
     * @var array<string, Column>
     */
    private array $columnsByKey;

    /** @var array<string, ArchitectField> */
    private array $fieldsByName;

    /** @var array<string, ArchitectFilter> */
    private array $filtersByName;

    /**
     * @param  class-string<ArchitectDataModel>  $dataModelClass
     * @param  list<Column>  $columns
     * @param  list<ArchitectField>  $fields
     * @param  list<ArchitectFilter>  $filters
     * @param  array<string, mixed>  $defaultFilters
     * @param  list<ArchitectBulkAction>  $bulkActions
     * @param  list<string>  $clonableExcept
     * @param  list<string>  $exportFormats  e.g., ['csv', 'excel', 'pdf', 'html']
     * @param  list<RowAction>  $rowActions
     * @param  list<HeaderAction>  $headerActions
     * @param  list<ArchitectRowAction>  $customRowActions
     * @param  array<int, array{title: string, url?: string|false}>  $breadcrumbs  Breadcrumb items with title and optional url
     * @param  string|null  $createMode  'inline' or 'slide-out'
     * @param  list<string>|null  $createColumns  Column keys editable in create mode (null = all)
     * @param  string|null  $modifyMode  'inline' or 'slide-out'
     * @param  list<string>|null  $modifyColumns  Column keys editable in modify mode (null = all)
     */
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $pageTitle,
        public array $breadcrumbs,
        public readonly string $dataModelClass,
        public readonly PermissionMap $permissions,
        /** Whether the New button / create flow is enabled. */
        public readonly bool $creatable,
        /** Whether the Edit button / modify flow is enabled. */
        public readonly bool $modifiable,
        public readonly string $formMode,
        public readonly bool $filterPersistence,
        public readonly bool $filterBookmarkFilters,
        public readonly array $columns,
        public readonly array $fields,
        public readonly array $filters,
        public readonly array $defaultFilters,
        public readonly bool $archivable,
        public readonly bool $requiresDeletionReason,
        public readonly bool $allowUnarchive,
        public readonly bool $allowDelete,
        public readonly bool $deletable,
        public readonly bool $deletableReasonRequired,
        public readonly bool $selectableRows,
        public readonly array $bulkActions,
        public readonly array $exportFormats,
        public readonly bool $clonable,
        public readonly array $clonableExcept,
        public readonly bool $auditable,
        public readonly ?string $documentationUrl,
        public readonly array $rowActions,
        public readonly array $headerActions,
        public readonly ?string $createMode = null,
        public readonly ?array $createColumns = null,
        public readonly ?string $modifyMode = null,
        public readonly ?array $modifyColumns = null,
        /** Dispatch architect:open-record instead of the default create panel. */
        public readonly bool $createOpenInTab = false,
        /** DynamicTabType key for the create flow (used when $createOpenInTab is true). */
        public readonly ?string $createTabType = null,
        /** Fallback URL when no ModuleTabsManager is present for the create flow. */
        public readonly ?string $createUrl = null,
        /** Dispatch architect:open-record instead of the default edit panel. */
        public readonly bool $modifyOpenInTab = false,
        /** DynamicTabType key for the modify flow (used when $modifyOpenInTab is true). */
        public readonly ?string $modifyTabType = null,
        /** Fallback URL when no ModuleTabsManager is present for the modify flow. */
        public readonly ?string $modifyUrl = null,
        /**
         * CSV import configuration. Null = no import button rendered.
         */
        public readonly ?ImportDefinition $importDefinition = null,
        /**
         * Custom alert banners rendered above the table.
         *
         * @var list<array{type: string, message: string}>
         */
        public readonly array $alerts = [],
        /** Suppress auto-detected default banners (e.g. read-only notice). */
        public readonly bool $suppressAutoAlerts = false,
        /** Hide the inline search bar in the toolbar. */
        public readonly bool $hideSearchBar = false,
        /** Auto-refresh interval in seconds; null disables polling. */
        public readonly ?int $autoRefreshSeconds = null,
        /** Render a live countdown badge next to the refresh button. */
        public readonly bool $autoRefreshCountdown = false,
        /** Optional column/key used for auto-refresh fingerprint checks. */
        public readonly ?string $autoRefreshFingerprintOn = null,
        /** Keep the create form open and clear fields after a successful save. */
        public readonly bool $persistOnCreate = false,
        /**
         * Ordered list of selectable page-size options rendered in the
         * pagination footer. An empty array hides the selector.
         *
         * @var list<int>
         */
        public readonly array $perPageOptions = [25, 50, 100],
        /** Initial page size; must be present in $perPageOptions (or any value when options = []). */
        public readonly int $defaultPerPage = 25,
        /**
         * Optional navigator rendered adjacent to the table (above by default).
         * Null = no navigator rendered.
         */
        public readonly ?ArchitectNavigatorDefinition $navigator = null,
        /**
         * Optional stat section rendered above the table (e.g. a MetricGrid).
         * Null = no header section. Populate via TableBuilder::header() when implemented.
         */
        public readonly ?ArchitectStatDefinition $headerSection = null,
        /** Whether deletion requires the user to type a confirmation phrase. */
        public readonly bool $deletablePhraseRequired = false,
        /** Fixed phrase to type when deleting; null = use the record's name/title. */
        public readonly ?string $deletablePhrase = null,
        /** Whether archiving requires the user to type a confirmation phrase. */
        public readonly bool $archivablePhraseRequired = false,
        /** Fixed phrase to type when archiving; null = use the record's name/title. */
        public readonly ?string $archivablePhrase = null,
        /** Whether to animate table rows (enter, state changes, save flash). Default true. */
        public readonly bool $animateRows = true,
        /** Whether to animate form panel slide-over / modal transitions. Default true. */
        public readonly bool $animatePanels = true,
        /** Whether to animate validation error messages with a shake effect. Default true. */
        public readonly bool $animateErrors = true,
        /** Whether to apply a press micro-interaction to buttons in this table. Default true. */
        public readonly bool $animateButtons = true,
        /** Scroll behaviour applied to the table-body wrapper. */
        public readonly string $scrollMode = 'static',
        /** For 'container' scroll: visible body-row count before overflow scrolling begins. Null = no height cap. */
        public readonly ?int $visibleRows = null,
        /** Whether paginate() was called. False → all records fetched in one pass; no pagination footer shown. */
        public readonly bool $isPaginated = false,
        /**
         * One-off, class-based row actions registered via customRowAction().
         * Unlike $rowActions (presentation-only), each of these runs real
         * PHP against the row via handle() and returns a success/message
         * result surfaced as an inline banner.
         *
         * @var list<ArchitectRowAction>
         */
        public readonly array $customRowActions = [],
    ) {
        if (! in_array($formMode, ['slide-over', 'page', 'modal'], true)) {
            throw new \InvalidArgumentException("formMode must be 'slide-over', 'page', or 'modal', got '{$formMode}'");
        }

        if (! in_array($scrollMode, ['page', 'container', 'static'], true)) {
            throw new \InvalidArgumentException(
                "scrollMode must be 'page', 'container', or 'static', got '{$scrollMode}'"
            );
        }

        // Build O(1) lookup indexes for the engine's hot paths
        // (column()/field()/filtersByName()). Done once here so render(),
        // toggleColumn(), bulkAction() etc. don't pay an O(n) linear scan
        // per call across the public ordered arrays.
        $this->columnsByKey = [];
        foreach ($this->columns as $column) {
            $this->columnsByKey[$column->getKey()] = $column;
        }

        $this->fieldsByName = [];
        foreach ($this->fields as $field) {
            $this->fieldsByName[$field->name()] = $field;
        }

        $this->filtersByName = [];
        foreach ($this->filters as $filter) {
            $this->filtersByName[$filter->name()] = $filter;
        }
    }

    /**
     * Find a field by its name. Returns null if not in this definition.
     */
    public function field(string $name): ?ArchitectField
    {
        return $this->fieldsByName[$name] ?? null;
    }

    /**
     * Find a column by its key.
     */
    public function column(string $key): ?Column
    {
        return $this->columnsByKey[$key] ?? null;
    }

    /**
     * Filters keyed by name. Used by the engine to populate
     * QueryContext::filterDefinitions without rebuilding the map per
     * request.
     *
     * @return array<string, ArchitectFilter>
     */
    public function filtersByName(): array
    {
        return $this->filtersByName;
    }

    /**
     * Get editable columns for create mode.
     *
     * @return list<Column>
     */
    public function getCreateColumns(): array
    {
        if ($this->createMode === null) {
            return [];
        }

        // If specific columns specified, return only those
        if ($this->createColumns !== null) {
            return array_values(array_filter($this->columns, function (Column $col) {
                return in_array($col->getKey(), $this->createColumns, true) && ! $col->isHiddenOnForm();
            }));
        }

        // Otherwise return all columns with a type set
        return array_values(array_filter($this->columns, function (Column $col) {
            return $col->hasType() && ! $col->isHiddenOnForm();
        }));
    }

    /**
     * Get editable columns for modify mode.
     *
     * @return list<Column>
     */
    public function getModifyColumns(): array
    {
        if ($this->modifyMode === null) {
            return [];
        }

        // If specific columns specified, return only those
        if ($this->modifyColumns !== null) {
            return array_values(array_filter($this->columns, function (Column $col) {
                return in_array($col->getKey(), $this->modifyColumns, true) && ! $col->isHiddenOnForm();
            }));
        }

        // Otherwise return all columns with a type set
        return array_values(array_filter($this->columns, function (Column $col) {
            return $col->hasType() && ! $col->isHiddenOnForm();
        }));
    }

    /**
     * Compute the set of editKeys that participate in a cross-column
     * relationship and therefore must open in row-mode (the whole row
     * is editable as a unit so dependencies stay consistent).
     *
     * Two relationship sources are detected automatically:
     *
     *   1. Cascade — column A declares `->cascadeFrom('B')`. Both A and
     *      B become row-dependent (changing B must clear A; A's options
     *      depend on B's value).
     *
     *   2. Cross-field validation rules — any rule that references
     *      another field by name. Recognised tokens: after, after_or_equal,
     *      before, before_or_equal, same, different, gt, gte, lt, lte,
     *      required_with, required_without, required_if, required_unless.
     *
     * Returns a map keyed by editKey (e.g. ['committee_position_id' => true,
     * 'activity_id' => true, 'end_date' => true, 'start_date' => true])
     * so blade templates can do O(1) `isset($map[$key])` lookups.
     *
     * @return array<string, true>
     */
    public function getRowDependentEditKeys(): array
    {
        // Rule tokens that reference another field by name. The field
        // name follows the colon: `after:start_date`, `same:password`.
        static $crossFieldRules = [
            'after', 'after_or_equal', 'before', 'before_or_equal',
            'same', 'different', 'gt', 'gte', 'lt', 'lte',
            'required_with', 'required_with_all',
            'required_without', 'required_without_all',
            'required_if', 'required_unless',
        ];

        $deps = [];

        foreach ($this->columns as $column) {
            if (! $column->isEditable()) {
                continue;
            }

            $editKey = $column->getEditKey();

            // (1) Cascade: parent and child are mutually dependent.
            $parentKey = $column->getCascadeFrom();
            if ($parentKey !== null) {
                $deps[$editKey] = true;
                $deps[$parentKey] = true;
            }

            // (2) Cross-field validation rules.
            $rules = $column->getRules();
            if ($rules === null || $rules === '') {
                continue;
            }

            foreach (explode('|', $rules) as $rule) {
                $colon = strpos($rule, ':');
                if ($colon === false) {
                    continue;
                }
                $name = substr($rule, 0, $colon);
                if (! in_array($name, $crossFieldRules, true)) {
                    continue;
                }
                // Args may be comma-separated; each arg might be a
                // field name (or a literal). We can't reliably distinguish
                // literals from field names in `required_if:status,active`,
                // but treating both args as potential dependencies is safe
                // — if no column with that key exists, the entry is harmless.
                $args = explode(',', substr($rule, $colon + 1));
                $deps[$editKey] = true;
                foreach ($args as $arg) {
                    $arg = trim($arg);
                    if ($arg === '') {
                        continue;
                    }
                    if ($this->columnByEditKey($arg) !== null) {
                        $deps[$arg] = true;
                    }
                }
            }
        }

        return $deps;
    }

    /**
     * Find a column whose editKey (== editAs ?? key) matches.
     */
    private function columnByEditKey(string $editKey): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->getEditKey() === $editKey) {
                return $column;
            }
        }

        return null;
    }
}
