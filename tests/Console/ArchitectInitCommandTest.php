<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ArchitectInitCommandTest extends TestCase
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

        foreach (File::glob(database_path('migrations/*_create_architect_user_states_table.php')) as $migration) {
            File::delete($migration);
        }

        parent::tearDown();
    }

    public function test_first_time_init_writes_config_and_generates_migration_for_database_mode(): void
    {
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');

        $this->assertTrue($config['setup']['initialized']);
        $this->assertSame('database', $config['setup']['chosen']['persistence_mode']);
        $this->assertSame('database', $config['state']['mode']);

        $migrations = File::glob(database_path('migrations/*_create_architect_user_states_table.php'));
        $this->assertNotEmpty($migrations);
    }

    public function test_first_time_init_skips_migration_for_local_storage_mode(): void
    {
        $this->initializeWithDefaults('localStorage');

        $migrations = File::glob(database_path('migrations/*_create_architect_user_states_table.php'));
        $this->assertEmpty($migrations);
    }

    public function test_rerun_without_break_glass_rejects_hard_lock_change(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->assertExitCode(1);

        $config = require config_path('architect.php');
        $this->assertSame('database', $config['setup']['chosen']['persistence_mode']);
    }

    public function test_rerun_with_break_glass_allows_hard_lock_change(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --break-glass')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Type yes again to confirm this irreversible hard-lock override', 'yes')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');
        $this->assertSame('localStorage', $config['setup']['chosen']['persistence_mode']);
    }

    public function test_rerun_with_break_glass_cancelled_at_double_confirmation_does_not_write_config(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --break-glass')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Type yes again to confirm this irreversible hard-lock override', 'no')
            ->assertExitCode(2);

        $config = require config_path('architect.php');
        $this->assertSame('database', $config['setup']['chosen']['persistence_mode']);
    }

    public function test_rerun_without_force_reconfigure_rejects_soft_lock_change(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'admin')
            ->assertExitCode(1);

        $config = require config_path('architect.php');
        $this->assertSame('web', $config['setup']['chosen']['auth_guard']);
    }

    public function test_rerun_with_force_reconfigure_allows_soft_lock_change(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --force-reconfigure')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'admin')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');
        $this->assertSame('admin', $config['setup']['chosen']['auth_guard']);
    }

    public function test_writes_backup_of_previous_config_on_reinitialization(): void
    {
        $this->initializeWithDefaults('database');

        // The very first init already backs up the pristine published config
        // that setUp() copied into place, so one backup should already exist.
        $backupsBefore = File::glob(config_path('architect.php').'.bak-*');
        $this->assertCount(1, $backupsBefore);

        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --force-reconfigure')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'admin')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $backupsAfter = File::glob(config_path('architect.php').'.bak-*');
        $this->assertGreaterThan(count($backupsBefore), count($backupsAfter));
    }

    public function test_only_option_allows_specified_soft_lock_key_change(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --force-reconfigure --only=auth_guard')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'admin')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');
        $this->assertSame('admin', $config['setup']['chosen']['auth_guard']);
    }

    public function test_only_option_rejects_soft_lock_change_not_in_allow_list(): void
    {
        $this->initializeWithDefaults('database');
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --force-reconfigure --only=state_connection')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'admin')
            ->assertExitCode(1);

        $config = require config_path('architect.php');
        $this->assertSame('web', $config['setup']['chosen']['auth_guard']);
    }

    private function initializeWithDefaults(string $persistenceMode): void
    {
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init')
            ->expectsChoice('Select persistence mode', $persistenceMode, ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);
    }

    /**
     * @return list<string>
     */
    private function dbConnectionChoices(): array
    {
        return array_merge(['default'], array_keys((array) config('database.connections', [])));
    }
}
