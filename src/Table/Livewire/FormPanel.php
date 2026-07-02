<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Livewire;

use Carbon\CarbonImmutable;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\HasViewAll;
use Entelechy\Architect\Table\Permissions\FieldVisibilityFilter;
use Entelechy\Architect\Table\Permissions\PermissionGate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * TableBuilder form panel — Tabler offcanvas (slide-over) edition.
 *
 * Lifecycle (slide-over mode):
 *   1. Engine emits 'architect:open-create' or 'architect:open-edit'
 *      with the definition class FQCN (and the record id when editing).
 *   2. This component listens, loads the definition + (for edit) the
 *      forForm() payload, and pops the offcanvas open via Alpine.
 *   3. User edits fields bound to $form, then submits.
 *   4. submit() validates against each visible field's rules, then calls
 *      the data model's create() or modify(). Layer 2 + 3 permission
 *      gates fire before any write.
 *   5. On success the offcanvas closes and the parent engine is told to
 *      refresh via dispatch('architect:refresh').
 *
 * The form payload is pushed and pulled with no schema assumptions
 * beyond what the data model returns from forForm() — every field
 * decides its own validation rules and storage shape, the panel just
 * shuttles the array.
 */
#[Layout('layouts.app')]
class FormPanel extends Component
{
    public string $definitionClass = '';

    public ?int $recordId = null;

    public bool $open = false;

    /**
     * Render mode. 'slide-over' (default) renders the Tabler offcanvas
     * triggered by Engine events. 'wizard' mounts directly under a route
     * and renders as a full-card form on its own page; the panel
     * auto-opens at mount time.
     */
    public string $mode = 'slide-over';

    /**
     * URL the Cancel / back button returns to in wizard mode. Ignored in
     * slide-over mode.
     */
    public ?string $cancelUrl = null;

    /**
     * Form payload — keyed by field name. Values arrive from forForm()
     * for edit mode and from setDefaults() for create mode, then
     * mutated in-place by Livewire's wire:model bindings.
     *
     * @var array<string, mixed>
     */
    public array $form = [];

    /**
     * Most recent error message, surfaced inside the offcanvas footer.
     * Validation errors are surfaced via Livewire's $errors bag instead.
     */
    public ?string $errorMessage = null;

    /** Standard Engine error contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public bool $isLoading = false;

    /**
     * Which content the panel is currently showing.
     * 'idle'   — closed, no content loaded.
     * 'create' — create form.
     * 'edit'   — edit form.
     * 'view'   — read-only label/value list from Model::viewAll().
     * 'custom' — arbitrary Blade partial loaded by a panelView() RowAction.
     */
    public string $panelState = 'idle';

    /** Displayed in the panel header chrome across all states. */
    public string $panelTitle = '';

    /**
     * Blade view path rendered in 'custom' state.
     * Set by openCustom() when a RowAction declares ->panelView().
     */
    public string $customBlade = '';

    /**
     * Data passed as variables to the custom Blade partial.
     *
     * @var array<string, mixed>
     */
    public array $customData = [];

    /**
     * Label/value pairs from Model::viewAll() rendered in 'view' state.
     *
     * @var list<array{label: string, value: mixed}>
     */
    public array $viewRecord = [];

    public function mount(string $definitionClass, ?int $id = null, ?string $mode = null, ?string $cancelUrl = null): void
    {
        $this->definitionClass = $definitionClass;

        if ($mode !== null) {
            if (! in_array($mode, ['slide-over', 'wizard', 'modal'], true)) {
                throw new \InvalidArgumentException("FormPanel mode must be 'slide-over', 'wizard', or 'modal', got '{$mode}'.");
            }
            $this->mode = $mode;
        } else {
            // Derive render mode from definition when embedded in the Engine
            // (no explicit mode passed). 'wizard' must always be set explicitly
            // via a route mount — never inferred from the definition here.
            $formMode = $this->definition()->formMode;
            if ($formMode !== 'wizard') {
                $this->mode = $formMode;
            }
        }

        $this->cancelUrl = $cancelUrl;

        // Wizard mode auto-opens at mount: either a fresh create form or
        // the edit form for the supplied record id.
        if ($this->mode === 'wizard') {
            if ($id === null) {
                $this->openCreate($definitionClass);
            } else {
                $this->openEdit($definitionClass, $id);
            }
        }
    }

