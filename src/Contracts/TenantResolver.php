<?php

declare(strict_types=1);

namespace Entelechy\Architect\Contracts;

/**
 * Resolves the current tenant identifier for import batch tracking.
 *
 * In single-tenant applications the null resolver is used and the
 * identifier is always an empty string. In multi-tenant applications
 * (e.g. spatie/laravel-multitenancy) a host-app adapter implements this
 * interface and returns the current tenant's slug or UUID.
 *
 * Bind a custom implementation in the host app's AppServiceProvider:
 *
 *   $this->app->singleton(TenantResolver::class, MyTenantResolver::class);
 */
interface TenantResolver
{
    /**
     * Return a string that uniquely identifies the current tenant,
     * or an empty string when tenancy is not in use.
     */
    public function currentIdentifier(): string;
}
