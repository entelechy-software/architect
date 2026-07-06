<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Entelechy\Architect\Table\Contracts\HasVisibleWhen;

/**
 * A single column in the index table.
 *
 * Configured via the chainable static factory:
 *   Column::make('name')->label('Name')->searchable()->sortable()->badge()
 *
 * Columns are render-time descriptors only: they do not mutate the query.
 * Sorting and searching toggles inform the engine which columns to expose
 * for client-side controls — actually applying sort/search is the
 * responsibility of the data model.
 */
final class Column implements HasVisibleWhen
{
    private string $label;

    private bool $searchable = false;

    private bool $sortable = false;

    private ?string $defaultSortDirection = null;

    private ?int $defaultSortPriority = null;

    private bool $badge = false;

    /** @var array<string, string> */
    private array $colors = [];

    /**
     * Optional badge profile map keyed by rendered value.
     *
     * Each profile supports:
     *   - color (string)
     *   - icon (string|null)
     *   - position ('left'|'right')
     *
     * @var array<string, array{color?: string, colors?: string, icon?: string|null, position?: string}>
     */
    private array $badgeProfiles = [];

    /** @var string|null Permission node required to see this column. */
    private ?string $visibleTo = null;

    /** @var string|null Permission node required to see this column in create form mode. */
    private ?string $createVisibleTo = null;

    /** @var string|null Permission node required to see this column in modify form mode. */
    private ?string $modifyVisibleTo = null;

    /** @var string|null Permission node required to edit this column in create form mode. */
    private ?string $createEditableTo = null;

    /** @var string|null Permission node required to edit this column in modify form mode. */
    private ?string $modifyEditableTo = null;

    private ?ArchitectFilter $filter = null;

    /** @var string|null Type for editing (text, select, checkbox, date, etc.) */
    private ?string $type = null;

    /** @var string|null Different key to edit (e.g., display 'member_name' but edit 'member_id') */
    private ?string $editAs = null;

    /**
     * Controls how the CSV importer resolves user-typed values into
     * the FK referenced by `editAs`.
     *
     * Values:
     *   - null         (default) — infer the lookup table from any
     *                  `exists:table,col` rule and fuzzy-match against
     *                  its `name` column.
     *   - 'id'         — disable resolution; the cell must already be
     *                  the integer FK.
     *   - other string — name of the candidate column to match against
     *                  (e.g. 'email', 'code') in the same table.
     */
    private ?string $importResolveBy = null;

    /** @var string|null Validation rules for this column */
    private ?string $rules = null;

    /** @var array<mixed>|null Options for select/multiselect types */
    private ?array $options = null;

    /** @var string|null API endpoint for dynamic options (lookup) */
    private ?string $source = null;

    /** @var string Storage disk used by type('upload') columns. */
    private string $disk = 'public';

    /** @var string Storage directory (within $disk) used by type('upload') columns. */
    private string $directory = 'uploads';

    /** @var string|null Model class for direct model binding */
    private ?string $model = null;

    /** @var string|null Parent column key for cascading selects */
    private ?string $cascadeFrom = null;

    /**
     * Column name on the child lookup table to filter by when a parent value is provided.
     * null  = use $cascadeFrom key as the column name (default behaviour).
     * false = frontend cascade reset only; no WHERE clause on the lookup query.
     */
    private string|false|null $cascadeChildColumn = null;

    /**
     * Search mode used by the auto-generated lookup lookup endpoint.
     *
     *   - 'contains'    (default) emits LIKE '%term%' against searchable columns.
     *   - 'starts_with' emits LIKE 'term%'. Index-friendly; recommended
     *                   when the searched columns carry a B-tree index
     *                   and prefix matching is acceptable UX (typeahead
     *                   on names/codes/slugs).
     */
    private string $lookupSearchMode = 'contains';

    /**
     * Optional callable that formats the human-readable label from a
     * lookup row. Receives a stdClass row from the auto-generated
     * lookup endpoint and returns a string.
     *
     * Default (null): uses the `importResolveBy` column value verbatim.
     *
     * @var (callable(object): string)|null
     */
    private $labelUsing = null;

    /**
     * Columns to match when the user types into a lookup input on
     * the auto-generated lookup endpoint.
     *
     * Default (null): matches against `importResolveBy` (or 'name').
     *
     * @var list<string>|null
     */
    private ?array $searchColumns = null;

    /**
     * When true, the cell renders an interactive toggle switch that
     * immediately updates the underlying boolean column via Livewire
     * without opening a form panel.
     */
    private bool $toggleable = false;