    #[On('architect:open-create')]
    public function openCreate(string $definitionClass): void
    {
        if ($definitionClass !== $this->definitionClass) {
            return; // event was for another panel on the page
        }

        app(PermissionGate::class)->assertCanCreate(
            $this->currentUser(),
            $this->definition()
        );

        $this->recordId = null;
        $this->form = $this->defaultsForCreate();
        $this->errorMessage = null;
        $this->hasError = false;
        $this->resetErrorBag();
        $this->panelState = 'create';
        $this->panelTitle = 'New '.($this->definition()->title ?? 'Record');
        $this->viewRecord = [];
        $this->open = true;
    }

    #[On('architect:open-edit')]
    public function openEdit(string $definitionClass, int $id): void
    {
        if ($definitionClass !== $this->definitionClass) {
            return;
        }

        $def = $this->definition();
        $user = $this->currentUser();
        $dataModel = $this->dataModel();

        // Layer 2 + Layer 3 gates: must hold modify and (per-row) canActOn.
        app(PermissionGate::class)->assertCanActOnRecord(
            $user, $def, $dataModel, 'modify', $id
        );

        $payload = $dataModel->forForm($id);

        if ($payload === null) {
            throw new \RuntimeException("Record {$id} not found.");
        }

        $payload = app(FieldVisibilityFilter::class)->stripForm($user, $def, $payload);

        $this->recordId = $id;
        $this->form = $payload;
        $this->errorMessage = null;
        $this->hasError = false;
        $this->resetErrorBag();
        $this->panelState = 'edit';
        $this->panelTitle = 'Edit '.($def->title ?? 'Record');
        $this->viewRecord = [];
        $this->open = true;
    }

    #[On('architect:open-view')]
    public function openView(string $definitionClass, int $id): void
    {
        if ($definitionClass !== $this->definitionClass) {
            return;
        }

        $modelClass = $this->dataModel()->modelClass();
        /** @var Model|null $record */
        $record = $modelClass::find($id);

        if ($record === null) {
            return;
        }

        if (! ($record instanceof HasViewAll)) {
            return;
        }

        $this->recordId = $id;
        $this->viewRecord = $record->viewAll();
        $this->panelState = 'view';
        $this->panelTitle = 'View '.($this->definition()->title ?? 'Record');
        $this->form = [];
        $this->errorMessage = null;
        $this->hasError = false;
        $this->open = true;
    }

    /**
     * Open the panel with a custom Blade partial as the body.
     *
     * Fired by Engine::handleRowAction() when a RowAction declares
     * ->panelView(blade, title). The row data is passed as $data.
     *
     * @param  array<string, mixed>  $data
     */
    #[On('architect:open-custom')]
    public function openCustom(string $definitionClass, string $title, string $blade, array $data = []): void
    {
        if ($definitionClass !== $this->definitionClass) {
            return;
        }

        $this->customBlade = $blade;
        $this->customData = $data;
        $this->panelState = 'custom';
        $this->panelTitle = $title;
        $this->recordId = null;
        $this->form = [];
        $this->viewRecord = [];
        $this->errorMessage = null;
        $this->hasError = false;
        $this->open = true;
    }

    #[On('architect:open-custom-form')]
    public function openCustomForm(
        string $definitionClass,
        string $title,
        string $customDefinitionClass,
        string $customMode = 'modal',
        ?int $recordId = null,
    ): void {
        if ($definitionClass !== $this->definitionClass) {
            return;
        }

        if (in_array($customMode, ['modal', 'slide-over'], true)) {
            $this->mode = $customMode;
        }

        $this->customBlade = 'architect::table.custom-form-host';
        $this->customData = [
            'customDefinitionClass' => $customDefinitionClass,
            'engineComponent' => $this->resolveCustomFormEngineComponent($customDefinitionClass),
            'recordId' => $recordId,
            'instanceKey' => md5($this->definitionClass),
        ];
        $this->panelState = 'custom';
        $this->panelTitle = $title;
        $this->recordId = $recordId;
        $this->form = [];
        $this->viewRecord = [];
        $this->errorMessage = null;
        $this->hasError = false;
        $this->open = true;
    }

    /**
     * Close the panel from any state.
     *
     * Custom Blade partials can trigger this by dispatching the
     * 'architect:close-panel' Livewire event.
     */
    #[On('architect:close-panel')]
    public function closePanel(): void
    {
        $this->open = false;
        $this->panelState = 'idle';
        $this->panelTitle = '';
        $this->customBlade = '';
        $this->customData = [];
        $this->viewRecord = [];
    }

