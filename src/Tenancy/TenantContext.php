<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tenancy;

/**
 * Frozen snapshot of "who is the current tenant, and how do I reach
 * their data" — returned by TenantResolver::resolve().
 *
 * `connection` is optional: null means shared-database/row-level scoping
 * (see Tenancy\Contracts\HasTenantScope); a connection name means
 * "switch database connections for this tenant" (database-per-tenant
 * host apps, e.g. a stancl/tenancy adapter). A single host app can mix
 * both strategies across different models — the choice is per-request
 * (via this object), not global.
 *
 * See ARCHITECT_IMPROVEMENT_PLAN.md Phase 4.
 */
final class TenantContext
{
    /**
     * @param  array<string, mixed>  $metadata  Extensible bag for adapter-specific
     *                                          data, without breaking this constructor again.
     */
    public function __construct(
        public readonly string $identifier = '',
        public readonly ?string $connection = null,
        public readonly array $metadata = [],
    ) {}
}
