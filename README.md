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
