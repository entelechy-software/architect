<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Tenancy;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Tenancy\TenantContext;

/**
 * Test-only TenantResolver returning a fixed, swappable TenantContext —
 * lets a test simulate "the current request belongs to tenant X" without
 * standing up real tenant-identification middleware.
 */
final class FakeTenantResolver implements TenantResolver
{
    public function __construct(private readonly TenantContext $context) {}

    public function resolve(): TenantContext
    {
        return $this->context;
    }
}
