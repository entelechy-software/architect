<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Tests\Fixtures\Discovery\SampleDiscoveryModel;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ArchitectStorageDiscoverCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(config_path());
        File::copy(__DIR__.'/../../config/architect.php', config_path('architect.php'));
    }

    protected function tearDown(): void
    {
        $configPath = config_path('architect.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        foreach (File::glob($configPath.'.bak-*') as $backup) {
            File::delete($backup);
        }

        parent::tearDown();
    }

    public function test_fails_fast_when_discovery_paths_is_empty(): void
    {
        config()->set('architect.storage_contracts.discovery.paths', []);

        $this->artisan('architect:storage:discover')->assertExitCode(1);
    }

    public function test_discovers_file_upload_columns_and_storage_contract_usage(): void
    {
        config()->set('architect.storage_contracts.discovery.paths', [
            __DIR__.'/../Fixtures/Discovery',
        ]);

        $this->artisan('architect:storage:discover')
            ->expectsOutputToContain('avatar_path, attachment_path')
            ->expectsOutputToContain('storage_contract=finance')
            ->assertExitCode(0);

        $config = require config_path('architect.php');

        $this->assertSame(
            ['avatar_path', 'attachment_path'],
            $config['storage_contracts']['discovery']['discovered_file_columns'][SampleDiscoveryModel::class]
        );
    }
}
