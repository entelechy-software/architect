<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Support\Doctor;

use Entelechy\Architect\Support\Doctor\DefinitionInterfaceAuditor;
use Entelechy\Architect\Tests\Fixtures\Discovery\SampleDiscoveryLegacyBuildDefinition;
use Entelechy\Architect\Tests\TestCase;

/**
 * Phase 3.2/3.4 audit test (see ARCHITECT_IMPROVEMENT_PLAN.md): every
 * host-app class exposing a static definition()/build() method must
 * implement its subsystem's Provides*Definition marker interface —
 * this is the mechanical enforcement that replaces the old
 * method_exists() duck-typing.
 *
 * The actual audit logic lives in Support\Doctor\DefinitionInterfaceAuditor
 * so `php artisan architect:doctor` can run the identical check on demand.
 */
class DefinitionInterfaceAuditorTest extends TestCase
{
    public function test_flags_a_definition_class_missing_its_marker_interface(): void
    {
        config()->set('architect.doctor.discovery.paths', [__DIR__.'/../../Fixtures/Discovery']);

        $findings = (new DefinitionInterfaceAuditor)->findings();

        $this->assertNotEmpty($findings);
        $this->assertStringContainsString(SampleDiscoveryLegacyBuildDefinition::class, implode("\n", $findings));
    }

    public function test_does_not_flag_a_definition_class_that_implements_its_marker_interface(): void
    {
        config()->set('architect.doctor.discovery.paths', [__DIR__.'/../../Fixtures/Discovery']);

        $findings = (new DefinitionInterfaceAuditor)->findings();
        $findingsText = implode("\n", $findings);

        $this->assertStringNotContainsString('SampleDiscoveryTableDefinition', $findingsText);
    }
}
