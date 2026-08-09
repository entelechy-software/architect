<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Doctor;

use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Support\Doctor\DocMaturityAuditor;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 5 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): every Forms
 * control registered as anything other than Maturity::Stable must have a
 * matching, up-to-date `<span class="maturity-badge maturity-{level}"
 * data-maturity-key="{key}">` badge somewhere under
 * package/docs/_documentation. Mirrors ControlMaturityAuditTest's shape.
 *
 * Note: package/docs/ is gitignored (disk-only), so this test is a no-op
 * pass in a fresh checkout/CI clone with no docs present — the auditor
 * intentionally treats a missing docs directory as "nothing to check",
 * not a failure (see DocMaturityAuditor::findings()'s early return).
 */
class DocMaturityAuditorTest extends TestCase
{
    public function test_every_non_stable_control_has_a_matching_up_to_date_doc_badge(): void
    {
        $registry = $this->app->make(ControlRegistry::class);
        $findings = (new DocMaturityAuditor($registry))->findings();

        $this->assertSame([], $findings, implode("\n", $findings));
    }
}
