<?php

declare(strict_types=1);

namespace Entelechy\Architect\Contracts;

use Entelechy\Architect\Tenancy\TenantContext;

/**
 * Resolves the current tenant for import batch tracking, user-state
 * persistence, and (Phase 4) AbstractEloquentDataModel query scoping.
 *
 * In single-tenant applications the null resolver is used and every
 * TenantContext is empty/connection-less. In multi-tenant applications
 * a host-app adapter implements this interface — e.g. row-level scoping
 * in a shared database (TenantContext::$connection left null) or
 * database-per-tenant connection switching (e.g. a stancl/tenancy
 * adapter setting TenantContext::$connection).
 *
 * Bind a custom implementation in the host app's AppServiceProvider:
 *
 *   $this->app->singleton(TenantResolver::class, MyTenantResolver::class);
 */
interface TenantResolver
{
    /**
     * Resolve the current tenant context, or an empty TenantContext
     * when tenancy is not in use.
     */
    public function resolve(): TenantContext;
}
