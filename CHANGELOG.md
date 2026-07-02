# Changelog

All notable changes to `entelechy/architect` will be documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Tables** — `customRowAction()` on `TableBuilder`, extending the `customBulkAction()` idea to individual rows. Register a class implementing `ArchitectRowAction` to run real server-side `handle()` logic against a single row (e.g. resend an invite, sync a record, mark verified), surfaced as an inline success/error banner — unlike `rowAction()`, which is presentation-only (link, custom panel, or a raw browser event).

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
