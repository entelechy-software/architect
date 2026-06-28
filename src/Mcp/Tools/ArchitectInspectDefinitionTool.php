<?php

declare(strict_types=1);

namespace Entelechy\Architect\Mcp\Tools;

use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectField;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;
use Throwable;

/**
 * Reflects on an Architect definition class and returns a human-readable
 * summary of its configuration (columns, fields, filters, permissions, etc.).
 */
class ArchitectInspectDefinitionTool extends Tool
{
    protected string $name = 'architect_inspect_definition';

    protected string $description = 'Reflect on an Architect definition class (TableDefinition, FormDefinition, etc.) and return a structured summary of its configuration. Pass the fully-qualified class name.';

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'fqcn' => $schema->string()->required()->description(
                'Fully-qualified class name of the definition, e.g. App\\Modules\\Activities\\Components\\Tables\\CommitteeTableDefinition'
            ),
        ];
    }

    public function handle(): Response
    {
        /** @var Request $request */
        $request = app('mcp.request');

        $fqcn = (string) $request->get('fqcn', '');

        if (! $this->isSafeClass($fqcn)) {
            return Response::error(
                'Class must belong to App\\, Entelechy\\, or the local packages/ namespace. '.
                'Arbitrary class names are not permitted for security reasons.'
            );
        }

        if (! class_exists($fqcn)) {
            return Response::error("Class not found: {$fqcn}. Ensure the class is autoloaded.");
        }

        try {
            /** @var class-string $safeClass */
            $safeClass = $fqcn;
            $summary = $this->inspect($safeClass);
        } catch (Throwable $e) {
            return Response::error("Failed to inspect {$fqcn}: {$e->getMessage()}");
        }

        return Response::json($summary);
    }

    /**
     * Only allow classes from known safe namespaces to prevent arbitrary code execution.
     */
    private function isSafeClass(string $fqcn): bool
    {
        if (! preg_match('/^[A-Za-z\\\\][A-Za-z0-9\\\\]*$/', $fqcn)) {
            return false;
        }

        $safeRoots = ['App\\', 'Entelechy\\'];

        foreach ($safeRoots as $root) {
            if (str_starts_with($fqcn, $root)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string  $fqcn
     * @return array<string, mixed>
     */
    private function inspect(string $fqcn): array
    {
        $reflection = new ReflectionClass($fqcn);

        // Attempt to call the standard static factory methods.
        $definition = null;

        if ($reflection->hasMethod('definition')) {
            $definition = $fqcn::definition();
        } elseif ($reflection->hasMethod('build')) {
            $instance = $reflection->newInstance();
            $definition = $reflection->getMethod('build')->invoke($instance);
        }

        $summary = [
            'class' => $fqcn,
            'short_name' => $reflection->getShortName(),
            'type' => $this->detectType($reflection->getShortName()),
            'definition' => null,
        ];

        if ($definition instanceof ArchitectTableDefinition) {
            $summary['definition'] = $this->summariseTable($definition);
        } elseif ($definition !== null) {
            // Generic fallback: expose public property names via reflection.
            $defReflection = new ReflectionClass($definition);
            $props = [];

            foreach ($defReflection->getProperties() as $prop) {
                if ($prop->isPublic() || $prop->isReadOnly()) {
                    $prop->setAccessible(true);
                    $value = $prop->getValue($definition);
                    $props[$prop->getName()] = is_scalar($value) ? $value : gettype($value);
                }
            }

            $summary['definition'] = $props;
        }

        return $summary;
    }

    private function detectType(string $shortName): string
    {
        if (str_ends_with($shortName, 'TableDefinition')) {
            return 'table';
        }

        if (str_ends_with($shortName, 'FormDefinition')) {
            return 'form';
        }

        if (str_ends_with($shortName, 'ContentDefinition')) {
            return 'content';
        }

        if (str_ends_with($shortName, 'TabsDefinition')) {
            return 'workspace-tabs';
        }

        if (str_ends_with($shortName, 'Navigator')) {
            return 'navigation';
        }

        return 'unknown';
    }

    /** @return array<string, mixed> */
    private function summariseTable(ArchitectTableDefinition $definition): array
    {
        $columns = array_map(
            static fn (Column $col): array => [
                'key' => $col->getKey(),
                'label' => $col->getLabel(),
                'sortable' => $col->isSortable(),
                'searchable' => $col->isSearchable(),
            ],
            $definition->columns
        );

        $fields = array_map(
            static fn (ArchitectField $f): array => [
                'key' => method_exists($f, 'getKey') ? $f->getKey() : '',
                'label' => $f->getLabel(),
                'type' => get_class($f),
                'required' => $f->isRequired(),
            ],
            $definition->fields
        );

        return [
            'title' => $definition->title,
            'model' => $definition->dataModelClass,
            'form_mode' => $definition->formMode,
            'columns' => $columns,
            'fields' => $fields,
            'permissions' => [
                'read' => $definition->permissions->read,
                'create' => $definition->permissions->create,
                'modify' => $definition->permissions->modify,
                'remove' => $definition->permissions->remove,
            ],
        ];
    }
}
