# Changelog

All notable changes to `entelechy/architect` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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

- **Breaking (Tables)** — `formMode('page')` is removed. Use `formMode('wizard')` and pair it with `customForm(for: 'create'|'modify', ...)` when wizard mode is enabled.
- **Navigator / Tables** — SPA inherit-mode tab breadcrumbs now resolve automatic table breadcrumbs consistently with standalone table rendering.

### Compatibility notes (Forms)

- No existing `FormBuilder`, `WizardBuilder`, or `Field` method was renamed, removed, or had its parameter order/types changed. All new capability was added as new methods or as optional trailing parameters with defaults (e.g. `WizardBuilder::step()`'s new `?string $id = null` third parameter) — see `FORMS_API_COMPATIBILITY_CONTRACT.md` for the full frozen contract and `FORMS_BASELINE_CAPABILITY_MATRIX.md` for the per-method stability ledger.
- Existing event *names* (`architect:form:saved`, `architect:form:autosaved`, `architect:wizard:completed`) are unchanged; their payloads gained new keys (`version`, `form_key`, `timestamp`, plus event-specific keys). Listeners that only react to the event name are unaffected; no internal listener destructures a payload positionally.
- `WizardEngine`'s internal step-tracking representation moved from an integer `$currentStep` to a string `$currentStepId`. This was never part of the public contract — only the Blade view contract and the `nextStep()`/`previousStep()`/`submit()` action method names are observable, and none of those changed.

### Migration

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
