<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Console\Concerns\WritesArchitectConfig;
use Entelechy\Architect\Support\DurationParser;
use Entelechy\Architect\Support\StorageContractsReferenceDocGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * Interactive authoring command for Storage Contracts (DB record lifecycle:
 * Archived -> Retired -> Cold Storage -> Purged) and File Retention
 * (uploaded/attached files: Inactive -> Soft-deleted -> Permanently removed).
 *
 * See STORAGE_CONTRACTS_PLAN.md. Unlike ArchitectInitCommand, there is no
 * hard/soft lock system here — re-running loads existing contracts from
 * config and offers to add/edit/remove them, it never blindly overwrites.
 */
class ArchitectStorageInitCommand extends Command
{
    use WritesArchitectConfig;

    protected $signature = 'architect:storage:init
        {--no-migrations : Skip generating ledger migrations}';

    protected $description = 'Interactively configure Storage Contracts and File Retention.';

    public function handle(Filesystem $files): int
    {
        $configPath = config_path('architect.php');
        if (! $files->exists($configPath)) {
            $this->error('Could not find config/architect.php. Publish it first: php artisan vendor:publish --tag=architect-config');

            return self::FAILURE;
        }

        /** @var array<string, mixed> $existing */
        $existing = require $configPath;

        $storageContracts = $this->promptStorageContracts((array) ($existing['storage_contracts'] ?? []));
        $fileRetention = $this->promptFileRetention((array) ($existing['file_retention'] ?? []));

        $generateReferenceDoc = $this->confirm(
            'Generate the human-readable reference doc at docs/storage-contracts.html?',
            (bool) ($existing['storage_contracts']['reference_doc']['enabled'] ?? true)
        );
        $storageContracts['reference_doc'] = ['enabled' => $generateReferenceDoc];

        $this->printSummary($storageContracts, $fileRetention);

        if (! $this->confirm('Write these values to config/architect.php?', true)) {
            $this->warn('Storage Contracts setup cancelled.');

            return self::INVALID;
        }

        $existing['storage_contracts'] = $storageContracts;
        $existing['file_retention'] = $fileRetention;

        $this->writeConfig($files, $configPath, $existing);
        $this->info('Updated config/architect.php');

        if (! (bool) $this->option('no-migrations')) {
            if ((bool) $storageContracts['enabled']) {
                $path = $this->generateLedgerMigration(
                    $files,
                    (string) $storageContracts['ledger']['table'],
                    $storageContracts['ledger']['connection'],
                    $this->storageLedgerColumns()
                );
                $this->info('Generated migration: '.$path);
            }

            if ((bool) $fileRetention['enabled']) {
                $path = $this->generateLedgerMigration(
                    $files,
                    (string) $fileRetention['ledger']['table'],
                    $fileRetention['ledger']['connection'],
                    $this->uploadsLedgerColumns()
                );
                $this->info('Generated migration: '.$path);
            }
        }

        if ($generateReferenceDoc) {
            $docPath = (new StorageContractsReferenceDocGenerator)->generate($storageContracts, $fileRetention, $files);
            $this->info('Generated reference doc: '.$docPath);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function promptStorageContracts(array $existing): array
    {
        $enabled = $this->confirm(
            'Enable Storage Contracts (DB record lifecycle: Archived -> Retired -> Cold Storage -> Purged)?',
            (bool) ($existing['enabled'] ?? false)
        );

        /** @var array<string, array<string, string|null>> $contracts */
        $contracts = (array) ($existing['contracts'] ?? []);
        $coldDisk = $existing['cold_disk'] ?? null;
        $defaultContract = $existing['default_contract'] ?? null;

        if ($enabled) {
            $contracts = $this->manageContracts($contracts, [
                'archived' => 'Archived duration (e.g. "2 years") — how long after soft-delete before moving to Retired',
                'retired' => 'Retired duration (e.g. "1 year") — how long Retired before moving to Cold Storage',
                'cold_storage' => 'Cold Storage duration (e.g. "1 year") — how long in Cold Storage before Purge',
            ], ['cold_storage'], requireAtLeastOne: true);

            $defaultContract = $this->choiceString(
                'Which contract is the project-wide default?',
                array_keys($contracts),
                in_array($defaultContract, array_keys($contracts), true) ? $defaultContract : array_key_first($contracts)
            );

            $coldDisk = $this->askRequired(
                'Filesystem disk for Cold Storage (a disk name from config/filesystems.php)',
                is_string($coldDisk) && $coldDisk !== '' ? $coldDisk : null
            );
        }

        $ledgerTable = (string) ($existing['ledger']['table'] ?? 'architect_storage_ledger');
        $ledgerConnection = $existing['ledger']['connection'] ?? null;

        if ($enabled) {
            $ledgerTable = (string) $this->ask('Storage ledger table name', $ledgerTable);
            $ledgerConnection = $this->askConnection('Storage ledger DB connection', $ledgerConnection);
        }

        return [
            'enabled' => $enabled,
            'cold_disk' => $coldDisk,
            'default_contract' => $defaultContract,
            'contracts' => $contracts,
            'ledger' => [
                'connection' => $ledgerConnection,
                'table' => $ledgerTable,
            ],
            'reference_doc' => (array) ($existing['reference_doc'] ?? ['enabled' => false]),
            'discovery' => (array) ($existing['discovery'] ?? ['paths' => []]),
        ];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function promptFileRetention(array $existing): array
    {
        $enabled = $this->confirm(
            'Enable File Retention (uploaded/attached files: Inactive -> Soft-deleted -> Permanently removed)?',
            (bool) ($existing['enabled'] ?? false)
        );

        /** @var array<string, array<string, string|null>> $contracts */
        $contracts = (array) ($existing['contracts'] ?? []);
        $defaultContract = $existing['default_contract'] ?? null;

        if ($enabled) {
            $contracts = $this->manageContracts($contracts, [
                'inactive' => 'Inactive duration (e.g. "1 year") — how long unaccessed before marking Soft-deleted',
                'purge' => 'Purge duration (e.g. "6 months") — how long Soft-deleted before permanent removal',
            ], ['purge'], requireAtLeastOne: true);

            $defaultContract = $this->choiceString(
                'Which file-retention contract is the project-wide default?',
                array_keys($contracts),
                in_array($defaultContract, array_keys($contracts), true) ? $defaultContract : array_key_first($contracts)
            );
        }

        $ledgerTable = (string) ($existing['ledger']['table'] ?? 'architect_uploads');
        $ledgerConnection = $existing['ledger']['connection'] ?? null;

        if ($enabled) {
            $ledgerTable = (string) $this->ask('Uploads ledger table name', $ledgerTable);
            $ledgerConnection = $this->askConnection('Uploads ledger DB connection', $ledgerConnection);
        }

        return [
            'enabled' => $enabled,
            'default_contract' => $defaultContract,
            'contracts' => $contracts,
            'ledger' => [
                'connection' => $ledgerConnection,
                'table' => $ledgerTable,
            ],
        ];
    }

    /**
     * Interactive add/edit/remove loop over a set of named contracts, each
     * holding the same set of duration fields.
     *
     * @param  array<string, array<string, string|null>>  $contracts
     * @param  array<string, string>  $fieldPrompts  duration key => prompt label
     * @param  list<string>  $nullableFields  fields that may be left blank (null = no limit)
     * @return array<string, array<string, string|null>>
     */
    private function manageContracts(array $contracts, array $fieldPrompts, array $nullableFields, bool $requireAtLeastOne): array
    {
        while (true) {
            if ($contracts !== []) {
                $this->line('Current contracts:');
                foreach ($contracts as $name => $durations) {
                    $summary = implode(', ', array_map(
                        static fn (string $key, ?string $value): string => "{$key}=".($value ?? 'never'),
                        array_keys($durations),
                        array_values($durations)
                    ));
                    $this->line("  - {$name}: {$summary}");
                }
            }

            $actions = ['Add a contract'];
            if ($contracts !== []) {
                $actions[] = 'Edit a contract';
                $actions[] = 'Remove a contract';
                $actions[] = 'Continue';
            } elseif (! $requireAtLeastOne) {
                $actions[] = 'Continue';
            }

            $action = $this->choiceString('What would you like to do?', $actions, $contracts === [] ? 'Add a contract' : 'Continue');

            if ($action === 'Continue') {
                return $contracts;
            }

            if ($action === 'Add a contract') {
                $name = trim((string) $this->ask('Contract name (e.g. "finance")'));
                if ($name === '') {
                    $this->warn('Contract name cannot be blank.');

                    continue;
                }

                $contracts[$name] = $this->promptDurations($fieldPrompts, $nullableFields, $contracts[$name] ?? []);

                continue;
            }

            if ($action === 'Edit a contract') {
                $name = $this->choiceString('Which contract?', array_keys($contracts));
                $contracts[$name] = $this->promptDurations($fieldPrompts, $nullableFields, $contracts[$name]);

                continue;
            }

            // Remove a contract
            $name = $this->choiceString('Which contract?', array_keys($contracts));
            if ($this->confirm("Remove contract \"{$name}\"?", false)) {
                unset($contracts[$name]);
            }
        }
    }

    /**
     * @param  array<string, string>  $fieldPrompts
     * @param  list<string>  $nullableFields
     * @param  array<string, string|null>  $current
     * @return array<string, string|null>
     */
    private function promptDurations(array $fieldPrompts, array $nullableFields, array $current): array
    {
        $durations = [];

        foreach ($fieldPrompts as $key => $label) {
            $isNullable = in_array($key, $nullableFields, true);
            $default = $current[$key] ?? null;

            while (true) {
                $raw = $this->ask($label.($isNullable ? ' (blank = never)' : ''), $default);
                $value = ($raw === null || trim((string) $raw) === '') ? null : trim((string) $raw);

                if ($value === null && ! $isNullable) {
                    $this->warn('This duration is required.');

                    continue;
                }

                if ($value !== null) {
                    try {
                        DurationParser::cutoff($value);
                    } catch (InvalidArgumentException) {
                        $this->warn("\"{$value}\" doesn't look like a valid duration (e.g. \"2 years\", \"90 days\").");

                        continue;
                    }
                }

                $durations[$key] = $value;
                break;
            }
        }

        return $durations;
    }

    private function askRequired(string $label, ?string $default): string
    {
        do {
            $value = trim((string) $this->ask($label, $default));
        } while ($value === '');

        return $value;
    }

    private function askConnection(string $label, mixed $current): ?string
    {
        $choices = array_merge(['default'], array_keys((array) config('database.connections', [])));
        $currentChoice = is_string($current) && $current !== '' && in_array($current, $choices, true) ? $current : 'default';

        $chosen = $this->choiceString($label, $choices, $currentChoice);

        return $chosen === 'default' ? null : $chosen;
    }

    /**
     * choice() is typed to return array|string because it supports a
     * multi-select mode we never use here — this narrows it back to string
     * for every call site in this command instead of casting an array to
     * string (which PHPStan correctly flags as a real bug risk).
     *
     * @param  array<int|string, string>  $choices
     */
    private function choiceString(string $question, array $choices, string|int|null $default = null): string
    {
        $answer = $this->choice($question, $choices, $default);

        return is_array($answer) ? (string) reset($answer) : $answer;
    }

    /**
     * @param  array<string, mixed>  $storageContracts
     * @param  array<string, mixed>  $fileRetention
     */
    private function printSummary(array $storageContracts, array $fileRetention): void
    {
        $this->newLine();
        $this->info('Storage Contracts setup summary:');
        $this->line('  storage_contracts.enabled  : '.($storageContracts['enabled'] ? 'yes' : 'no'));
        if ($storageContracts['enabled']) {
            $this->line('  default contract           : '.$storageContracts['default_contract']);
            $this->line('  cold storage disk          : '.$storageContracts['cold_disk']);
            $this->line('  ledger table               : '.$storageContracts['ledger']['table']);
            $this->line('  ledger connection          : '.($storageContracts['ledger']['connection'] ?? 'default'));
        }
        $this->line('  file_retention.enabled     : '.($fileRetention['enabled'] ? 'yes' : 'no'));
        if ($fileRetention['enabled']) {
            $this->line('  default contract           : '.$fileRetention['default_contract']);
            $this->line('  ledger table               : '.$fileRetention['ledger']['table']);
            $this->line('  ledger connection          : '.($fileRetention['ledger']['connection'] ?? 'default'));
        }
        $this->newLine();
    }

    /**
     * @return list<array{name: string, type: string, nullable?: bool}>
     */
    private function storageLedgerColumns(): array
    {
        return [
            ['name' => 'model_type', 'type' => 'string'],
            ['name' => 'model_id', 'type' => 'unsignedBigInteger'],
            ['name' => 'contract_key', 'type' => 'string'],
            ['name' => 'stage', 'type' => 'string'],
            ['name' => 'archived_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'retired_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'cold_storage_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'purged_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'cold_disk', 'type' => 'string', 'nullable' => true],
            ['name' => 'cold_path', 'type' => 'string', 'nullable' => true],
        ];
    }

    /**
     * @return list<array{name: string, type: string, nullable?: bool}>
     */
    private function uploadsLedgerColumns(): array
    {
        return [
            ['name' => 'path', 'type' => 'string'],
            ['name' => 'disk', 'type' => 'string'],
            ['name' => 'contract_key', 'type' => 'string'],
            ['name' => 'stage', 'type' => 'string'],
            ['name' => 'last_accessed_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'soft_deleted_at', 'type' => 'timestamp', 'nullable' => true],
            ['name' => 'purged_at', 'type' => 'timestamp', 'nullable' => true],
        ];
    }

    /**
     * Generates a timestamped migration into the host app's
     * database/migrations, following the same shape as
     * ArchitectInitCommand::generateStateMigration() (optional $connection
     * property, unique index on the natural key). Column definitions are
     * simple enough across both ledger tables to share one renderer.
     *
     * @param  list<array{name: string, type: string, nullable?: bool}>  $columns
     */
    private function generateLedgerMigration(Filesystem $files, string $tableName, ?string $connection, array $columns): string
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

        $columnLines = implode("\n", array_map(
            static function (array $column): string {
                $line = '            $table->'.$column['type']."('".$column['name']."')";
                if ($column['nullable'] ?? false) {
                    $line .= '->nullable()';
                }

                return $line.';';
            },
            $columns
        ));

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
__COLUMNS__
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('__TABLE__');
    }
};
PHP;

        $content = str_replace(
            ['__CONNECTION_PROPERTY__', '__TABLE__', '__COLUMNS__'],
            [$connectionProperty, $tableName, $columnLines],
            $template
        );

        $files->put($path, $content);

        return $path;
    }
}