    public function close(): void
    {
        // In wizard mode the panel cannot be 'closed' inline — the user
        // navigates back to the index. Surface that as a redirect so a
        // single Cancel button works for both modes.
        if ($this->mode === 'wizard') {
            $url = $this->cancelUrl ?? url()->previous();
            $this->redirect($url, navigate: true);

            return;
        }

        $this->open = false;
        $this->recordId = null;
        $this->form = [];
        $this->errorMessage = null;
        $this->hasError = false;
        $this->panelState = 'idle';
        $this->panelTitle = '';
        $this->viewRecord = [];
        $this->customBlade = '';
        $this->customData = [];
        $this->resetErrorBag();
    }

    public function submit(): void
    {
        $def = $this->definition();
        $user = $this->currentUser();
        $dataModel = $this->dataModel();

        $isCreate = $this->recordId === null;

        // Re-assert permission immediately before write.
        try {
            if ($isCreate) {
                app(PermissionGate::class)->assertCanCreate($user, $def);
            } else {
                app(PermissionGate::class)->assertCanActOnRecord(
                    $user, $def, $dataModel, 'modify', $this->recordId ?? 0
                );
            }
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = 'You do not have permission to perform this action.';
            $this->dispatch('architect:unauthorized');

            return;
        }

        $formColumns = $this->getFormColumns($isCreate);
        $editableColumns = array_values(array_filter(
            $formColumns,
            fn (Column $column): bool => $this->canEditColumn($column, $isCreate)
        ));

        // Lookup columns arrive as {val,txt} arrays from forForm()
        // (and from the JS combobox wrapper). Flatten to scalars so
        // the integer rule on the column passes.
        $this->flattenLookupValues($editableColumns);
        $this->normalizeTemporalValues($editableColumns);

        try {
            $validated = $this->validateAgainstColumns($editableColumns);
        } catch (ValidationException $e) {
            // Validation errors render via $errors bag automatically;
            // re-throw so Livewire stops here.
            throw $e;
        }

        try {
            if ($isCreate) {
                $newId = $dataModel->create($validated);
                $this->dispatch('architect:created', id: $newId);
            } else {
                $dataModel->modify((int) $this->recordId, $validated);
                $this->dispatch('architect:updated', id: (int) $this->recordId);
            }
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            report($e);

            return;
        }

        // Targeted refresh: include instanceKey so that when multiple
        // Engines are embedded on a single page, only the matching one
        // re-queries. Empty key would broadcast to every Engine.
        $this->dispatch('architect:refresh', instanceKey: md5($this->definitionClass));

        if ($this->mode === 'wizard') {
            // After a wizard-mode submit, redirect back to the index.
            $url = $this->cancelUrl ?? url()->previous();
            $this->redirect($url, navigate: true);

            return;
        }

        // persistOnCreate: keep the create panel open and clear fields so
        // the operator can immediately enter the next record. Has no
        // effect on edits — those always close.
        if ($isCreate && $def->persistOnCreate) {
            $this->form = $this->defaultsForCreate();
            $this->errorMessage = null;
            $this->hasError = false;
            $this->resetErrorBag();

            return;
        }

        $this->close();
    }

