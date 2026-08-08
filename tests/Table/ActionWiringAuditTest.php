<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Support\Doctor\ActionWiringAuditor;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 0 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): guards against
 * the "half-wired feature" bug shape found repeatedly in this package —
 * a row/bulk action that Table\Livewire\Engine.php dispatches as a plain
 * browser event, where nothing on the client ever listens for it (a
 * silent no-op button, e.g. the original ->clonable()/->viewable() bugs).
 *
 * This does not (and cannot, without a much heavier static-analysis
 * pass) verify a listener does anything *useful* — only that one exists.
 * A listener that just shows a "not yet available" toast is considered
 * wired (honest stub), matching the precedent set by ->auditable().
 * Fully silent events (no listener at all, not even a toast) are what
 * this test exists to catch.
 *
 * The actual audit logic lives in Support\Doctor\ActionWiringAuditor so
 * `php artisan architect:doctor` can run the identical check on demand.
 */
class ActionWiringAuditTest extends TestCase
{
    public function test_every_client_dispatch_only_action_event_has_a_js_listener(): void
    {
        $findings = (new ActionWiringAuditor)->findings();

        $this->assertSame([], $findings, implode("\n", $findings));
    }
}
