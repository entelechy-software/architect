<?php

declare(strict_types=1);

namespace Entelechy\Architect\Mcp\Tools;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Generates ready-to-use Architect table scaffold code (TableDefinition + DataModel + migration)
 * from an entity name and a list of field definitions.
 */
class ArchitectScaffoldTableTool extends Tool
{
    protected string $name = 'architect_scaffold_table';

    protected string $description = 'Generate a complete Architect table scaffold: TableDefinition PHP class, DataModel PHP class, and database migration stub. Returns code strings, does not write files.';

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'module' => $schema->string()->required()->description(
                'Module name in PascalCase (e.g. Activities, Members, Finance).'
            ),
            'entity' => $schema->string()->required()->description(
                'Entity name in PascalCase singular (e.g. CommitteeRole, MembershipType).'
            ),
            'table_name' => $schema->string()->nullable()->description(
                'Database table name in snake_case (e.g. committee_roles). Inferred from entity name if omitted.'
            ),
            'fields' => $schema->array()->items($schema->object([
                'name' => $schema->string()->description('Field name in camelCase (e.g. memberName).'),
                'type' => $schema->string()->description('Field type: text | integer | decimal | date | datetime | checkbox | select | textarea | toggle | file.'),
                'label' => $schema->string()->description('Human-readable label.'),
                'required' => $schema->boolean()->description('Whether this field is required. Default true.'),
                'sortable' => $schema->boolean()->description('Whether the column is sortable. Default false.'),
            ]))->description('Fields to scaffold. Each becomes a column and form field.'),
        ];
    }

    public function handle(): Response
    {
        /** @var Request $request */
        $request = app('mcp.request');

        $module = (string) $request->get('module', '');
        $entity = (string) $request->get('entity', '');

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $module)) {
            return Response::error('module must be PascalCase alphanumeric (e.g. Activities).');
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $entity)) {
            return Response::error('entity must be PascalCase alphanumeric (e.g. CommitteeRole).');
        }

        $tableName = $request->get('table_name');
        $tableName = is_string($tableName) && $tableName !== ''
            ? $tableName
            : $this->toSnakeCase($entity).'s';

        /** @var array<int, array{name: string, type: string, label: string, required?: bool, sortable?: bool}> $fields */
        $fields = is_array($request->get('fields')) ? $request->get('fields') : [];

        $permModule = strtolower($module);
        $permEntity = $this->toSnakeCase($entity);

        return Response::json([
            'table_definition' => $this->generateTableDefinition($module, $entity, $tableName, $permModule, $permEntity, $fields),
            'data_model' => $this->generateDataModel($module, $entity, $tableName, $fields),
            'migration' => $this->generateMigration($tableName, $fields),
            'instructions' => [
                "Place TableDefinition in app/Modules/{$module}/Components/Tables/{$entity}TableDefinition.php",
                "Place DataModel in app/Modules/{$module}/Models/{$entity}TableModel.php",
                'Place migration in database/migrations/customer/',
                "Register the table: <x-architect definition-class=\"App\\\\Modules\\\\{$module}\\\\Components\\\\Tables\\\\{$entity}TableDefinition\" />",
            ],
        ]);
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, sortable?: bool}>  $fields
     */
    private function generateTableDefinition(
        string $module,
        string $entity,
        string $tableName,
        string $permModule,
        string $permEntity,
        array $fields
    ): string {
        $columns = '';
        $formFields = '';
        $fieldImports = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $fieldLabel = $field['label'];
            $sortable = ($field['sortable'] ?? false) ? "\n                    ->sortable()" : '';
            $columns .= "            Column::make('{$fieldName}')\n                    ->label('{$fieldLabel}'){$sortable},\n";

            $fieldClass = $this->fieldTypeToClass($field['type']);
            $fieldImports[$fieldClass] = "use Entelechy\\Architect\\Forms\\Fields\\{$fieldClass};";
            $required = ($field['required'] ?? true) ? "\n                    ->required()" : '';
            $formFields .= "            {$fieldClass}::make('{$fieldName}')\n                    ->label('{$fieldLabel}'){$required},\n";
        }

        $importBlock = implode("\n", array_unique(array_values($fieldImports)));

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$module}\\Components\Tables;

use Entelechy\Architect\Architect;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
{$importBlock}
use App\Modules\\{$module}\\Models\\{$entity}TableModel;

final class {$entity}TableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return Architect::make('table')
            ->title('{$entity}')
            ->model({$entity}TableModel::class)
            ->permissions(
                read: '{$permModule}_{$permEntity}.read',
                create: '{$permModule}_{$permEntity}.create',
                modify: '{$permModule}_{$permEntity}.modify',
                remove: '{$permModule}_{$permEntity}.remove',
            )
            ->formMode('slide-over')
            ->archivable()
            ->selectableRows()
            ->exportable()
            ->columns([
{$columns}        ])
            ->fields([
{$formFields}        ])
            ->build();
    }
}
PHP;
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, sortable?: bool}>  $fields
     */
    private function generateDataModel(string $module, string $entity, string $tableName, array $fields): string
    {
        $columns = implode(",\n        ", array_map(static fn (array $f): string => "'{$f['name']}'", $fields));

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Modules\\{$module}\Models;

use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @extends Model<{$entity}> */
class {$entity}TableModel extends Model implements ArchitectDataModel
{
    protected \$connection = 'customer';

    protected \$table = '{$tableName}';

    protected \$fillable = [
        {$columns},
    ];

    public static function viewAll(QueryContext \$ctx): Builder
    {
        return static::query()
            ->when(\$ctx->search, static function (Builder \$q, string \$search): void {
                \$q->where('name', 'like', "%{\$search}%");
            });
    }
}
PHP;
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, sortable?: bool}>  $fields
     */
    private function generateMigration(string $tableName, array $fields): string
    {
        $columnDefs = '';
        foreach ($fields as $field) {
            $col = $this->toSnakeCase($field['name']);
            $dbType = $this->fieldTypeToMigration($field['type']);
            $columnDefs .= "            \$table->{$dbType}('{$col}');\n";
        }

        $date = Carbon::now()->format('Y_m_d');
        $className = 'Create'.str_replace('_', '', ucwords($tableName, '_')).'Table';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'customer';
    }

    public function up(): void
    {
        Schema::connection('customer')->create('{$tableName}', static function (Blueprint \$table): void {
            \$table->id();
{$columnDefs}            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('customer')->dropIfExists('{$tableName}');
    }
};
PHP;
    }

    private function fieldTypeToClass(string $type): string
    {
        return match ($type) {
            'integer' => 'IntegerField',
            'decimal' => 'DecimalField',
            'date' => 'DateField',
            'datetime' => 'DateTimeField',
            'checkbox' => 'CheckboxField',
            'select' => 'SelectField',
            'textarea' => 'TextareaField',
            'toggle' => 'Toggle',
            'file' => 'FileUpload',
            default => 'TextField',
        };
    }

    private function fieldTypeToMigration(string $type): string
    {
        return match ($type) {
            'integer' => 'integer',
            'decimal' => 'decimal',
            'date' => 'date',
            'datetime' => 'dateTime',
            'checkbox', 'toggle' => 'boolean',
            'file' => 'string',
            default => 'string',
        };
    }

    private function toSnakeCase(string $input): string
    {
        $result = preg_replace('/([A-Z])/', '_$1', lcfirst($input));

        return strtolower($result ?? $input);
    }
}