    public function render(): View
    {
        $isCreate = $this->recordId === null;
        $isFormState = in_array($this->panelState, ['create', 'edit'], true);

        return view('architect::table.form-panel', [
            'definition' => $this->definition(),
            'columns' => $isFormState ? $this->getFormColumns($isCreate) : [],
            'isCreate' => $isCreate,
            'mode' => $this->mode,
            'panelState' => $this->panelState,
            'panelTitle' => $this->panelTitle,
            'viewRecord' => $this->viewRecord,
            'customBlade' => $this->customBlade,
            'customData' => $this->customData,
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────

    public function definition(): ArchitectTableDefinition
    {
        $class = $this->definitionClass;

        if (! class_exists($class) || ! method_exists($class, 'definition')) {
            throw new \LogicException(
                "TableBuilder form panel: '{$class}' must expose a static ::definition() method"
            );
        }

        /** @var ArchitectTableDefinition $def */
        $def = $class::definition();

        return $def;
    }

    private function resolveCustomFormEngineComponent(string $definitionClass): string
    {
        if (! class_exists($definitionClass) || ! method_exists($definitionClass, 'definition')) {
            throw new \LogicException(
                "Custom form definition class [{$definitionClass}] must expose static definition()."
            );
        }

        $definition = $definitionClass::definition();

        if ($definition instanceof ArchitectWizardDefinition) {
            return 'architect-wizard-engine';
        }

        if ($definition instanceof ArchitectFormDefinition) {
            return 'architect-form-engine';
        }

        throw new \LogicException(
            "Custom form definition class [{$definitionClass}] must return ArchitectFormDefinition or ArchitectWizardDefinition."
        );
    }

    private function dataModel(): ArchitectDataModel
    {
        /** @var ArchitectDataModel $instance */
        $instance = app($this->definition()->dataModelClass);

        return $instance;
    }

    private function currentUser(): ?Authenticatable
    {
        return auth()->user();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultsForCreate(): array
    {
        $defaults = [];

        // Get columns for create mode (columns with ->type() set)
        foreach ($this->getFormColumns(true) as $column) {
            $defaults[$column->getEditKey()] = null;
        }

        return $defaults;
    }

    /**
     * Build the rule set from each column with validation rules.
     *
     * @param  list<Column>  $columns
     * @return array<string, mixed>
     */
    private function validateAgainstColumns(array $columns): array
    {
        $rules = [];
        $applicable = [];

        foreach ($columns as $column) {
            $editKey = $column->getEditKey();
            $applicable[] = $editKey;

            $columnRules = $column->getRules();
            if ($columnRules !== null && $columnRules !== '') {
                $rules['form.'.$editKey] = $columnRules;
            }
        }

        // Livewire v4 throws MissingRulesException when validate() is
        // called with an empty rule set. Skip it entirely in that case.
        if (empty($rules)) {
            return array_intersect_key($this->form, array_flip($applicable));
        }

        $validated = $this->validate($rules);

        /** @var array<string, mixed> $formPart */
        $formPart = $validated['form'] ?? [];

        // Only return keys we asked for, dropping any stale form state
        return array_intersect_key($formPart, array_flip($applicable));
    }

    /**
     * @param  list<Column>  $columns
     */
    private function flattenLookupValues(array $columns): void
    {
        foreach ($columns as $column) {
            if (! in_array($column->getType(), ['lookup', 'multiselect'], true)) {
                continue;
            }
            $editKey = $column->getEditKey();
            $value = $this->form[$editKey] ?? null;
            if (is_array($value) && array_key_exists('val', $value)) {
                $val = $value['val'];
                $this->form[$editKey] = ($val === '' || $val === null) ? null : (int) $val;
            }
        }
    }

    /**
     * Normalize native browser date/time inputs into the field formats the
     * TableBuilder validators expect.
     *
     * HTML date inputs post ISO values such as Y-m-d and datetime-local posts
     * Y-m-d\TH:i, while our field rules validate against display formats like
     * d/m/Y and d/m/Y H:i.
     *
     * @param  list<Column>  $columns
     */
    private function normalizeTemporalValues(array $columns): void
    {
        foreach ($columns as $column) {
            $editKey = $column->getEditKey();
            $value = $this->form[$editKey] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $normalized = match ($column->getType()) {
                'date' => $this->normalizeTemporalValue($value, ['Y-m-d'], 'd/m/Y'),
                'datetime', 'date_time' => $this->normalizeTemporalValue($value, ['Y-m-d\TH:i', 'Y-m-d H:i'], 'd/m/Y H:i'),
                'time' => $this->normalizeTemporalValue($value, ['H:i'], 'H:i'),
                default => null,
            };

            if ($normalized !== null) {
                $this->form[$editKey] = $normalized;
            }
        }
    }

    /**
     * @param  list<string>  $sourceFormats
     */
    private function normalizeTemporalValue(string $value, array $sourceFormats, string $targetFormat): ?string
    {
        foreach ($sourceFormats as $sourceFormat) {
            try {
                $parsed = CarbonImmutable::createFromFormat($sourceFormat, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed instanceof CarbonImmutable) {
                return $parsed->format($targetFormat);
            }
        }

        return null;
    }

    /**
     * Get columns applicable for the form based on create/modify mode.
     *
     * @return list<Column>
     */
    private function getFormColumns(bool $isCreate): array
    {
        $def = $this->definition();
        $user = $this->currentUser();

        // Get columns based on mode
        $columns = $isCreate ? $def->getCreateColumns() : $def->getModifyColumns();

        // Filter columns by visibility permission (mode-specific first, then global fallback).
        $visibleColumns = [];
        foreach ($columns as $column) {
            $permission = $column->visibilityNodeForMode($isCreate);
            if ($permission !== null && ! app(PermissionGate::class)->userCan($user, $permission)) {
                continue;
            }
            $visibleColumns[] = $column;
        }

        return $visibleColumns;
    }

    public function canEditColumn(Column $column, bool $isCreate): bool
    {
        $permission = $column->editabilityNodeForMode($isCreate);
        if ($permission === null) {
            return true;
        }

        return app(PermissionGate::class)->userCan($this->currentUser(), $permission);
    }
}
