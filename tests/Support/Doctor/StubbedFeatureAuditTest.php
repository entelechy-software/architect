<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Doctor;

use Entelechy\Architect\Support\Doctor\StubbedFeatureAuditor;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 2 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): guards against
 * the "KNOWN GAP (tracked, not yet wired)" disclosures added during the
 * Phase 2 wiring audit being silently deleted (e.g. by a refactor that
 * assumes the method must already work) without the underlying gap
 * actually being fixed.
 *
 * The actual audit logic lives in Support\Doctor\StubbedFeatureAuditor so
 * `php artisan architect:doctor` can run the identical check on demand.
 */
class StubbedFeatureAuditTest extends TestCase
{
    public function test_all_currently_tracked_stub_disclosures_are_present(): void
    {
        $findings = (new StubbedFeatureAuditor)->findings();

        $this->assertSame([], $findings);
    }
}
