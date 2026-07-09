<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ArchitectStorageInitCommandTest extends TestCase
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

        foreach (File::glob(database_path('migrations/*_create_architect_storage_ledger_table.php')) as $migration) {
            File::delete($migration);
        }

        foreach (File::glob(database_path('migrations/*_create_architect_uploads_table.php')) as $migration) {
            File::delete($migration);
        }

        $docPath = base_path('docs/storage-contracts.html');
        if (File::exists($docPath)) {
            File::delete($docPath);
        }

        parent::tearDown();
    }

    public function test_first_time_setup_enables_both_contracts_and_writes_everything(): void
    {
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:storage:init')
            ->expectsConfirmation('Enable Storage Contracts (DB record lifecycle: Archived -> Retired -> Cold Storage -> Purged)?', 'yes')
            ->expectsChoice('What would you like to do?', 'Add a contract', ['Add a contract'])
            ->expectsQuestion('Contract name (e.g. "finance")', 'finance')
            ->expectsQuestion('Archived duration (e.g. "2 years") — how long after soft-delete before moving to Retired', '2 years')
            ->expectsQuestion('Retired duration (e.g. "1 year") — how long Retired before moving to Cold Storage', '1 year')
            ->expectsQuestion('Cold Storage duration (e.g. "1 year") — how long in Cold Storage before Purge (blank = never)', '1 year')
            ->expectsChoice('What would you like to do?', 'Continue', ['Add a contract', 'Edit a contract', 'Remove a contract', 'Continue'])
            ->expectsChoice('Which contract is the project-wide default?', 'finance', ['finance'])
            ->expectsQuestion('Filesystem disk for Cold Storage (a disk name from config/filesystems.php)', 'glacier')
            ->expectsQuestion('Storage ledger table name', 'architect_storage_ledger')
            ->expectsChoice('Storage ledger DB connection', 'default', $dbChoices)
            ->expectsConfirmation('Enable File Retention (uploaded/attached files: Inactive -> Soft-deleted -> Permanently removed)?', 'yes')
            ->expectsChoice('What would you like to do?', 'Add a contract', ['Add a contract'])
            ->expectsQuestion('Contract name (e.g. "finance")', 'standard-files')
            ->expectsQuestion('Inactive duration (e.g. "1 year") — how long unaccessed before marking Soft-deleted', '1 year')
            ->expectsQuestion('Purge duration (e.g. "6 months") — how long Soft-deleted before permanent removal (blank = never)', '6 months')
            ->expectsChoice('What would you like to do?', 'Continue', ['Add a contract', 'Edit a contract', 'Remove a contract', 'Continue'])
            ->expectsChoice('Which file-retention contract is the project-wide default?', 'standard-files', ['standard-files'])
            ->expectsQuestion('Uploads ledger table name', 'architect_uploads')
            ->expectsChoice('Uploads ledger DB connection', 'default', $dbChoices)
            ->expectsConfirmation('Generate the human-readable reference doc at docs/storage-contracts.html?', 'yes')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');

        $this->assertTrue($config['storage_contracts']['enabled']);
        $this->assertSame('finance', $config['storage_contracts']['default_contract']);
        $this->assertSame('glacier', $config['storage_contracts']['cold_disk']);
        $this->assertSame(
            ['archived' => '2 years', 'retired' => '1 year', 'cold_storage' => '1 year'],
            $config['storage_contracts']['contracts']['finance']
        );
        $this->assertTrue($config['storage_contracts']['reference_doc']['enabled']);

        $this->assertTrue($config['file_retention']['enabled']);
        $this->assertSame('standard-files', $config['file_retention']['default_contract']);
        $this->assertSame(
            ['inactive' => '1 year', 'purge' => '6 months'],
            $config['file_retention']['contracts']['standard-files']
        );

        $ledgerMigrations = File::glob(database_path('migrations/*_create_architect_storage_ledger_table.php'));
        $this->assertNotEmpty($ledgerMigrations);
        $this->assertStringContainsString('model_type', File::get($ledgerMigrations[0]));

        $uploadsMigrations = File::glob(database_path('migrations/*_create_architect_uploads_table.php'));
        $this->assertNotEmpty($uploadsMigrations);
        $this->assertStringContainsString('last_accessed_at', File::get($uploadsMigrations[0]));

        $docPath = base_path('docs/storage-contracts.html');
        $this->assertFileExists($docPath);
        $this->assertStringContainsString('finance', File::get($docPath));
        $this->assertStringContainsString('standard-files', File::get($docPath));
    }

    public function test_declining_both_features_writes_disabled_config_with_no_migrations(): void
    {
        $this->artisan('architect:storage:init')
            ->expectsConfirmation('Enable Storage Contracts (DB record lifecycle: Archived -> Retired -> Cold Storage -> Purged)?', 'no')
            ->expectsConfirmation('Enable File Retention (uploaded/attached files: Inactive -> Soft-deleted -> Permanently removed)?', 'no')
            ->expectsConfirmation('Generate the human-readable reference doc at docs/storage-contracts.html?', 'no')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');

        $this->assertFalse($config['storage_contracts']['enabled']);
        $this->assertFalse($config['file_retention']['enabled']);
        $this->assertFalse($config['storage_contracts']['reference_doc']['enabled']);

        $this->assertEmpty(File::glob(database_path('migrations/*_create_architect_storage_ledger_table.php')));
        $this->assertEmpty(File::glob(database_path('migrations/*_create_architect_uploads_table.php')));
        $this->assertFileDoesNotExist(base_path('docs/storage-contracts.html'));
    }

    public function test_cancelling_the_final_confirmation_does_not_write_config(): void
    {
        $originalConfig = File::get(config_path('architect.php'));

        $this->artisan('architect:storage:init')
            ->expectsConfirmation('Enable Storage Contracts (DB record lifecycle: Archived -> Retired -> Cold Storage -> Purged)?', 'no')
            ->expectsConfirmation('Enable File Retention (uploaded/attached files: Inactive -> Soft-deleted -> Permanently removed)?', 'no')
            ->expectsConfirmation('Generate the human-readable reference doc at docs/storage-contracts.html?', 'no')
            ->expectsConfirmation('Write these values to config/architect.php?', 'no')
            ->assertExitCode(2);

        $this->assertSame($originalConfig, File::get(config_path('architect.php')));
    }

    private function dbConnectionChoices(): array
    {
        return array_merge(['default'], array_keys((array) config('database.connections', [])));
    }
}
