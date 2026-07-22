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
        $layoutPath = resource_path('views/layouts/app.blade.php');

        if (File::exists($configPath)) {
            File::delete($configPath);
        }

        if (File::exists($layoutPath)) {
            File::delete($layoutPath);
        }

        if (File::isDirectory(dirname($layoutPath))) {
            File::deleteDirectory(resource_path('views'));
        }

        foreach (File::glob($configPath.'.bak-*') as $backup) {
            File::delete($backup);
        }

        foreach (File::glob($layoutPath.'.bak-*') as $backup) {
            File::delete($backup);
        }

        foreach (File::glob(database_path('migrations/*_create_architect_user_states_table.php')) as $migration) {
            File::delete($migration);
        }

        if (File::isDirectory(public_path('vendor/architect'))) {
            File::deleteDirectory(public_path('vendor/architect'));
        }

        parent::tearDown();
    }

    public function test_first_time_init_auto_publishes_missing_config(): void
    {
        File::delete(config_path('architect.php'));
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists(config_path('architect.php'));
    }

    public function test_dry_run_does_not_write_config_migration_or_layout(): void
    {
        File::delete(config_path('architect.php'));

        $layoutPath = resource_path('views/layouts/app.blade.php');
        File::ensureDirectoryExists(dirname($layoutPath));
        File::put($layoutPath, <<<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
</head>
<body>
    {{ $slot ?? '' }}
</body>
</html>
BLADE);
        $originalLayout = File::get($layoutPath);
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:init --dry-run --layout=resources/views/layouts/app.blade.php')
            ->expectsOutputToContain('Architect installation wizard')
            ->expectsChoice('Select persistence mode', 'database', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsOutputToContain('Dry run: would publish Architect config')
            ->expectsOutputToContain('Dry run: would update config/architect.php')
            ->expectsOutputToContain('Dry run: would generate migration:')
            ->expectsOutputToContain('Dry run: would update Blade layout:')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(config_path('architect.php'));
        $this->assertSame($originalLayout, File::get($layoutPath));
        $this->assertEmpty(File::glob(database_path('migrations/*_create_architect_user_states_table.php')));
        $this->assertEmpty(File::glob($layoutPath.'.bak-*'));
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

    public function test_first_time_init_can_wire_a_blade_layout_when_explicitly_requested(): void
    {
        $dbChoices = $this->dbConnectionChoices();
        $layoutPath = resource_path('views/layouts/app.blade.php');

        File::ensureDirectoryExists(dirname($layoutPath));
        File::put($layoutPath, <<<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
</head>
<body>
    {{ $slot ?? '' }}
</body>
</html>
BLADE);

        $this->artisan('architect:init --layout=resources/views/layouts/app.blade.php')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $layout = File::get($layoutPath);

        $this->assertStringContainsString('@architectStyles', $layout);
        $this->assertStringContainsString('@architectScripts', $layout);
        $this->assertStringContainsString('<livewire:architect-toast-manager />', $layout);
        $this->assertNotEmpty(File::glob($layoutPath.'.bak-*'));
    }

    public function test_install_alias_runs_the_same_wizard(): void
    {
        $dbChoices = $this->dbConnectionChoices();

        $this->artisan('architect:install')
            ->expectsOutputToContain('Architect installation wizard')
            ->expectsChoice('Select persistence mode', 'localStorage', ['localStorage', 'database'])
            ->expectsChoice('Select tenancy mode', 'single', ['single', 'multi'])
            ->expectsQuestion('State table name (database mode)', 'architect_user_states')
            ->expectsChoice('State storage DB connection', 'default', $dbChoices)
            ->expectsQuestion('Architect auth guard', 'web')
            ->expectsConfirmation('Write these values to config/architect.php?', 'yes')
            ->assertExitCode(0);

        $config = require config_path('architect.php');
        $this->assertTrue($config['setup']['initialized']);
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
