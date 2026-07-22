<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Console\Concerns\WritesArchitectConfig;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ArchitectInitCommand extends Command
{
    use WritesArchitectConfig;

    private const PACKAGE_CONFIG_PATH = __DIR__.'/../../../config/architect.php';

    private const LAYOUT_HEAD_START = '{{-- architect:start head --}}';

    private const LAYOUT_HEAD_END = '{{-- architect:end head --}}';

    private const LAYOUT_BODY_START = '{{-- architect:start body --}}';

    private const LAYOUT_BODY_END = '{{-- architect:end body --}}';

    protected $signature = 'architect:init
        {--force-reconfigure : Allow updating soft-locked setup options}
        {--break-glass : Allow changing hard-locked setup options}
        {--only= : Restrict soft-lock reconfiguration to a comma-separated list of keys (e.g. state_connection,auth_guard)}
        {--no-migration : Skip generating persistence migration when database mode is selected}
        {--layout= : Relative or absolute path to a Blade layout file to wire with Architect assets and toast manager}
        {--dry-run : Show the install actions without writing files}';

    protected $description = 'Initialize Architect project setup and lock foundational options.';

    protected function configure(): void
    {
        parent::configure();

        $this->setAliases(['architect:install']);
    }

    public function handle(Filesystem $files): int
    {
        $this->renderBanner();

        $this->ensureConfigPublished($files);

        $configPath = config_path('architect.php');
        if (! $files->exists($configPath) && ! $this->isDryRun()) {
            $this->error('Could not find config/architect.php. Publish it first: php artisan vendor:publish --tag=architect-config');

            return self::FAILURE;
        }

        /** @var array<string, mixed> $existing */
        $existing = $files->exists($configPath)
            ? require $configPath
            : require self::PACKAGE_CONFIG_PATH;

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
            $onlyOptionRaw = $this->option('only');
            $onlyOption = is_string($onlyOptionRaw) ? $onlyOptionRaw : '';
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

        if ($this->isDryRun()) {
            $this->warn('Dry run: no files will be written.');
        } elseif (! $this->confirm('Write these values to config/architect.php?', true)) {
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

        if ($this->isDryRun()) {
            $this->info('Dry run: would update config/architect.php');
        } else {
            $this->writeConfig($files, $configPath, $existing);
            $this->info('Updated config/architect.php');
        }

        if ($newChosen['persistence_mode'] === 'database' && ! (bool) $this->option('no-migration')) {
            if ($this->isDryRun()) {
                $this->info('Dry run: would generate migration: '.$this->plannedStateMigrationPath($newChosen['state_table']));
            } else {
                $migrationPath = $this->generateStateMigration(
                    $files,
                    $newChosen['state_table'],
                    $newChosen['state_connection'] ?? null
                );
                $this->info('Generated migration: '.$migrationPath);
            }
        }

        $this->ensureAssetsPublished($files);
        $this->offerLayoutWiring($files);

        return self::SUCCESS;
    }

    private function ensureConfigPublished(Filesystem $files): void
    {
        $configPath = config_path('architect.php');

        if ($files->exists($configPath)) {
            return;
        }

        if ($this->isDryRun()) {
            $this->info('Dry run: would publish Architect config to '.str_replace(base_path().'/', '', $configPath));

            return;
        }

        $this->info('Publishing Architect config...');

        $this->call('vendor:publish', [
            '--tag' => 'architect-config',
            '--force' => true,
        ]);
    }

    private function ensureAssetsPublished(Filesystem $files): void
    {
        $assetsPath = public_path('vendor/architect');
        $cssPath = $assetsPath.'/architect.css';
        $jsPath = $assetsPath.'/architect.js';

        if ($files->exists($cssPath) && $files->exists($jsPath)) {
            return;
        }

        if ($this->isDryRun()) {
            $this->info('Dry run: would publish Architect assets to '.str_replace(base_path().'/', '', $assetsPath));

            return;
        }

        $this->info('Publishing Architect assets...');

        $this->call('vendor:publish', [
            '--tag' => 'architect-assets',
            '--force' => true,
        ]);
    }

    private function offerLayoutWiring(Filesystem $files): void
    {
        $layoutOptionRaw = $this->option('layout');
        $layoutOption = is_string($layoutOptionRaw) ? trim($layoutOptionRaw) : '';

        if ($layoutOption !== '') {
            $this->wireLayout($files, $this->resolveLayoutPath($layoutOption));

            return;
        }

        $defaultLayout = 'resources/views/layouts/app.blade.php';
        $defaultLayoutPath = base_path($defaultLayout);

        if (! $files->exists($defaultLayoutPath)) {
            $this->warn("Skipping layout wiring: no layout found at {$defaultLayout}.");
            $this->line('Every Architect component renders via Livewire and requires a layout with @architectStyles (in <head>) and @architectScripts (before </body>) — without it, pages render completely unstyled and client-side-only elements (banners, toasts, modals) can render permanently visible.');
            $this->line('Create a layout and re-run with --layout=<path>, e.g.: php artisan architect:init --layout=resources/views/layouts/app.blade.php');
            $this->line('See the Styling Guide "Loading the Assets" section for a minimal layout example.');

            return;
        }

        if (! $this->confirm('Would you like Architect to wire a Blade layout with Architect styles, scripts, and toast manager?', true)) {
            return;
        }

        $layoutAnswer = $this->ask('Blade layout path', $defaultLayout);
        $layoutPath = is_string($layoutAnswer) && trim($layoutAnswer) !== ''
            ? $this->resolveLayoutPath(trim($layoutAnswer))
            : $defaultLayoutPath;

        $this->wireLayout($files, $layoutPath);
    }

    private function resolveLayoutPath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function wireLayout(Filesystem $files, string $layoutPath): void
    {
        if (! $files->exists($layoutPath)) {
            $this->warn('Skipping layout wiring: file not found at '.$layoutPath);

            return;
        }

        $contents = $files->get($layoutPath);

        if (
            str_contains($contents, '@architectStyles')
            && str_contains($contents, '@architectScripts')
            && str_contains($contents, 'architect-toast-manager')
        ) {
            $this->info('Architect layout hooks already exist in '.$layoutPath);

            return;
        }

        if (! preg_match('/<head\b[^>]*>/i', $contents, $headMatches, PREG_OFFSET_CAPTURE)) {
            $this->warn('Skipping layout wiring: could not find a <head> tag in '.$layoutPath);

            return;
        }

        if (preg_match_all('/<head\b[^>]*>/i', $contents) !== 1) {
            $this->warn('Skipping layout wiring: expected exactly one <head> tag in '.$layoutPath);

            return;
        }

        if (preg_match_all('/<\/body>/i', $contents) !== 1) {
            $this->warn('Skipping layout wiring: expected exactly one </body> tag in '.$layoutPath);

            return;
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $headBlock = $lineEnding.$lineEnding.implode($lineEnding, [
            self::LAYOUT_HEAD_START,
            '@architectStyles',
            self::LAYOUT_HEAD_END,
        ]);
        $bodyBlock = $lineEnding.$lineEnding.implode($lineEnding, [
            self::LAYOUT_BODY_START,
            '@architectScripts',
            '<livewire:architect-toast-manager />',
            self::LAYOUT_BODY_END,
        ]).$lineEnding;

        $updatedContents = preg_replace('/<head\b[^>]*>/i', '$0'.$headBlock, $contents, 1);
        if (! is_string($updatedContents)) {
            $this->warn('Skipping layout wiring: failed to update <head> block in '.$layoutPath);

            return;
        }

        $updatedContents = preg_replace('/<\/body>/i', $bodyBlock.'</body>', $updatedContents, 1);
        if (! is_string($updatedContents)) {
            $this->warn('Skipping layout wiring: failed to update </body> block in '.$layoutPath);

            return;
        }

        if ($this->isDryRun()) {
            $this->info('Dry run: would update Blade layout: '.$layoutPath);

            return;
        }

        $backupPath = $layoutPath.'.bak-'.now()->format('Y_m_d_His');
        if ($files->exists($backupPath)) {
            $backupPath .= '-'.substr(uniqid('', true), -6);
        }

        $files->copy($layoutPath, $backupPath);
        $files->put($layoutPath, $updatedContents);

        $this->info('Updated Blade layout: '.$layoutPath);
    }

    private function plannedStateMigrationPath(string $tableName): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $fileName = $timestamp.'_create_'.str_replace('-', '_', $tableName).'_table.php';

        return database_path('migrations/'.$fileName);
    }

    private function isDryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    private function renderBanner(): void
    {
        $this->line('<fg=cyan>     _             _     _ _            _   </>');
        $this->line('<fg=cyan>    / \   _ __ ___| |__ (_) |_ ___  ___| |_ </>');
        $this->line('<fg=cyan>   / _ \\ | \'__/ __| \'_ \\| | __/ _ \\/ __| __|</>');
        $this->line('<fg=cyan>  / ___ \\| | | (__| | | | | ||  __/ (__| |_ </>');
        $this->line('<fg=cyan> /_/   \_\\_|  \___|_| |_|_|_|\__\___|\___|\__|</>');
        $this->newLine();
        $this->line('<fg=gray>Architect installation wizard</>');
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array{
     *     persistence_mode: 'localStorage'|'database',
     *     tenancy_mode: 'single'|'multi',
     *     state_table: string,
     *     state_connection: string|null,
     *     auth_guard: string
     * }
     */
    private function promptForChoices(array $existing): array
    {
        $setupChosen = (array) (($existing['setup']['chosen'] ?? []) ?: []);
        $state = (array) (($existing['state'] ?? []) ?: []);

        $defaultPersistenceMode = $this->stringFromMixed(
            $setupChosen['persistence_mode'] ?? $state['mode'] ?? 'localStorage',
            'localStorage'
        );
        $defaultTenancyMode = $this->stringFromMixed($setupChosen['tenancy_mode'] ?? 'single', 'single');
        $defaultStateTable = $this->stringFromMixed(
            $setupChosen['state_table'] ?? $state['table'] ?? 'architect_user_states',
            'architect_user_states'
        );

        $persistenceModeAnswer = $this->choice(
            'Select persistence mode',
            ['localStorage', 'database'],
            $defaultPersistenceMode
        );
        $persistenceMode = is_string($persistenceModeAnswer) ? $persistenceModeAnswer : $defaultPersistenceMode;

        $tenancyModeAnswer = $this->choice(
            'Select tenancy mode',
            ['single', 'multi'],
            $defaultTenancyMode
        );
        $tenancyMode = is_string($tenancyModeAnswer) ? $tenancyModeAnswer : $defaultTenancyMode;

        $stateTableAnswer = $this->ask(
            'State table name (database mode)',
            $defaultStateTable
        );
        $stateTable = is_string($stateTableAnswer) && $stateTableAnswer !== ''
            ? $stateTableAnswer
            : $defaultStateTable;

        $dbConnections = array_keys((array) config('database.connections', []));
        $dbConnectionChoices = array_merge(['default'], $dbConnections);
        $currentConnection = $this->stringFromMixed(
            $setupChosen['state_connection'] ?? $state['connection'] ?? 'default',
            'default'
        );
        if ($currentConnection === '' || ! in_array($currentConnection, $dbConnectionChoices, true)) {
            $currentConnection = 'default';
        }
        $chosenConnectionAnswer = $this->choice(
            'State storage DB connection',
            $dbConnectionChoices,
            $currentConnection
        );
        $chosenConnection = is_string($chosenConnectionAnswer) ? $chosenConnectionAnswer : $currentConnection;

        $currentGuard = $this->stringFromMixed($setupChosen['auth_guard'] ?? $existing['auth_guard'] ?? 'web', 'web');
        $guardAnswer = $this->ask('Architect auth guard', $currentGuard);
        $guard = is_string($guardAnswer) && $guardAnswer !== '' ? $guardAnswer : $currentGuard;

        $persistenceMode = $this->normalizePersistenceMode($persistenceMode);
        $tenancyMode = $this->normalizeTenancyMode($tenancyMode);

        return [
            'persistence_mode' => $persistenceMode,
            'tenancy_mode' => $tenancyMode,
            'state_table' => $stateTable,
            'state_connection' => $chosenConnection === 'default' ? null : $chosenConnection,
            'auth_guard' => $guard,
        ];
    }

    private function stringFromMixed(mixed $value, string $fallback = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $fallback;
    }

    /**
     * @return 'localStorage'|'database'
     */
    private function normalizePersistenceMode(string $mode): string
    {
        return in_array($mode, ['localStorage', 'database'], true) ? $mode : 'localStorage';
    }

    /**
     * @return 'single'|'multi'
     */
    private function normalizeTenancyMode(string $mode): string
    {
        return in_array($mode, ['single', 'multi'], true) ? $mode : 'single';
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
