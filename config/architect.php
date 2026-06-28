<?php

use Entelechy\Architect\Permissions\AllowAllPermissionResolver;
use Entelechy\Architect\Tenancy\NullTenantResolver;

return [
    'auth_guard' => env('ARCHITECT_AUTH_GUARD', 'web'),

    'permissions' => [
        'resolver' => AllowAllPermissionResolver::class,
    ],

    'lookup_route' => 'architect.lookup',

    'table' => [
        'per_page' => 25,
        'per_page_options' => [10, 25, 50, 100],
        'empty_state_text' => 'No records found.',
        'search_debounce' => 400,
    ],

    'asset_version' => env('ARCHITECT_ASSET_VERSION', '1'),

    'toast' => [
        'position' => 'bottom-right',
        'duration' => 4000,
    ],

    'notifications' => [
        'driver' => 'database',
        'polling_interval' => '30s',
        'centre_max_items' => 50,
    ],

    'playground' => [
        'enabled' => env('ARCHITECT_PLAYGROUND', false),
    ],

    'features' => [
        'tables' => true,
        'navigator' => true,
        'toolbar' => true,
        'stats' => true,
        'supersearch' => true,
        'forms' => true,
        'notifications' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Subsystem
    |--------------------------------------------------------------------------
    | Database connection and table names for architect_import_batches.
    | Set 'connection' to null to use the default database connection.
    | Host apps using an existing table can override 'table' and 'items_table'
    | to point at their legacy tables and skip the package migration.
    */
    'import' => [
        'connection' => null,   // null = config('database.default')
        'table' => 'architect_import_batches',
        'items_table' => 'architect_import_batch_items',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy
    |--------------------------------------------------------------------------
    | FQCN of the class implementing \Entelechy\Architect\Contracts\TenantResolver.
    | Defaults to NullTenantResolver (returns empty string — single-tenant).
    | Multi-tenant host apps implement their own resolver (e.g. spatie adapter).
    */
    'tenant' => [
        'resolver' => NullTenantResolver::class,
    ],
];
