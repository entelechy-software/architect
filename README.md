# Architect

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A Laravel package for building data-rich admin interfaces with tables, forms, navigation, dashboards, stats, supersearch, and notifications — powered by Livewire 4.

## Requirements

- PHP 8.3+
- Laravel 13
- Livewire 4.3+

## Installation

```bash
composer require entelechy/architect
```

Publish the config and assets:

```bash
php artisan vendor:publish --tag=architect-config
php artisan vendor:publish --tag=architect-assets
```

Run the migrations:

```bash
php artisan migrate
```

Initialize the project's foundational setup (required, one-time):

```bash
php artisan architect:init
```

This interactive command asks five questions and permanently locks the answers into `config/architect.php`:

| # | Option | Lock class |
|---|--------|------------|
| 1 | Persistence backend mode (`localStorage` or `database`) | Hard |
| 2 | Tenancy mode (`single` or `multi`) | Hard |
| 3 | State table name | Hard |
| 4 | State storage DB connection | Soft |
| 5 | Architect auth guard | Soft |

- **Hard-locked** options cannot be changed on a re-run unless you pass `--break-glass` (which also requires a second explicit confirmation, since it can orphan existing persisted state).
- **Soft-locked** options can be changed with `--force-reconfigure`. Use `--only=state_connection,auth_guard` to restrict a reconfigure run to specific soft-locked keys, guarding against accidentally drifting others.
- Choosing `database` persistence mode generates a migration for the state table (skip with `--no-migration` if you want to hand-write it).
- Inspect the current locked state at any time with:

```bash
php artisan architect:setup:status
```

`database` persistence mode is what backs server-side storage of remembered/bookmarked table filters and hidden columns (see Tables below) instead of the browser's `localStorage`.

## Quick start

### A table

```php
use Entelechy\Architect\Facades\Architect;
use Entelechy\Architect\Table\Columns\TextColumn;

public function definition(): array
{
    return Architect::table()
        ->model(User::class)
        ->columns([
            TextColumn::make('name')->sortable()->searchable(),
            TextColumn::make('email')->sortable(),
        ])
        ->build();
}
```

### A form

```php
use Entelechy\Architect\Facades\Architect;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Fields\SelectField;

public function definition(): array
{
    return Architect::form()
        ->fields([
            TextField::make('name')->required(),
            SelectField::make('role')->options(['admin' => 'Admin', 'user' => 'User']),
        ])
        ->build();
}
```

## Features

- **Tables** — sortable, filterable, searchable, exportable, bulk actions, permissions, row actions
- **Forms** — 28 field types, conditional logic, autosave, wizard builder, repeaters
- **Navigation** — tab-based SPA navigation with dynamic tabs
- **Dashboards** — stat cards, charts, embedded tables, quick-form panels
- **Supersearch** — global cross-resource search with keyboard shortcut
- **Notifications** — toast, alert, inbox, and announcement delivery via rule engine
- **MCP tools** — AI-assistant integration via the Laravel MCP server
- **Actions** — slide-over create/edit/view panels driven by a fluent API

## Documentation

Full documentation is available at the [Architect website](https://github.com/entelechy-software/architect).

## License

MIT. See [LICENSE](LICENSE).
