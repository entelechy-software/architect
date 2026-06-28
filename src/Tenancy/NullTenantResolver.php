<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tenancy;

use Entelechy\Architect\Contracts\TenantResolver;

/**
 * Default TenantResolver for single-tenant applications.
 * Always returns an empty string — no tenant isolation applied.
 */
final class NullTenantResolver implements TenantResolver
{
    public function currentIdentifier(): string
    {
        return '';
    }
}
