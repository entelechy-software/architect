<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Console\Concerns\WritesArchitectConfig;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ArchitectInitCommand extends Command
{
    use WritesArchitectConfig;

    protected $signature = 'architect:init
        {--force-reconfigure : Allow updating soft-locked setup options}
        {--break-glass : Allow changing hard-locked setup options}
        {--only= : Restrict soft-lock reconfiguration to a comma-separated list of keys (e.g. state_connection,auth_guard)}
        {--no-migration : Skip generating persistence migration when database mode is selected}';

    protected $description = 'Initialize Architect project setup and lock foundational options.';

    public function handle(Filesystem $files): int
    {
        $configPath = config_path('architect.php');
        if (! $files->exists($configPath)) {
            $this->error('Could not find config/architect.php. Publish it first: php artisan vendor:publish --tag=architect-config');

            return self::FAILURE;
        }

        /** @var array<string, mixed> $existing */
        $existing = require $configPath;

        $setup = (array) ($existing['setup'] ?? []);
        $initialized = (bool) ($setup['initialized'] ?? false);
        $hardLocks = array_values((array) (($setup['locks']['hard'] ?? []) ?: []));
        $softLocks = array_values((array) (($setup['locks']['soft'] ?? []) ?: []));
        $chosen = (array) ($setup['chosen'] ?? []);

        $newChosen = $this->promptForChoices($existing);

        if ($initialized) {
            $hardChanges = [];
            $softChanges = [];

            foreach ($hardLocks as $key) {
                if (($chosen[$key] ?? null) !== ($newChosen[$key] ?? null)) {
                    $hardChanges[] = $key;
                }
            }
            foreach ($softLocks as $key) {
                if (($chosen[$key] ?? null) !== ($newChosen[$key] ?? null)) {
                    $softChanges[] = $key;
                }
            }

            if ($hardChanges !== [] && ! (bool) $this->option('break-glass')) {
                $this->error('This project is initialized and hard-locked keys were changed: '.implode(', ', $hardChanges));
                $this->line('Re-run with --break-glass to override hard locks.');

                return self::FAILURE;
            }

            if ($softChanges !== [] && ! (bool) $this->option('force-reconfigure') && ! (bool) $this->option('break-glass')) {
                $this->error('This project is initialized and soft-locked keys were changed: '.implode(', ', $softChanges));
                $this->line('Re-run with --force-reconfigure to override soft locks.');

                return self::FAILURE;
            }

            // --only restricts the *scope* of an already-permitted soft-lock
            // reconfiguration to an explicit allow-list, so a broad
            // --force-reconfigure run cannot silently drift keys the caller
            // did not intend to touch.
            $onlyOption = (string) ($this->option('only') ?? '');
            if ($softChanges !== [] && $onlyOption !== '') {
                $allowed = array_filter(array_map('trim', explode(',', $onlyOption)));
                $disallowed = array_values(array_diff($softChanges, $allowed));

                if ($disallowed !== []) {
                    $this->error('The following soft-locked keys changed but are not included in --only: '.implode(', ', $disallowed));
                    $this->line('Add them to --only=... or omit --only to allow all soft-lock changes.');

                    return self::FAILURE;
                }
            }

            // Hard-lock overrides are destructive and irreversible (they can
            // orphan existing persisted state, e.g. renaming the state table
            // or switching tenancy mode). Require an explicit second
            // confirmation on top of --break-glass before proceeding.
            if ($hardChanges !== []) {
                $this->warn('You are about to override HARD-LOCKED setup options: '.implode(', ', $hardChanges));
                $this->warn('This is a break-glass operation and cannot be undone automatically.');

                if (! $this->confirm('Type yes again to confirm this irreversible hard-lock override', false)) {
                    $this->warn('Break-glass override cancelled.');

                    return self::INVALID;
                }
            }
        }

        $this->newLine();
        $this->info('Architect setup summary:');
        $this->line('  persistence mode : '.$newChosen['persistence_mode']);
        $this->line('  tenancy mode     : '.$newChosen['tenancy_mode']);
        $this->line('  state table      : '.$newChosen['state_table']);
        $this->line('  state connection : '.($newChosen['state_connection'] ?? 'default'));
        $this->line('  auth guard       : '.$newChosen['auth_guard']);
        $this->newLine();

        if (! $this->confirm('Write these values to config/architect.php?', true)) {
            $this->warn('Initialization cancelled.');

            return self::INVALID;
        }

        $existing['auth_guard'] = $newChosen['auth_guard'];
        $existing['state'] = array_merge((array) ($existing['state'] ?? []), [
            'mode' => $newChosen['persistence_mode'],
            'connection' => $newChosen['state_connection'],
            'table' => $newChosen['state_table'],
        ]);
        $existing['setup'] = [
            'initialized' => true,
            'version' => 1,
            'locks' => [
                'hard' => $hardLocks !== [] ? $hardLocks : ['persistence_mode', 'tenancy_mode', 'state_table'],
                'soft' => $softLocks !== [] ? $softLocks : ['state_connection', 'auth_guard'],
            ],
            'chosen' => $newChosen,
        ];

        $this->writeConfig($files, $configPath, $existing);
        $this->info('Updated config/architect.php');

        if ($newChosen['persistence_mode'] === 'database' && ! (bool) $this->option('no-migration')) {
            $migrationPath = $this->generateStateMigration(
                $files,
                (string) $newChosen['state_table'],
                $newChosen['state_connection'] ?? null
            );
            $this->info('Generated migration: '.$migrationPath);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function promptForChoices(array $existing): array
    {
        $setupChosen = (array) (($existing['setup']['chosen'] ?? []) ?: []);
        $state = (array) (($existing['state'] ?? []) ?: []);

        $persistenceMode = (string) $this->choice(
            'Select persistence mode',
            ['localStorage', 'database'],
            (string) ($setupChosen['persistence_mode'] ?? $state['mode'] ?? 'localStorage')
        );

        $tenancyMode = (string) $this->choice(
            'Select tenancy mode',
            ['single', 'multi'],
            (string) ($setupChosen['tenancy_mode'] ?? 'single')
        );

        $stateTable = (string) $this->ask(
            'State table name (database mode)',
            (string) ($setupChosen['state_table'] ?? $state['table'] ?? 'architect_user_states')
        );

        $dbConnections = array_keys((array) config('database.connections', []));
        $dbConnectionChoices = array_merge(['default'], $dbConnections);
        $currentConnection = (string) ($setupChosen['state_connection'] ?? $state['connection'] ?? 'default');
        if ($currentConnection === '' || ! in_array($currentConnection, $dbConnectionChoices, true)) {
            $currentConnection = 'default';
        }
        $chosenConnection = (string) $this->choice(
            'State storage DB connection',
            $dbConnectionChoices,
            $currentConnection
        );

        $currentGuard = (string) ($setupChosen['auth_guard'] ?? $existing['auth_guard'] ?? 'web');
        $guard = (string) $this->ask('Architect auth guard', $currentGuard);

        return [
            'persistence_mode' => $persistenceMode,
            'tenancy_mode' => $tenancyMode,
            'state_table' => $stateTable,
            'state_connection' => $chosenConnection === 'default' ? null : $chosenConnection,
            'auth_guard' => $guard,
        ];
    }

    private function generateStateMigration(Filesystem $files, string $tableName, ?string $connection): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $fileName = $timestamp.'_create_'.str_replace('-', '_', $tableName).'_table.php';
        $path = database_path('migrations/'.$fileName);

        if ($files->exists($path)) {
            return $path;
        }

        $connectionProperty = $connection !== null && $connection !== ''
            ? "    protected \$connection = '".addslashes($connection)."';\n\n"
            : '';

        $template = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
__CONNECTION_PROPERTY__    public function up(): void
    {
        Schema::create('__TABLE__', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('tenant_identifier')->default('')->index();
            $table->string('scope');
            $table->string('state_key');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_identifier', 'scope', 'state_key'], '__TABLE___uniq_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__TABLE__');
    }
};
PHP;

        $content = str_replace(
            ['__CONNECTION_PROPERTY__', '__TABLE__'],
            [$connectionProperty, $tableName],
            $template
        );

        $files->put($path, $content);

        return $path;
    }
}
