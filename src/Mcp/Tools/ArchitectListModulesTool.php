<?php

declare(strict_types=1);

namespace Entelechy\Architect\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Lists all Architect definition classes registered in the host application's
 * Modules directory, grouped by type (table, form, content, panel, navigation).
 */
class ArchitectListModulesTool extends Tool
{
    protected string $name = 'architect_list_modules';

    protected string $description = 'List all Architect definition classes (TableDefinition, FormDefinition, ContentDefinition, etc.) found in app/Modules/. Optionally filter by definition type.';

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->nullable()->description(
                'Optional filter: table | form | content | panel | navigation. Omit to list all.'
            ),
        ];
    }

    public function handle(): Response
    {
        /** @var Request $request */
        $request = app('mcp.request');

        $typeFilter = $request->get('type');
        $typeFilter = is_string($typeFilter) ? strtolower(trim($typeFilter)) : null;

        $validTypes = ['table', 'form', 'content', 'panel', 'navigation'];

        if ($typeFilter !== null && ! in_array($typeFilter, $validTypes, true)) {
            return Response::error(
                'Invalid type filter. Valid values: '.implode(', ', $validTypes)
            );
        }

        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return Response::json(['definitions' => [], 'total' => 0, 'modules_path' => $modulesPath]);
        }

        $definitions = $this->scanForDefinitions($modulesPath, $typeFilter);

        return Response::json([
            'definitions' => $definitions,
            'total' => count($definitions),
            'type_filter' => $typeFilter,
        ]);
    }

    /**
     * Scan a directory tree for Architect definition PHP files.
     *
     * @return array<int, array{class: string, type: string, module: string, file: string}>
     */
    private function scanForDefinitions(string $basePath, ?string $typeFilter): array
    {
        $typePatterns = [
            'table' => 'TableDefinition',
            'form' => 'FormDefinition',
            'content' => 'ContentDefinition',
            'panel' => 'TabsDefinition',
            'navigation' => 'Navigator',
        ];

        if ($typeFilter !== null) {
            $typePatterns = array_filter(
                $typePatterns,
                static fn (string $k): bool => $k === $typeFilter,
                ARRAY_FILTER_USE_KEY
            );
        }

        $results = [];

        /** @var RecursiveIteratorIterator<RecursiveDirectoryIterator> $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filename = $file->getBasename('.php');

            foreach ($typePatterns as $type => $suffix) {
                if (! str_ends_with($filename, $suffix)) {
                    continue;
                }

                $relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
                $moduleName = $parts[0];

                // Derive FQCN from file path, assuming PSR-4 app/ → App\
                $fqcn = 'App\\Modules\\'.str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    $relativePath
                );

                $results[] = [
                    'class' => $fqcn,
                    'type' => $type,
                    'module' => $moduleName,
                    'file' => $file->getPathname(),
                ];
            }
        }

        usort($results, static fn (array $a, array $b): int => strcmp($a['module'], $b['module']));

        return $results;
    }
}