    /** @var string|null Permission node required to toggle; defaults to the table's modify node. */
    private ?string $togglePermission = null;

    /** Label shown on the 'on' (value=1) side of the toggle. */
    private string $toggleOnLabel = 'On';

    /** Label shown on the 'off' (value=0) side of the toggle. */
    private string $toggleOffLabel = 'Off';

    /**
     * Hint value shown to the user as guidance.
     *
     * Two roles:
     *   - HTML `placeholder` attribute on text/date/textarea form inputs
     *     (create / inline edit panels).
     *   - Example row in the auto-generated CSV import template.
     *
     * Empty string when not set.
     */
    private string $placeholder = '';

    /**
     * Inline-edit mode override for this column.
     *
     *   null  (default) — engine auto-detects: row-mode if the column
     *                     participates in a cascade or cross-field rule
     *                     dependency; otherwise cell-mode on direct click.
     *   true            — force cell-mode on direct click. Column still
     *                     participates normally if a row-edit is already
     *                     open.
     *   false           — never editable inline (full opt-out).
     */
    private ?bool $modifyInline = null;

    /**
     * Conditional visibility rules for the create/modify form.
     *
     * Each rule references another column by its editKey. Multiple rules
     * combine with AND. Compiled to an Alpine x-show expression by
     * VisibleWhenAlpineCompiler.
     *
     * @var list<array{field: string, op: string, value: mixed}>
     */
    private array $visibleWhen = [];

    /**
     * Lookup: when true, the user can type a value not in the dropdown
     * list and submit it as-is (Lookup `tags: true`).
     */
    private bool $allowCustom = false;

    /**
     * Lookup: when set, fetches results immediately on dropdown open
     * without requiring the user to type. 0 = no limit; positive int
     * = fetch only the latest N rows.
     */
    private ?int $autoLoad = null;

    /**
     * Lookup: name of another form field whose current value is sent
     * as `?extra=` on every AJAX call. Unlike cascadeFrom, this never
     * clears the child on parent change — it merely passes context to
     * the search endpoint.
     */
    private ?string $extraDataFrom = null;

    /**
     * Lookup: column on the lookup table used to group results into
     * Lookup <optgroup>s. Null = no grouping.
     */
    private ?string $optGroupsColumn = null;

    /** Never show this column in the create/edit form, even if it has a type. */
    private bool $hideOnForm = false;

    /** Never show this column in the index table rows. */
    private bool $hideOnIndex = false;

