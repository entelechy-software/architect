<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Tests\TestCase;

class ArchitectSetupStatusCommandTest extends TestCase
{
    public function test_reports_not_initialized_by_default(): void
    {
        $this->artisan('architect:setup:status')
            ->expectsOutputToContain('initialized : no')
            ->assertExitCode(0);
    }
}
