<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Support\Doctor\ControlMaturityAuditor;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 0 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): every Forms
 * control registered as Maturity::Stable must not reference an Alpine.js
 * component that doesn't exist. This is the mechanical signal used to
 * classify all 99 registered controls in the first place (a Blade view
 * with `x-data="architectXyz(...)"` but no matching
 * `Alpine.data('architectXyz', ...)` registration anywhere under
 * resources/js is provably non-functional) — this test turns that
 * one-off audit into a permanent regression guard: if a control is ever
 * re-labelled Stable (or a JS registration is ever removed/renamed)
 * without the other side being updated to match, this test fails.
 *
 * This intentionally only checks the named-component `x-data="architectXyz(...)"`
 * convention. Some Stable controls use inline Alpine object literals
 * (e.g. MarkdownEditor's `x-data="{ value: ... }"`) or plain native
 * HTML/Livewire binding with no Alpine at all — both are fine and simply
 * produce no match here, so nothing is asserted for them.
 *
 * The actual audit logic lives in Support\Doctor\ControlMaturityAuditor
 * so `php artisan architect:doctor` can run the identical check on demand.
 */
class ControlMaturityAuditTest extends TestCase
{
    public function test_every_stable_control_referencing_an_alpine_component_has_it_registered(): void
    {
        $registry = $this->app->make(ControlRegistry::class);
        $findings = (new ControlMaturityAuditor($registry))->findings();

        $this->assertSame([], $findings, implode("\n", $findings));
    }
}
