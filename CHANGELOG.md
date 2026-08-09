# Changelog

All notable changes to `entelechy/architect` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Release gate (Phase 6)** — published `RELEASE_CHECKLIST.md` at the repo root, the definition-of-done checklist for this plan's target release, with every item verified against the live repo state (not just asserted).

### Fixed

- **Tests** — fixed the last 2 known-red tests in the suite: `WizardDraftPersistenceTest` failed with `no such table: cache` because the testbench environment had no cache store configured (defaulted to the `database` driver with no migrated `cache` table); `phpunit.xml` now sets `CACHE_STORE=array`, which still exercises the real draft-persistence code path within a test. Also silenced a PHPUnit 12 notice in `FormSearchSetTest` by switching an unasserted `Authenticatable` test double from `createMock()` to `createStub()` (the double's methods are never verified, so a stub — not a mock — is the correct tool). The full suite is now 100% green with zero errors and zero notices.
- **Static analysis** — PHPStan is now genuinely clean at level 8 (previously carried 3 real pre-existing errors plus a stale `ignoreErrors` entry that no longer matched anything): `StubbedFeatureAuditor::findings()` now guards against `file_get_contents()` returning `false` before calling `str_contains()`; `AbstractEloquentDataModel::duplicate()`'s `$except` parameter is now documented `list<string>`; `FormPanel::buildGenericViewRecord()` now wraps its result in `array_values()` so its return type is provably `list<...>`, matching its docblock. Removed the stale `view-string|null` `ignoreErrors` entry from `phpstan.neon.dist` — none of `src/`'s ~20 `view('architect::...')` call sites trigger it any more, so keeping it around only produced an "unmatched ignored error" meta-error.

### Added

- **Docs (Phase 5)** — three new doc pages: **Feature Maturity** (`architect-status.html`, the Stable/Beta/Experimental/Planned legend plus every non-Stable Forms control in one lookup), **Multi-tenancy** (`architect-multi-tenancy.html`, row-level scoping vs database-per-tenant connection switching, a decision tree, and a `stancl/tenancy` adapter cookbook recipe — deferred from Phase 4), and **Feature Matrix (Phase 2)** (`architect-feature-matrix.html`, publishing `PHASE2_FEATURE_MATRIX.md` as a browsable doc page). `architect-form-controls.html` gained a "Roadmap" section grouping all 22 non-Stable controls by maturity with caveats, plus an inline maturity badge on each of those 22 rows in the category tables.
- **Tooling** — `php artisan architect:doctor` gained a sixth check: `Support\Doctor\DocMaturityAuditor` scans `docs/_documentation/*.html` for `maturity-badge` spans and cross-references them against `ControlRegistry`, failing if a non-Stable control has no doc badge, or if a badge's level has drifted out of sync with the registry.

### Added

- **Tooling** — `php artisan architect:doctor` gained a fifth check: `Support\Doctor\StubbedFeatureAuditor` mechanically verifies that every Phase 2 "known gap" disclosure (`ToolbarBuilder::bind()`, `ToolbarBadge::live()`, `NavigatorBuilder::position()`, `StatBuilder`'s standalone top-level types) stays documented until it's genuinely fixed — see `PHASE2_FEATURE_MATRIX.md` for the full audit.

### Fixed

- **Toolbar** — Phase 2 systemic wiring audit (see `PHASE2_FEATURE_MATRIX.md`): the Toolbar subsystem had zero test coverage before this phase. The first comprehensive fixture test surfaced 4 fatal/dead-property bugs, all fixed: 9 Blade partials called an undefined `->key()` instead of `->getKey()` (14 call sites); `dropdown-text-input.blade.php` called undefined `->getType()` (real accessor is `->getInputType()`); `dropdown-submenu.blade.php` called undefined `->getSubItems()` (real accessor is `->getItems()`); `ToolbarButtonGroup::size()` was set but never applied. Also fixed: toolbar-level `->size()`/`->bordered()` were stored but never read by `engine.blade.php` — both are now applied to the toolbar's wrapping element.
- **Stats** — `MetricCard::live()` cards always rendered `—` regardless of their live callable; `DashboardEngine::resolveMetrics()` now actually invokes it, relying on the dashboard's existing `->poll()` interval for periodic re-resolution.

### Documented (known gaps, tracked via `architect:doctor`)

- `ToolbarBuilder::bind()` — `ToolbarEngine` dispatches `architect:toolbar:bound-changed` but no consumer listens for it anywhere.
- `ToolbarBadge::live()` — Toolbar has no polling mechanism; the live callable is never invoked.
- `NavigatorBuilder::position()` — validated and stored, but no Blade partial or component reads it; Navigator never wraps host content, so this is host-app placement metadata only.
- `StatBuilder`'s non-`dashboard`-type top-level `->build()` (standalone metric grid/chart/table/crosstab) — `DashboardEngine` only ever resolves `dashboard`-type `$definition->sections`; nest these types inside a dashboard's `->section()` instead.

### Added

- **Tenancy (Con #4)** — `TenantResolver::resolve(): TenantContext` replaces `currentIdentifier(): string` (see Changed, below). `TenantContext` (`identifier`, `connection`, `metadata`) supports both multi-tenancy strategies under one contract: row-level scoping in a shared database (`connection: null`) and database-per-tenant connection switching (a non-null `connection`), combinable per-model in the same host app. New `Entelechy\Architect\Tenancy\Contracts\HasTenantScope` marker interface — implement it on an Eloquent model (optionally with the `Tenancy\Concerns\ScopedToTenant` trait for the `tenant_id` default column) to have `AbstractEloquentDataModel::baseQuery()` automatically filter every query by the current tenant. A non-null `TenantContext::$connection` switches `baseQuery()` to that connection regardless of `HasTenantScope`. Both mechanisms are proven by real two-tenant isolation tests (`tests/Tenancy/`), not just assertions on generated SQL/code.
- **Tooling** — `php artisan architect:doctor` gained a third check: `Support\Doctor\TenantScopeAuditor` scans `config('architect.tenant.discovery.paths')` (opt-in, empty by default) for `AbstractEloquentDataModel` subclasses that override `baseQuery()` without calling `parent::baseQuery()` first — a mistake that silently disables tenant scoping/connection switching for that model with no error anywhere.

### Fixed

- **Docs** — the `baseQuery()` override examples in `architect-tables.html` and `concepts.html` predated Phase 4 and never called `parent::baseQuery()`; one even manually re-implemented tenant filtering (`->where('tenant_id', tenant()->id)`) in a way that now bypasses the real tenant-scoping mechanism entirely. Both now call `parent::baseQuery()` first, with a callout explaining why.

- **Forms** — `Entelechy\Architect\Support\Maturity` enum (`Stable`/`Beta`/`Experimental`/`Planned`) and a new required `Maturity` parameter on `ControlRegistry::register()`. Every one of the 99 registered Forms controls has been mechanically re-audited against its actual Blade view + Alpine.js wiring and re-labelled accordingly: **50 Stable**, **5 Beta** (works, but narrower than advertised — see the inline comment on each in `ArchitectServiceProvider::registerControlLibrary()`), **33 Experimental** (Blade view references an Alpine component with no matching `Alpine.data(...)` registration anywhere — not functional today), **11 Planned** (9 Wave 3 hardware/media-capture fields — camera, mic, canvas/signature — plus `AddressAutocompleteField`/`PostalCodeLookupField`, all deliberately descoped from Phase 1 pending further decisions, see `ARCHITECT_IMPROVEMENT_PLAN.md`). This corrects the 0.1.21 changelog's "no control ships as experimental" claim, which predated this audit. `ControlRegistry::byMaturity()` lets consumers filter by maturity.
- **Tooling** — new `php artisan architect:doctor` command and two paired PHPUnit regression tests (`ControlMaturityAuditTest`, `ActionWiringAuditTest`, backed by shared `Support\Doctor\*` auditor classes) that mechanically detect two recurring "half-wired feature" bug shapes: (1) a Forms control labelled `Maturity::Stable` whose view references a non-existent Alpine component, and (2) a built-in Table row/bulk action (`clone`, `view`, `audit`, `export`, `copy`, `email`, `status`) whose browser event has no client-side listener at all. Fixed the latter for `export`/`copy`/`email`/`status` bulk actions, which previously dispatched a browser event with **zero** listener (not even a toast) — now show an honest "not yet available" toast, matching the existing `clonable()`/`viewable()`/`auditable()` precedent.
- **Forms** — Phase 1 Wave 1 (see `ARCHITECT_IMPROVEMENT_PLAN.md`): implemented real, dependency-free interactivity for 7 fields that were previously registered as `Beta`/`Experimental` stubs, and re-labelled all 7 `Maturity::Stable` now that `architect:doctor`'s Alpine-wiring audit confirms them clean. All use hand-rolled vanilla JS/DOM APIs — no third-party libraries, per Wave 1's dependency policy:
  - `PasswordStrengthField` — live strength meter (length/case-mix/digit/symbol heuristics).
  - `RankingField` / `SortableListField` — native HTML5 drag-and-drop reordering (shared implementation).
  - `HierarchicalCheckboxTreeField` — tri-state (indeterminate) cascading checkbox tree.
  - `KeyboardShortcutRecorderField` — captures and normalizes a keyboard combo (e.g. `cmd+shift+k`).
  - `DialKnobField` — draggable rotational control (Pointer Events, angle-clamped).
  - `MaskedInputField` — format-as-you-type input masking (`9`/`A`/`*` token masks).
  - `CardInputField` — a generic, vendor-agnostic hosted-fields adapter rather than a hard-coded payment provider integration: loads a configurable `providerScriptUrl`, then expects that script to expose `window.ArchitectCardProvider.mount(el, {publishableKey}, onToken)`. Never handles raw card data (PAN/CVC) server-side — only the provider's returned token reaches Livewire state.

  New `Wave1FieldIntegrationTest` covers each field's `FormEngine` submit/validation round-trip.

- **Forms** — Phase 1 Wave 2 (see `ARCHITECT_IMPROVEMENT_PLAN.md`): implemented real interactivity for the "small, focused third-party JS dependency" tier of fields, re-labelled `Maturity::Stable` now that `architect:doctor`'s Alpine-wiring audit confirms them clean:
  - `KanbanBoardField` — drag-between-columns board using SortableJS (cross-column dragging needs real drop-target detection that native HTML5 drag-and-drop makes error-prone); gained a new `items(array $itemKey => $label)` config method since the field previously had no way to supply card labels at all.
  - `ImageCropperField` — Cropper.js-powered crop/rotate/zoom step before the cropped output replaces the file input's `FileList` (via `DataTransfer`) and Livewire's native `wire:model` upload takes over.
  - `MentionEditorField` — `@mention` autocomplete via Tribute.js over a `contenteditable` div.
  - `ImageComparisonSliderField`, `GradientEditorField`, `EntityPickerField`, `RelationshipPickerField`, `TreeSelectField`, `DualListboxField`, `TemplateEditorField` — vanilla JS/DOM (Pointer Events, imperative DOM construction, debounced `fetch` search), per the plan's assessment that these don't warrant a third-party dependency. `RelationshipPickerField` gained a new `searchUrl(string $url)` config method (receives `?type=...&q=...`, returns `[{id, label}, ...]`) since it previously had no fetch/search mechanism at all.
  - `RegexBuilderTesterField` — already `Maturity::Stable` (its plain pattern/flags/sample inputs were functional), but its own docblock promised "live match highlighting and captured groups" that didn't exist; added a live-highlighting panel using native `RegExp` (no maturity change needed).
  - `CascadingSelectField` and `DiffMergeField`, both listed in the plan's Wave 2 scope, were found already `Maturity::Stable` and fully functional during the pre-implementation audit — no changes needed.

  New npm dependencies: `sortablejs`, `cropperjs` (v1.x — the long-established `new Cropper(el, opts)` API, not the 2024 Web-Components v2 rewrite), `tributejs`. New `Wave2FieldIntegrationTest` covers each field's `FormEngine` submit/validation round-trip.

- **Forms** — Phase 1 Wave 4 (see `ARCHITECT_IMPROVEMENT_PLAN.md`): implemented real interactivity for the "meta/builder / mini-DSL editor" tier of fields, re-labelled `Maturity::Stable` now that `architect:doctor`'s Alpine-wiring audit confirms them clean. Wave 3 (hardware/media capture) remains deliberately `Maturity::Planned` — not part of this rollout, per the plan's own confirmed deferral:
  - `QueryBuilderField` — recursive nested AND/OR condition-group builder (bespoke imperative DOM; the field only builds the tree, evaluation is a host-app concern).
  - `RulesWorkflowBuilderField` — ordered node list + from/to edge list. Deliberately a structured list builder rather than a drag-positioned canvas: the field's own value shape has no `(x, y)` coordinates, so a full 2D canvas would be surface without substance.
  - `NodeGraphEditorField` — draggable, positioned nodes (Pointer Events) connected by SVG edges; this field's value shape does include `(x, y)`, so a real canvas was warranted here.
  - `SchemaDrivenObjectEditorField` — renders one input per JSON Schema property (recursing into nested `type: object` properties), no JSON-schema-form library.
  - `FormulaExpressionEditorField` — bespoke input + field-reference chip insertion + live highlighting of recognized field names.
  - `DataMappingField` — repeatable `{source, destination, transform}` rows.
  - `CronScheduleBuilderField` — friendly per-field schedule builder that assembles a 5-field cron expression; validation and next-3-run-times preview powered by `cron-parser` (the builder UI itself stays bespoke).
  - `RoleBuilderField` — was `Maturity::Beta` (only `permissions`/`inherits_from` were wired); completed to match its own docblock by adding `scope` (plain text input) and `exceptions` (reusing the existing `TagsInputField` Alpine component, `architectTagsInput`, rather than writing a new one).
  - `RecurrenceBuilderField`, `QueryLanguageTextField`, `PermissionMatrixField`, `DependencyBuilderField`, `ApiRequestBuilderField` were found already `Maturity::Stable` and fully functional during the pre-implementation audit — no changes needed.
  - `MathEquationEditorField` — deliberately **not** built on MathQuill: the real npm `mathquill` package hard-depends on jQuery `^1.12.3` (a legacy dependency this codebase otherwise avoids entirely), so this ships as a lightweight LaTeX-snippet palette (fraction/exponent/square-root/π buttons) over a plain text input instead. The field's value is the resulting LaTeX string; visual rendering (KaTeX/MathJax) is left as a host-app concern.
  - `RulesWorkflowBuilderField`/`QueryBuilderField` — the plan's dependency-policy table suggested `json-logic-js` for rule evaluation; not bundled, since neither field evaluates rules itself (that's a host-app concern) — it would have been dead code in the package.
  - `Builder`'s "block sub-field gap" mentioned in the plan was investigated and found to be a pre-existing, already-documented, deliberately deferred limitation (`builder.blade.php`'s own comment: "pending Phase 8 nested-structure support") — not a Wave 4 concern.

  New npm dependency: `cron-parser`. New `Wave4FieldIntegrationTest` covers each field's `FormEngine` submit/validation round-trip.
- **Rendering (Con #3)** — new `Entelechy\Architect\Contracts\ArchitectDefinitionProvider` marker interface (`public static function definition(): object;`) plus 9 per-subsystem narrowing interfaces, each covariantly typing the return to its subsystem's definition class: `Table\Contracts\ProvidesTableDefinition`, `Content\Contracts\ProvidesContentDefinition`, `Stats\Contracts\ProvidesStatDefinition`, `Panels\Contracts\ProvidesDashboardDefinition`, `Forms\Contracts\ProvidesFormDefinition`, `Forms\Contracts\ProvidesWizardDefinition`, `Toolbar\Contracts\ProvidesToolbarDefinition`, `Supersearch\Contracts\ProvidesSupersearchDefinition`, `Navigator\Contracts\ProvidesNavigatorDefinition`. Every host-app "definition class" (Table/Content/Stats/Panels/Forms/Wizard/Toolbar/Supersearch/Navigator) must now `implements` the matching interface — every engine resolves definition classes via `is_subclass_of()`/`instanceof` instead of `method_exists()` duck-typing, so a misconfigured definition class now fails at static-analysis time (or with a clear `LogicException`/`InvalidArgumentException` naming the missing interface) instead of silently duck-typing.
- **Rendering (Con #3)** — `php artisan architect:doctor` gained a new `Support\Doctor\DefinitionInterfaceAuditor` check (Phase 3.4): scans host-app classes discovered via the existing definition-class discovery mechanism (new `architect.doctor.discovery.paths` config, defaults to `app_path()`) and reports any that expose a `definition()`/`build()` method but do not implement the required marker interface for their subsystem.

### Changed

- **BREAKING — Toolbar / Supersearch / Navigator rendering convention unified.** `ToolbarBuilder`/`SupersearchBuilder`/Navigator's `ModuleTabsManager` (workspace-tabs) definition classes must now expose a static `definition()` method (previously `build()`); the old method name is no longer recognized. Update any custom Toolbar/Supersearch/workspace-tabs definition class: rename its static factory method from `build()` to `definition()` and add `implements Provides*Definition` (see Added, above).
- **BREAKING** — Navigator's Blade-only rendering component (used for `tabs`/`pills`/`buttons`/`stepper`/`sidebar`/`dropdown` types) is renamed from `<x-architect::definition-renderer>` to `<x-architect::static>` — the "static" counterpart to every other subsystem's Livewire `<livewire:architect-*>` ("live") tag, since Navigator is the only subsystem that ever renders without a Livewire round-trip. Update any Blade view referencing the old tag name.

### Migration notes

1. For every custom Toolbar/Supersearch/workspace-tabs Navigator definition class: rename `public static function build()` → `public static function definition()`.
2. Add `implements Provides{Subsystem}Definition` to every definition class (Table/Content/Stats/Panels/Forms/Wizard/Toolbar/Supersearch/Navigator), importing the matching interface from `Entelechy\Architect\{Subsystem}\Contracts\Provides{Subsystem}Definition`.
3. Replace any `<x-architect::definition-renderer ...>` Blade reference with `<x-architect::static ...>`.
4. Run `php artisan architect:doctor` to confirm every definition class in your app now satisfies its required interface.

## [0.1.21] — 2026-07-22

### Added

- **Forms** — expanded the control library from 28 to **99 field types** across 9 categories (Text & Structured Text, Choice & Selection, Date & Time, Numeric, Formatted & Validated Text, Relationships & Lookup, File & Media, Visual & Spatial, Structural). Every control is registered with complete metadata via the new `Entelechy\Architect\Forms\ControlRegistry`, queryable at runtime through `Architect::controls()`. No control ships as experimental — every type listed is stable from this release.
- **Forms** — `WizardBuilder` gained id-based step navigation, conditional branching (`branch()`/`then()`), in-progress draft persistence (`drafts()`, `resumeUsingKey()`, `resumeToStepFromDraft()`), a dirty-navigation guard (`guardDirtyNavigation()`), per-step validation hooks (`onStepValidated()`), and deep-linkable step position (`?step=...`). The step graph is validated for reachability at `build()` time and throws `WizardGraphException` on any duplicate id, unknown branch target, or unreachable step.
- **Forms** — new validation DSL under `Entelechy\Architect\Forms\Validation`: `Field::validate(?Preset $preset)` for zero-config named rule bundles (`Preset::workEmail()`, `Preset::ukPhone()`, `Preset::currency()`, etc.), `Field::ruleset(array $rules)` for a fluent, additive `Rule` DSL that compiles 1:1 to native Laravel rules, and `RuleRegistry::register()` for host-app custom named rules. `Field::getClientValidationAttributes()` derives progressive-enhancement HTML5 attributes from a field's compiled rules.
- **Forms** — `Field::permission(string $node)` gates a field's visibility on a permission node; both `FormEngine` and `WizardEngine` now sanitize submitted data server-side, reverting any value for a hidden, disabled, or permission-gated field back to its pre-existing snapshot regardless of what the client submits.
- **Forms** — versioned lifecycle event payloads via `Entelechy\Architect\Forms\Events\EventPayload` (`version`, `form_key`, `timestamp`, plus event-specific keys) across all `FormEvents` constants, including new wizard events `WIZARD_STEP_ENTERED`, `WIZARD_STEP_LEAVING`, `WIZARD_STEP_VALIDATED`, `WIZARD_DRAFT_SAVED`, and `WIZARD_NAVIGATION_BLOCKED`.
- **Forms** — global form-key uniqueness enforcement via `Entelechy\Architect\Forms\FormKeyRegistry` (throws `DuplicateFormKeyException` at mount time when two different definition classes reuse a key on the same page) and the new `php artisan architect:forms:audit-keys` console command for project-wide, offline duplicate-key detection.
- **Forms / Panels / Actions / Supersearch** — `onSavedDispatch()` and `notifyOnSave()` fluent hooks, with the identical API and behavior, added to `FormBuilder`, `WizardBuilder`, and `QuickFormPanel`, so any of the three can dispatch a custom browser event and/or call `Architect::toast()`/`Architect::alert()` after a successful (or failed) save without introducing a parallel notification mechanism.
- **Forms / Supersearch** — `->exposeToSupersearch(string $label)` on `FormBuilder`/`WizardBuilder`, paired with the new `Entelechy\Architect\Forms\FormSearchSet::for()` adapter that turns an exposed form or wizard into a permission-aware `NavigationSearchSet` entry.
- **Forms / Actions** — `CreateAction`/`EditAction`'s `->formClass()` now transparently supports wizard definitions: `ActionEngine` detects whether the target class's `definition()` returns an `ArchitectWizardDefinition` and renders `architect-wizard-engine` instead of `architect-form-engine` automatically, with wizard completion (`architect:wizard:completed`) closing the action panel identically to form completion.
- **Setup** — new `php artisan architect:init` command: a one-time, interactive setup flow that locks five foundational project decisions into `config/architect.php` — persistence backend mode, tenancy mode, and state table name (hard-locked), plus state storage DB connection and auth guard (soft-locked, changeable via `--force-reconfigure`, optionally scoped with `--only=key,...`). Hard-locked changes require `--break-glass` and a second explicit confirmation. Config writes are atomic (temp file + `php -l` validation) and back up the previous config on every re-run.
- **Setup** — new `php artisan architect:setup:status` command reporting current initialization state, chosen values, and lock classes.
- **Setup** — choosing `database` persistence mode during `architect:init` generates a host migration for the locked state table (composite-unique on `user_id`, `tenant_identifier`, `scope`, `state_key`), honoring the chosen DB connection.
- **Persistence** — new `Entelechy\Architect\Contracts\StateStore` runtime resolver, with `LocalStateStore` (no-op, `localStorage` mode) and `DatabaseStateStore` (Eloquent-backed via the generated state table) implementations, selected automatically from the locked `architect.state.mode` config.
- **Tables** — remembered filters, bookmarked filters, and hidden-column preferences now support server-side persistence via the new `StateStore` when `database` mode is locked in, in addition to the existing `localStorage` behavior.
- **Tables** — new `customForm(for: ..., definitionClass: ..., mode: ...)` orchestration API for create/modify flows. Supports launch modes: `modal`, `slide-over`, `same-window-page`, `new-window`, and `tabs-manager`.
- **Tables** — return-hook support for external custom forms:
	- callback query refresh (configurable via `callbackQueryKey`),
	- same-origin `postMessage` refresh (`type: 'architect:table-custom-form-saved'`).
- **Breadcrumbs (Core)** — introduced normalized breadcrumb primitives (`BreadcrumbItem`, `BreadcrumbTrail`) and dropdown-ready breadcrumb metadata via optional `menu` items.
- **Tables** — `breadcrumbsAutomatic()` and `breadcrumbsMode()` for request-path-driven breadcrumb generation with configurable home/current behavior.
- **Tables / Forms** — new `->redact()` UI helper, shared via `Entelechy\Architect\Support\Redaction\Redactable` across `Table\Column` and `Forms\Fields\Field`. `redact(string|RedactionStrategy $strategy = 'partial')` masks a value (`RedactionStrategy::partial()`/`::full()`/`::custom(Closure)`, with a fixed-length mask run that never leaks the real value's length); `redactUnless(string $permission)` bypasses masking entirely for holders of a permission node; `revealable(?string $permission = null)` offers a click-to-reveal affordance. On `Column`, redaction is enforced server-side in the index table, the single-row view modal, and every export format (CSV, Excel, HTML/print) via the new `Table\Permissions\RedactionFilter` — masking happens before a row ever reaches Blade or the Livewire response payload, and `revealColumn()` fetches a freshly-authorised single cell rather than unmasking data already sent to the browser. On `Forms\Fields\Field`, redaction is wired up for `DisplayField` only (mask or `redactUnless()` bypass, no click-to-reveal — masking an editable input has no security value).
- **Tables / Forms** — new `->tooltip(string $text)` UI helper on `Table\Column` and `Forms\Fields\Field` (plus `getTooltip()` on the `Forms\Contracts\ArchitectField` interface). Renders a small hover-only info icon (native `title` attribute, no JS tooltip library — consistent with the existing `Toolbar` item tooltips) next to the column header or field label, for context that doesn't warrant `hint()`'s always-visible line. Wired into the index table header, and into `<x-architect::field-wrapper>` (the shared component used by 30+ Forms field types) plus `DisplayField`; custom-layout fields that render their own label without the shared wrapper (`CheckboxField`, `ToggleField`, `ButtonGroup`, `Rating`, `SegmentedControl`, `YesNoUnknown`, `Fieldset`) don't render it yet.

### Changed

- **BREAKING (Tenancy)** — `Contracts\TenantResolver::currentIdentifier(): string` is replaced by `resolve(): Tenancy\TenantContext`. Every call site inside the package (`Table\Livewire\Engine`'s state persistence, `Table\Import\ImportProcessor`/`ImportRateLimiter`/`Table\Livewire\ImportWizard`) has been updated. Custom `TenantResolver` implementations must implement `resolve()` instead of `currentIdentifier()`; `NullTenantResolver` (the single-tenant default) is unaffected in behavior.
- **Breaking (Tables)** — `formMode('page')` is removed. Use `formMode('wizard')` and pair it with `customForm(for: 'create'|'modify', ...)` when wizard mode is enabled.
- **Navigator / Tables** — SPA inherit-mode tab breadcrumbs now resolve automatic table breadcrumbs consistently with standalone table rendering.

### Compatibility notes (Forms)

- No existing `FormBuilder`, `WizardBuilder`, or `Field` method was renamed, removed, or had its parameter order/types changed. All new capability was added as new methods or as optional trailing parameters with defaults (e.g. `WizardBuilder::step()`'s new `?string $id = null` third parameter) — see `FORMS_API_COMPATIBILITY_CONTRACT.md` for the full frozen contract and `FORMS_BASELINE_CAPABILITY_MATRIX.md` for the per-method stability ledger.
- Existing event *names* (`architect:form:saved`, `architect:form:autosaved`, `architect:wizard:completed`) are unchanged; their payloads gained new keys (`version`, `form_key`, `timestamp`, plus event-specific keys). Listeners that only react to the event name are unaffected; no internal listener destructures a payload positionally.
- `WizardEngine`'s internal step-tracking representation moved from an integer `$currentStep` to a string `$currentStepId`. This was never part of the public contract — only the Blade view contract and the `nextStep()`/`previousStep()`/`submit()` action method names are observable, and none of those changed.

### Migration

- **Tenancy**: any custom `TenantResolver` implementation must rename `currentIdentifier(): string` to `resolve(): Tenancy\TenantContext` (e.g. `return new TenantContext($previousIdentifierLogic);`). Host apps using the default `NullTenantResolver` need no changes.
- Replace:
	- `->formMode(create: 'page')` with `->formMode(create: 'wizard')`
	- `->formMode(modify: 'page')` with `->formMode(modify: 'wizard')`
- Add `customForm()` for each enabled wizard flow.
- For `new-window` or `same-window-page` custom forms, either:
	- return with callback query (for example `?architect_refresh=1`), or
	- post `architect:table-custom-form-saved` to `window.opener` on same origin.

## [0.1.20] — 2026-07-02

### Added

- **Tables** — `customRowAction()` on `TableBuilder`, extending the `customBulkAction()` idea to individual rows. Register a class implementing `ArchitectRowAction` to run real server-side `handle()` logic against a single row (e.g. resend an invite, sync a record, mark verified), surfaced as an inline success/error banner — unlike `rowAction()`, which is presentation-only (link, custom panel, or a raw browser event).
- **Tables / Columns** — mode-specific column permission gates: `createVisibleTo()`, `modifyVisibleTo()`, `createEditableTo()`, and `modifyEditableTo()`. Enforced in create/modify forms and inline edit saves, with read-only rendering for locked fields and server-side filtering of unauthorized writes.
- **Tables / Columns** — upgraded badge profiles via `badge(array)` to support per-value `color`, `icon`, and icon `position` (`left`/`right`) while preserving backward compatibility with `colors()`.

## [0.1.0] — 2026-06-28

### Initial public release

- **Tables** — sortable, filterable, bulk-action, row-action, export, and permission-gated table engine powered by Livewire 4
- **Forms** — 28 field types, FormBuilder, WizardBuilder, conditional visibility/disabled logic, autosave, lifecycle hooks
- **Navigation** — tab-based SPA navigator with dynamic-tab type registration, sidebar/toolbar/pills/buttons/dropdown/tabs layouts
- **Dashboards** — stat cards, ApexCharts chart panels, embedded table panels, quick-form panels, configurable grid layout
- **Supersearch** — global cross-resource keyboard-shortcut search with definition-based result rendering
- **Notifications** — toast, alert, inbox, and announcement delivery via a rule-engine with template interpolation; polling-based NotificationCentre Livewire component
- **Actions** — CreateAction, EditAction, ViewAction slide-over panels with fluent field API
- **MCP tools** — `architect_list_fields`, `architect_schema_help`, `architect_list_columns` for AI-assistant integration via the Laravel MCP server
- **Permission gating** — per-component permission nodes resolved via `Gate::check()` (configurable)
- PHPStan level 8, Laravel Pint code style, PHPUnit test suite
