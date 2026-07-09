<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Concerns\HasStorageContract;
use Entelechy\Architect\Console\Concerns\WritesArchitectConfig;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File as FileFacade;
use Throwable;

/**
 * Scans the definition classes under config('architect.storage_contracts.discovery.paths')
 * for a static definition(): ArchitectTableDefinition method, and walks
 * dataModelClass -> modelClass() to build an informational Model FQCN =>
 * [file-upload column names] map (columns declared Column::make(...)->type('upload'),
 * see STORAGE_CONTRACTS_PLAN.md "Model-attached file column discovery").
 *
 * The result is written into config as a non-authoritative cache for
 * inspection/tooling only — the model's own booted() call (HasStorageContract)
 * remains the source of truth at runtime, this command never changes model
 * behaviour by itself.
 */
class ArchitectStorageDiscoverCommand extends Command
{
    use WritesArchitectConfig;

    protected $signature = 'architect:storage:discover';

    protected $description = 'Scan configured definition classes for FileUpload columns and HasStorageContract usage.';

    public function handle(Filesystem $files): int
    {
        /** @var list<string> $paths */
        $paths = (array) config('architect.storage_contracts.discovery.paths', []);

        if ($paths === []) {
            $this->error(
                'config(\'architect.storage_contracts.discovery.paths\') is empty. '.
                'Set it explicitly to the directories containing your *Definition classes '.
                '(e.g. app/Modules/**/Components/Tables) before running this command.'
            );

            return self::FAILURE;
        }

        $definitionClasses = $this->findDefinitionClasses($paths);

        /** @var array<class-string, list<string>> $discovered */
        $discovered = [];
        $storageContractModels = [];

        foreach ($definitionClasses as $definitionClass) {
            $result = $this->inspectDefinition($definitionClass);

            if ($result === null) {
                continue;
            }

            [$modelClass, $fileColumns] = $result;

            if ($fileColumns !== []) {
                $discovered[$modelClass] = array_values(array_unique(array_merge($discovered[$modelClass] ?? [], $fileColumns)));
            }

            if (in_array(HasStorageContract::class, class_uses_recursive($modelClass), true)) {
                // Eloquent only runs a model's booted() hook (where
                // storageContract()/fileRetentionContract() are assigned)
                // lazily, on first instantiation — force it here so the
                // getters below reflect the model's real declared contract
                // rather than an empty/default value.
                new $modelClass;

                $storageContractModels[$modelClass] = [
                    'storage_contract' => $modelClass::getStorageContractKey(),
                    'file_retention_contract' => $modelClass::getFileRetentionContractKey(),
                    'file_columns' => $discovered[$modelClass] ?? [],
                ];
            }
        }

        $this->printReport($discovered, $storageContractModels);

        $configPath = config_path('architect.php');
        if ($files->exists($configPath)) {
            /** @var array<string, mixed> $existing */
            $existing = require $configPath;
            $existing['storage_contracts']['discovery']['paths'] = $paths;
            $existing['storage_contracts']['discovery']['discovered_file_columns'] = $discovered;

            $this->writeConfig($files, $configPath, $existing);
            $this->info('Cached discovery results in config/architect.php (storage_contracts.discovery.discovered_file_columns).');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $paths
     * @return list<class-string>
     */
    private function findDefinitionClasses(array $paths): array
    {
        $classes = [];

        foreach ($paths as $path) {
            if (! FileFacade::isDirectory($path)) {
                $this->warn("Discovery path does not exist, skipping: {$path}");

                continue;
            }

            foreach (FileFacade::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = $this->extractFqcnFromFile($file->getPathname());

                if ($fqcn === null || ! class_exists($fqcn) || ! method_exists($fqcn, 'definition')) {
                    continue;
                }

                $classes[] = $fqcn;
            }
        }

        return $classes;
    }

    /**
     * @param  class-string  $definitionClass
     * @return array{0: class-string, 1: list<string>}|null
     */
    private function inspectDefinition(string $definitionClass): ?array
    {
        try {
            $definition = $definitionClass::definition();
        } catch (Throwable $e) {
            $this->warn("Skipping {$definitionClass}: definition() threw ".$e->getMessage());

            return null;
        }

        // definition() is a plain, untyped dynamic call on a class we only
        // discovered at runtime — no @var hint here on purpose, this check
        // is the real runtime guard against a discovered class whose
        // definition() doesn't actually return what the convention expects.
        if (! $definition instanceof ArchitectTableDefinition) {
            return null;
        }

        $dataModelClass = $definition->dataModelClass;

        if (! class_exists($dataModelClass) || ! is_a($dataModelClass, ArchitectDataModel::class, true)) {
            return null;
        }

        try {
            $dataModel = app($dataModelClass);
            $modelClass = $dataModel->modelClass();
        } catch (Throwable $e) {
            $this->warn("Skipping {$definitionClass}: could not resolve modelClass() (".$e->getMessage().')');

            return null;
        }

        if (! class_exists($modelClass)) {
            return null;
        }

        $fileColumns = [];
        foreach ($definition->columns as $column) {
            if ($column->getType() === 'upload') {
                $fileColumns[] = $column->getKey();
            }
        }

        /** @var class-string $modelClass */
        return [$modelClass, $fileColumns];
    }

    /**
     * Extracts the fully-qualified class name declared in a PHP file via
     * tokenizing rather than require\/include — discovery must never execute
     * arbitrary host-app file side effects just to learn a class name.
     */
    private function extractFqcnFromFile(string $path): ?string
    {
        $contents = FileFacade::get($path);
        $tokens = token_get_all($contents);
        $count = count($tokens);

        $namespace = '';
        $class = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $previous = $this->previousSignificantToken($tokens, $i);
                if (is_array($previous) && $previous[0] === T_DOUBLE_COLON) {
                    // `Foo::class` constant usage, not a class declaration.
                    continue;
                }

                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        if ($class === null) {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$class : $class;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function previousSignificantToken(array $tokens, int $index): mixed
    {
        for ($k = $index - 1; $k >= 0; $k--) {
            if (is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                continue;
            }

            return $tokens[$k];
        }

        return null;
    }

    /**
     * @param  array<class-string, list<string>>  $discovered
     * @param  array<class-string, array{storage_contract: string, file_retention_contract: string, file_columns: list<string>}>  $storageContractModels
     */
    private function printReport(array $discovered, array $storageContractModels): void
    {
        $this->newLine();

        if ($discovered === []) {
            $this->line('No FileUpload columns discovered.');
        } else {
            $this->info('Discovered model-attached file columns:');
            foreach ($discovered as $modelClass => $columns) {
                $this->line("  - {$modelClass}: ".implode(', ', $columns));
            }
        }

        $this->newLine();

        if ($storageContractModels === []) {
            $this->line('No models using HasStorageContract were found among discovered models.');

            return;
        }

        $this->info('Models using HasStorageContract:');
        foreach ($storageContractModels as $modelClass => $info) {
            $this->line("  - {$modelClass}: storage_contract={$info['storage_contract']}, file_retention_contract={$info['file_retention_contract']}");
        }
    }
}
