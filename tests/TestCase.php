<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests;

use Entelechy\Architect\ArchitectServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /** @param Application $app */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            ArchitectServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
