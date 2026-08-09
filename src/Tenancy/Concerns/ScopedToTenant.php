<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tenancy\Concerns;

/**
 * Default HasTenantScope column ('tenant_id'). Skip this trait and
 * implement tenantScopeColumn() directly for a non-default column name.
 */
trait ScopedToTenant
{
    public static function tenantScopeColumn(): string
    {
        return 'tenant_id';
    }
}
