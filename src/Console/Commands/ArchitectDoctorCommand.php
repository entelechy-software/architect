<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Support\Doctor\ActionWiringAuditor;
use Entelechy\Architect\Support\Doctor\ControlMaturityAuditor;
use Entelechy\Architect\Support\Doctor\DefinitionInterfaceAuditor;
use Entelechy\Architect\Support\Doctor\DocMaturityAuditor;
use Entelechy\Architect\Support\Doctor\StubbedFeatureAuditor;
use Entelechy\Architect\Support\Doctor\TenantScopeAuditor;
use Illuminate\Console\Command;

/**
 * On-demand version of the Phase 0 audit tests (see
 * ARCHITECT_IMPROVEMENT_PLAN.md) — runs the same checks
 * ControlMaturityAuditTest and ActionWiringAuditTest run in CI, but as a
 * command any host app or CI pipeline can invoke directly without going
 * through PHPUnit.
 *
 * This is intentionally a thin shell around the shared
 * Support\Doctor\* auditor classes, not a reimplementation — new audits
 * (Phase 2/3/4 will add more, per the plan) should be added the same
 * way: a small stateless `findings(): list<string>` class under
 * Support\Doctor, exercised by both a PHPUnit test and this command.
 */
class ArchitectDoctorCommand extends Command
{
    protected $signature = 'architect:doctor';

    protected $description = 'Audit the installed Architect package for known half-wired/stub features.';

    public function handle(): int
    {
        $this->info('Running Architect diagnostics…');
        $this->newLine();

        $controlsClean = $this->report(
            'Forms control maturity (Stable controls must have working JS)',
            (new ControlMaturityAuditor($this->laravel->make(ControlRegistry::class)))->findings(),
        );

        $actionsClean = $this->report(
            'Table action wiring (client-dispatch-only actions must have a JS listener)',
            (new ActionWiringAuditor)->findings(),
        );

        $definitionInterfacesClean = $this->report(
            'Definition class interfaces (must implement a Provides*Definition marker interface)',
            (new DefinitionInterfaceAuditor)->findings(),
        );

        $tenantScopeClean = $this->report(
            'Tenant scoping (baseQuery() overrides must call parent::baseQuery() first)',
            (new TenantScopeAuditor)->findings(),
        );

        $stubbedFeaturesClean = $this->report(
            'Stubbed features (tracked known-gap disclosures must stay in place)',
            (new StubbedFeatureAuditor)->findings(),
        );

        $docMaturityClean = $this->report(
            'Doc maturity badges (non-Stable controls must have an up-to-date doc badge)',
            (new DocMaturityAuditor($this->laravel->make(ControlRegistry::class)))->findings(),
        );

        $clean = $controlsClean && $actionsClean && $definitionInterfacesClean && $tenantScopeClean
            && $stubbedFeaturesClean && $docMaturityClean;

        $this->newLine();

        if ($clean) {
            $this->info('No issues found.');

            return self::SUCCESS;
        }

        $this->error('Issues found — see above.');

        return self::FAILURE;
    }

    /** @param  list<string>  $findings */
    private function report(string $label, array $findings): bool
    {
        if ($findings === []) {
            $this->line("<fg=green>✓</> {$label}");

            return true;
        }

        $this->line("<fg=red>✗</> {$label}");

        foreach ($findings as $finding) {
            $this->line("    - {$finding}");
        }

        return false;
    }
}