    private function __construct(private readonly string $key)
    {
        $this->label = ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function searchable(bool $searchable = true): self
    {
        $clone = clone $this;
        $clone->searchable = $searchable;

        return $clone;
    }

    /**
     * Mark this column as sortable, optionally with a default sort direction and priority.
     *
     * @param  string|bool  $defaultDirection  'asc', 'desc', or true for sortable without default
     * @param  int|null  $priority  Sort priority (1 = highest) when multiple columns have defaults
     */
    public function sortable(string|bool $defaultDirection = true, ?int $priority = null): self
    {
        $clone = clone $this;
        if ($defaultDirection === true || $defaultDirection === false) {
            $clone->sortable = $defaultDirection;
            $clone->defaultSortDirection = null;
            $clone->defaultSortPriority = null;
        } else {
            if (! in_array($defaultDirection, ['asc', 'desc'], true)) {
                throw new \InvalidArgumentException("Sort direction must be 'asc' or 'desc', got '{$defaultDirection}'");
            }
            $clone->sortable = true;
            $clone->defaultSortDirection = $defaultDirection;
            $clone->defaultSortPriority = $priority;
        }

        return $clone;
    }

    /**
     * Enable badge rendering, optionally with per-value profile metadata.
     *
     * Example:
     *   ->badge([
     *      'Verified' => ['color' => 'success', 'icon' => 'fas fa-check', 'position' => 'left'],
     *   ])
     *
     * Backward compatibility:
     *   - bool keeps prior enable/disable behavior.
     *   - profile key 'colors' is accepted as an alias of 'color'.
     *
     * @param  bool|array<string, array{color?: string, colors?: string, icon?: string|null, position?: string}>  $badge
     */
    public function badge(bool|array $badge = true): self
    {
        $clone = clone $this;
        if (is_bool($badge)) {
            $clone->badge = $badge;

            return $clone;
        }

        $clone->badge = true;
        $clone->badgeProfiles = $this->normalizeBadgeProfiles($badge);

        // Preserve legacy color lookups by hydrating colors[] from profile map.
        foreach ($clone->badgeProfiles as $value => $profile) {
            $color = $profile['color'] ?? ($profile['colors'] ?? null);
            if (is_string($color) && $color !== '') {
                $clone->colors[$value] = $color;
            }
        }

        return $clone;
    }

    /**
     * @param  array<string, string>  $colors  Map of value => Tabler badge colour name (e.g. 'success', 'danger').
     */
    public function colors(array $colors): self
    {
        $clone = clone $this;
        $clone->colors = $colors;
        $clone->badge = true;

        // Keep badge profiles in sync when only colors() is used.
        foreach ($colors as $value => $color) {
            $clone->badgeProfiles[(string) $value]['color'] = (string) $color;
        }

        return $clone;
    }

    public function visibleTo(string $node): self
    {
        $clone = clone $this;
        $clone->visibleTo = $node;

        return $clone;
    }

    /** Permission node required to see this column in create form mode. */
    public function createVisibleTo(string $node): self
    {
        $clone = clone $this;
        $clone->createVisibleTo = $node;

        return $clone;
    }

    /** Permission node required to see this column in modify form mode. */
    public function modifyVisibleTo(string $node): self
    {
        $clone = clone $this;
        $clone->modifyVisibleTo = $node;

        return $clone;
    }

    /** Permission node required to edit this column in create form mode. */
    public function createEditableTo(string $node): self
    {
        $clone = clone $this;
        $clone->createEditableTo = $node;

        return $clone;
    }

    /** Permission node required to edit this column in modify form mode. */
    public function modifyEditableTo(string $node): self
    {
        $clone = clone $this;
        $clone->modifyEditableTo = $node;

        return $clone;
    }

    /**
     * Attach a filter to this column. The filter will be collected by
     * the TableBuilder builder alongside standalone filters.
     */
    public function filter(ArchitectFilter $filter): self
    {
        $clone = clone $this;
        $clone->filter = $filter;

        return $clone;
    }

    /**
     * Set the input type for creating/editing this column.
     *
     * Supported types: text, textarea, number, date, datetime, time, select, lookup,
     * multiselect, checkbox, wysiwyg, upload, color, icon, hidden, etc.
     */
    public function type(string $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    /**
     * Specify a different field key for editing (e.g., display 'member_name' but edit 'member_id').
     *
     * The optional second argument controls CSV-import resolution:
     *   - null  (default) — fuzzy-match cell value against the lookup table's
     *                       `name` column (table inferred from `exists:` rule).
     *   - 'id'            — no resolution; CSV must contain integer FK.
     *   - other string    — fuzzy-match against this column instead of `name`.
     */
    public function editAs(string $key, ?string $resolveBy = null): self
    {
        $clone = clone $this;
        $clone->editAs = $key;
        $clone->importResolveBy = $resolveBy;

        return $clone;
    }

    /**
     * Laravel validation rules for this column.
     */
    public function rules(string $rules): self
    {
        $clone = clone $this;
        $clone->rules = $rules;

        return $clone;
    }

    /**
     * Static options for select/multiselect types.
     *
     * @param  array<mixed>  $options
     */
    public function options(array $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    /**
     * Override the lookup URL for lookup / multiselect columns.
     *
     * Under normal circumstances you DO NOT need to call this. When a
     * lookup column declares `->editAs(...)` and an `exists:table,col`
     * rule, the framework auto-generates a signed, generic lookup
     * endpoint that handles `?q=` search, cascading, and label
     * formatting.
     *
     * Use `->source()` ONLY for purely external API calls (e.g. a
     * third-party autocomplete service) where the framework generic
     * endpoint cannot apply.
     */
    public function source(string $url): self
    {
        $clone = clone $this;
        $clone->source = $url;

        return $clone;
    }

    /**
     * Storage disk used to persist an uploaded file for type('upload') columns.
     * Defaults to 'public'.
     */
    public function disk(string $disk): self
    {
        $clone = clone $this;
        $clone->disk = $disk;

        return $clone;
    }

    /**
     * Storage directory (within the configured disk) used to persist an
     * uploaded file for type('upload') columns. Defaults to 'uploads'.
     */
    public function directory(string $directory): self
    {
        $clone = clone $this;
        $clone->directory = trim($directory, '/');

        return $clone;
    }

    /**
     * Conditional visibility within the create/modify form.
     *
     * Multiple calls combine with AND. The referenced field is the
     * editKey of another column — i.e. the column's own key unless
     * the column declares ->editAs(), in which case it's the FK key.
     *
     * Supported operators: equals (=), not (!=), in, filled, empty,
     * truthy, falsy.
     *
     * Example:
     *   Column::make('rejection_reason')->type('textarea')
     *       ->visibleWhen('status_id', 'equals', 3)
     */
    public function visibleWhen(string $field, string $op, mixed $value): self
    {
        $this->visibleWhen[] = ['field' => $field, 'op' => $op, 'value' => $value];

        return $this;
    }

    /**
     * Lookup: allow the user to type a value not in the dropdown
     * (Lookup `tags: true`). The typed value is submitted verbatim.
     */
    public function allowCustom(bool $allow = true): self
    {
        $clone = clone $this;
        $clone->allowCustom = $allow;

        return $clone;
    }

    /**
     * Lookup: fetch results immediately when the dropdown opens — no
     * typing required.
     *
     * @param  int  $limit  0 (default) = fetch all; positive int = fetch
     *                      latest N rows. Useful for short reference lists.
     */
    public function autoLoad(int $limit = 0): self
    {
        $clone = clone $this;
        if ($limit < 0) {
            throw new \InvalidArgumentException(
                "autoLoad limit must be >= 0, got {$limit}"
            );
        }

        $clone->autoLoad = $limit;

        return $clone;
    }

    /**
     * Lookup: pass the current value of another form field as `?extra=`
     * on every AJAX call. Unlike cascadeFrom this does NOT clear the
     * child field when the source field changes — it just adds context
     * to every search query.
     *
     * Most useful with ->source() (custom endpoints) since the
     * auto-generated lookup endpoint only consumes `?extra=` when
     * combined with ->optGroups().
     */
    public function extraDataFrom(string $fieldKey): self
    {
        $clone = clone $this;
        $clone->extraDataFrom = $fieldKey;

        return $clone;
    }

    /**
     * Lookup: group lookup results into <optgroup>s by the given
     * column on the lookup table. Renders as nested results in the
     * dropdown.
     */
    public function optGroups(string $groupColumn): self
    {
        $clone = clone $this;
        $clone->optGroupsColumn = $groupColumn;

        return $clone;
    }

    /**
     * Provide a callback to format the human-readable label returned
     * by the auto-generated lookup endpoint.
     *
     * @param  callable(object): string  $callback
     */
    public function labelUsing(callable $callback): self
    {
        $clone = clone $this;
        $clone->labelUsing = $callback;

        return $clone;
    }

    /**
     * Set the DB columns searched when the user types in a lookup
     * input on the auto-generated lookup endpoint.
     *
     * Useful for composite labels (e.g. members: first_name,
     * last_name, student_id) where a single column doesn't cover
     * all the values a user might type.
     *
     * @param  list<string>  $columns
     */
    public function searchColumns(array $columns): self
    {
        $clone = clone $this;
        $clone->searchColumns = $columns;

        return $clone;
    }

    /**
     * Model class for direct model binding (e.g., App\Models\Activity::class).
     */
    public function model(string $modelClass): self
    {
        $clone = clone $this;
        $clone->model = $modelClass;

        return $clone;
    }

    /**
     * Parent column key for cascading selects (e.g., 'position_id' cascades from 'activity_id').
     *
     * The optional $childColumn controls the WHERE clause applied by the lookup endpoint:
     *   - null (default): filter the child lookup table by a column named after $parentKey.
     *   - string: filter by this explicit column name on the child table.
     *   - false: frontend cascade reset only — no WHERE clause on the lookup query.
     *            Use this when the child lookup table has no FK to the parent
     *            (e.g. a global positions table shared across all activities).
     */
    public function cascadeFrom(string $parentKey, string|false|null $childColumn = null): self
    {
        $clone = clone $this;
        $clone->cascadeFrom = $parentKey;
        $clone->cascadeChildColumn = $childColumn;

        return $clone;
    }

    /**
     * Render this boolean column as an interactive inline toggle switch.
     *
     * When the user flips the switch the engine calls toggleColumn() with
     * this column's key and the new 0/1 value — no form panel is opened.
     *
     * @param  string|null  $permission  Permission node required to flip the toggle.
     *                                   Defaults to the table's modify permission.
     * @param  string  $onLabel  Tooltip / aria-label when value = 1.
     * @param  string  $offLabel  Tooltip / aria-label when value = 0.
     */
    public function toggleable(
        ?string $permission = null,
        string $onLabel = 'On',
        string $offLabel = 'Off',
    ): self {
        $clone = clone $this;
        $clone->toggleable = true;
        $clone->togglePermission = $permission;
        $clone->toggleOnLabel = $onLabel;
        $clone->toggleOffLabel = $offLabel;

        return $clone;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /** Alias for getKey() — used in Blade templates. */
    public function key(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getDefaultSortDirection(): ?string
    {
        return $this->defaultSortDirection;
    }

    public function getDefaultSortPriority(): ?int
    {
        return $this->defaultSortPriority;
    }

    public function hasDefaultSort(): bool
    {
        return $this->defaultSortDirection !== null;
    }

    public function isBadge(): bool
    {
        return $this->badge;
    }

    /**
     * @return array<string, string>
     */
    public function getColors(): array
    {
        return $this->colors;
    }

    /**
     * @return array<string, array{color?: string, colors?: string, icon?: string|null, position?: string}>
     */
    public function getBadgeProfiles(): array
    {
        return $this->badgeProfiles;
    }

    /**
     * Resolve badge metadata for a rendered value.
     *
     * @return array{color: string, icon: ?string, position: string}
     */
    public function getBadgeProfileForValue(mixed $value): array
    {
        $key = is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value;
        $profile = $this->badgeProfiles[$key] ?? [];

        $color = $profile['color'] ?? ($profile['colors'] ?? ($this->colors[$key] ?? 'gray'));
        $icon = $profile['icon'] ?? null;
        if ($icon === '') {
            $icon = null;
        }
        $position = ($profile['position'] ?? 'left') === 'right' ? 'right' : 'left';

        return [
            'color' => (string) $color,
            'icon' => $icon,
            'position' => $position,
        ];
    }

    public function getVisibleTo(): ?string
    {
        return $this->visibleTo;
    }

    public function getCreateVisibleTo(): ?string
    {
        return $this->createVisibleTo;
    }

    public function getModifyVisibleTo(): ?string
    {
        return $this->modifyVisibleTo;
    }

    public function getCreateEditableTo(): ?string
    {
        return $this->createEditableTo;
    }

    public function getModifyEditableTo(): ?string
    {
        return $this->modifyEditableTo;
    }

    /**
     * Resolve the visibility node for a form mode.
     *
     * Precedence: mode-specific node -> global visibleTo -> null.
     */
    public function visibilityNodeForMode(bool $isCreate): ?string
    {
        return $isCreate
            ? ($this->createVisibleTo ?? $this->visibleTo)
            : ($this->modifyVisibleTo ?? $this->visibleTo);
    }

    /**
     * Resolve the editability node for a form mode.
     *
     * Null means "no extra per-column edit node required".
     */
    public function editabilityNodeForMode(bool $isCreate): ?string
    {
        return $isCreate ? $this->createEditableTo : $this->modifyEditableTo;
    }

    public function getFilter(): ?ArchitectFilter
    {
        return $this->filter;
    }

    public function hasFilter(): bool
    {
        return $this->filter !== null;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function hasType(): bool
    {
        return $this->type !== null;
    }

    public function getEditAs(): ?string
    {
        return $this->editAs;
    }

    /**
     * How the CSV importer should resolve user-typed values into the
     * `editAs` FK. See the `importResolveBy` property docblock for
     * the meaning of return values.
     */
    public function getImportResolveBy(): ?string
    {
        return $this->importResolveBy;
    }

    /**
     * Convenience: true if the CSV importer should attempt to fuzzy-match
     * cell values for this column. False when the user explicitly set
     * `editAs(..., 'id')` (raw FK only).
     */
    public function shouldResolveImport(): bool
    {
        return $this->editAs !== null && $this->importResolveBy !== 'id';
    }

    public function getEditKey(): string
    {
        return $this->editAs ?? $this->key;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    /**
     * @return array<mixed>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Explicit source URL override — null when the auto-generated
     * framework endpoint should be used instead.
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /** Storage disk used to persist an uploaded file for type('upload') columns. */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /** Storage directory used to persist an uploaded file for type('upload') columns. */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /** @return (callable(object): string)|null */
    public function getLabelUsing(): ?callable
    {
        return $this->labelUsing;
    }

    /** @return list<string>|null */
    public function getSearchColumns(): ?array
    {
        return $this->searchColumns;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getCascadeFrom(): ?string
    {
        return $this->cascadeFrom;
    }

    /**
     * Returns the column name on the child lookup table used for the WHERE clause,
     * or false if backend filtering is disabled, or null when falling back to getCascadeFrom().
     */
    public function getCascadeChildColumn(): string|false|null
    {
        return $this->cascadeChildColumn;
    }

    /**
     * Switch this column's lookup lookup endpoint to prefix-search mode
     * (LIKE 'term%') so it can use a B-tree index instead of doing a
     * full table scan with a leading wildcard.
     */
    public function lookupStartsWith(): self
    {
        $clone = clone $this;
        $clone->lookupSearchMode = 'starts_with';

        return $clone;
    }

    public function getLookupSearchMode(): string
    {
        return $this->lookupSearchMode;
    }

    public function isEditable(): bool
    {
        return $this->type !== null;
    }

    public function isToggleable(): bool
    {
        return $this->toggleable;
    }

    public function getTogglePermission(): ?string
    {
        return $this->togglePermission;
    }

    public function getToggleOnLabel(): string
    {
        return $this->toggleOnLabel;
    }

    public function getToggleOffLabel(): string
    {
        return $this->toggleOffLabel;
    }

    /**
     * Set the placeholder/example value for this column.
     *
     * Used by the create form (rendered as the input's HTML
     * `placeholder` attribute) and by the TableBuilder import wizard
     * (rendered as the example row in the downloadable CSV template).
     */
    public function placeholder(string $value): self
    {
        $clone = clone $this;
        $clone->placeholder = $value;

        return $clone;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * Override the engine's inline-edit mode auto-detection for this column.
     *
     *   ->modifyInline()        — force cell-mode on direct click, even if
     *                             the column participates in a cascade or
     *                             cross-field rule. Column still joins a
     *                             row-edit when one is already open.
     *   ->modifyInline(false)   — opt the column out of inline editing
     *                             entirely; cell click does nothing.
     *
     * Without this declaration the engine decides per click:
     *   - column has dependencies     → opens whole row
     *   - no dependencies             → opens single cell
     */
    public function modifyInline(bool $cellOnly = true): self
    {
        $clone = clone $this;
        $clone->modifyInline = $cellOnly;

        return $clone;
    }

    /**
     * Return the inline-edit mode override for this column.
     *
     * @return bool|null null = auto-detect; true = force cell-mode on click; false = opt out entirely
     */
    public function getModifyInline(): ?bool
    {
        return $this->modifyInline;
    }

    /** Never show this column in the create/edit form, even if it has a type set. */
    public function hideOnForm(bool $hide = true): self
    {
        $clone = clone $this;
        $clone->hideOnForm = $hide;

        return $clone;
    }

    /** Never show this column in the index table rows. */
    public function hideOnIndex(bool $hide = true): self
    {
        $clone = clone $this;
        $clone->hideOnIndex = $hide;

        return $clone;
    }

    public function isHiddenOnForm(): bool
    {
        return $this->hideOnForm;
    }

    public function isHiddenOnIndex(): bool
    {
        return $this->hideOnIndex;
    }

    /**
     * @return list<array{field: string, op: string, value: mixed}>
     */
    public function getVisibleWhen(): array
    {
        return $this->visibleWhen;
    }

    public function getAllowCustom(): bool
    {
        return $this->allowCustom;
    }

    public function isAutoLoad(): bool
    {
        return $this->autoLoad !== null;
    }

    /** 0 = no limit (all rows); positive int = latest N. Null when autoLoad disabled. */
    public function getAutoLoadLimit(): ?int
    {
        return $this->autoLoad;
    }

    public function getExtraDataFrom(): ?string
    {
        return $this->extraDataFrom;
    }

    public function getOptGroupsColumn(): ?string
    {
        return $this->optGroupsColumn;
    }

    /**
     * @param  array<string, mixed>  $profiles
     * @return array<string, array{color?: string, colors?: string, icon?: string|null, position?: string}>
     */
    private function normalizeBadgeProfiles(array $profiles): array
    {
        $normalized = [];

        foreach ($profiles as $value => $profile) {
            if (! is_array($profile)) {
                continue;
            }

            $entry = [];

            $color = $profile['color'] ?? ($profile['colors'] ?? null);
            if (is_string($color) && $color !== '') {
                $entry['color'] = $color;
            }

            if (array_key_exists('icon', $profile)) {
                $icon = $profile['icon'];
                $entry['icon'] = (is_string($icon) && $icon !== '') ? $icon : null;
            }

            if (isset($profile['position']) && is_string($profile['position'])) {
                $entry['position'] = $profile['position'] === 'right' ? 'right' : 'left';
            }

            $normalized[(string) $value] = $entry;
        }

        return $normalized;
    }
}
