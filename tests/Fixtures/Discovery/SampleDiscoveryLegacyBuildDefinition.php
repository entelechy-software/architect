<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Discovery;

/**
 * Fixture for DefinitionInterfaceAuditorTest: simulates a host-app
 * definition class that predates Phase 3's marker interfaces — exposes
 * the old build() convention and implements nothing. Should be flagged.
 */
class SampleDiscoveryLegacyBuildDefinition
{
    public static function build(): object
    {
        return new \stdClass;
    }
}
