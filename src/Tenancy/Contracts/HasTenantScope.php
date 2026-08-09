<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tenancy\Contracts;

/**
 * Opt-in marker for Eloquent models whose table holds rows for every
 * tenant in a shared database. AbstractEloquentDataModel::baseQuery()
 * automatically scopes every query to TenantContext::$identifier when
 * the bound model implements this interface.
 *
 * Not needed for database-per-tenant models — TenantContext::$connection
 * already isolates those at the connection level.
 *
 * See ARCHITECT_IMPROVEMENT_PLAN.md Phase 4.
 */
interface HasTenantScope
{
    /**
     * The column holding each row's owning tenant identifier.
     */
    public static function tenantScopeColumn(): string;
}
