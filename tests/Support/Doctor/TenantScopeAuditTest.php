<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Doctor;

use Entelechy\Architect\Support\Doctor\TenantScopeAuditor;
use Entelechy\Architect\Tests\Fixtures\Doctor\CompliantDataModel;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 4 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): guards against
 * a baseQuery() override that never calls parent::baseQuery(), silently
 * bypassing tenant scoping and connection switching for that model — the
 * exact mistake shape found in this package's own now-corrected docs
 * examples.
 *
 * The actual audit logic lives in Support\Doctor\TenantScopeAuditor so
 * `php artisan architect:doctor` can run the identical check on demand.
 */
class TenantScopeAuditTest extends TestCase
{
    public function test_flags_a_base_query_override_that_never_calls_parent(): void
    {
        config()->set('architect.tenant.discovery.paths', [__DIR__.'/../../Fixtures/Doctor']);

        $findings = (new TenantScopeAuditor)->findings();

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('NonCompliantDataModel::baseQuery()', $findings[0]);
    }

    public function test_does_not_flag_a_base_query_override_that_calls_parent(): void
    {
        config()->set('architect.tenant.discovery.paths', [__DIR__.'/../../Fixtures/Doctor']);

        $findings = (new TenantScopeAuditor)->findings();

        foreach ($findings as $finding) {
            $this->assertFalse(str_starts_with($finding, CompliantDataModel::class.'::baseQuery()'));
        }
    }

    public function test_skips_the_check_entirely_when_discovery_paths_is_empty(): void
    {
        config()->set('architect.tenant.discovery.paths', []);

        $this->assertSame([], (new TenantScopeAuditor)->findings());
    }
}
