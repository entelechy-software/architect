<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Scaffolds a new Architect CRUD table: a TableDefinition class, a plain
 * Eloquent model, a thin DataModel extending AbstractEloquentDataModel
 * (the storage layer's ArchitectDataModel adapter), and a migration stub.
 *
 * The Eloquent model and the DataModel are deliberately separate classes:
 * ArchitectDataModel::delete(int $id, ?string $reason): void is
 * incompatible with Eloquent Model::delete(): bool, so a single class
 * cannot be both.
 *
 * Example:
 *   php artisan make:architect-table Members --model=Member --module=Members
 */
class ArchitectMakeTableCommand extends Command
{
    protected $signature = 'make:architect-table
        {name : Base name for the table definition class, e.g. Members}
        {--model= : Singular entity name, e.g. Member (defaults to the singular of name)}
        {--module= : Module folder under app/Modules (defaults to name)}
        {--table= : Database table name (defaults to the snake_case plural of the model name)}
        {--force : Overwrite existing files}';

    protected $description = 'Scaffold an Architect CRUD table definition, data model, and migration.';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly((string) $this->argument('name'));
        $module = $this->option('module') !== null ? (string) $this->option('module') : $name;
        $model = $this->option('model') !== null ? (string) $this->option('model') : Str::singular($name);
        $table = (string) ($this->option('table') ?: Str::snake(Str::plural($model)));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $module)) {
            $this->components->error('--module must be PascalCase alphanumeric (e.g. Members).');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $model)) {
            $this->components->error('--model must be PascalCase alphanumeric (e.g. Member).');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $definitionPath = app_path("Modules/{$module}/Components/Tables/{$name}TableDefinition.php");
        $modelPath = app_path("Modules/{$module}/Models/{$model}.php");
        $dataModelPath = app_path("Modules/{$module}/Models/{$model}DataModel.php");
        $migrationPath = database_path('migrations/'.date('Y_m_d_His')."_create_{$table}_table.php");

        foreach ([$definitionPath, $modelPath, $dataModelPath] as $path) {
            if ($files->exists($path) && ! $force) {
                $this->components->error("File already exists: {$path} (use --force to overwrite)");

                return self::FAILURE;
            }
        }

        $files->ensureDirectoryExists(dirname($definitionPath));
        $files->ensureDirectoryExists(dirname($dataModelPath));
        $files->ensureDirectoryExists(dirname($migrationPath));

        $permBase = Str::snake($module);

        $files->put($definitionPath, $this->tableDefinitionStub($module, $name, $model, $permBase));
        $files->put($modelPath, $this->modelStub($module, $model, $table));
        $files->put($dataModelPath, $this->dataModelStub($module, $model));
        $files->put($migrationPath, $this->migrationStub($table));

        $this->components->info('Architect table scaffolded successfully.');
        $this->components->twoColumnDetail('Table definition', $definitionPath);
        $this->components->twoColumnDetail('Model', $modelPath);
        $this->components->twoColumnDetail('Data model', $dataModelPath);
        $this->components->twoColumnDetail('Migration', $migrationPath);
        $this->newLine();
        $this->line("Register it: <livewire:architect-engine definition-class=\"App\\Modules\\{$module}\\Components\\Tables\\{$name}TableDefinition\" />");

        return self::SUCCESS;
    }

    private function tableDefinitionStub(string $module, string $name, string $model, string $permBase): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Modules\\{$module}\\Components\Tables;

        use App\Modules\\{$module}\\Models\\{$model}DataModel;
        use Entelechy\Architect\Architect;
        use Entelechy\Architect\Table\ArchitectTableDefinition;
        use Entelechy\Architect\Table\Column;
        use Entelechy\Architect\Table\Contracts\ProvidesTableDefinition;

        final class {$name}TableDefinition implements ProvidesTableDefinition
        {
            public static function definition(): ArchitectTableDefinition
            {
                return Architect::make('table')
                    ->title('{$name}')
                    ->model({$model}DataModel::class)
                    ->permissions(
                        read: '{$permBase}.read',
                        create: '{$permBase}.create',
                        modify: '{$permBase}.modify',
                        remove: '{$permBase}.remove',
                    )
                    ->formMode(create: 'slide-over', modify: 'slide-over')
                    ->column(Column::make('id')->label('ID')->sortable())
                    ->column(Column::make('name')->label('Name')->type('text')->rules('required|string|max:255')->sortable()->searchable())
                    ->column(Column::make('created_at')->label('Created')->sortable())
                    ->paginate(25)
                    ->build();
            }
        }

        PHP;
    }

    private function modelStub(string $module, string $model, string $table): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Modules\\{$module}\\Models;

        use Illuminate\Database\Eloquent\Model;
        use Illuminate\Database\Eloquent\SoftDeletes;

        class {$model} extends Model
        {
            use SoftDeletes;

            protected \$table = '{$table}';

            protected \$guarded = [];
        }

        PHP;
    }

    private function dataModelStub(string $module, string $model): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Modules\\{$module}\\Models;

        use Entelechy\Architect\Table\AbstractEloquentDataModel;

        class {$model}DataModel extends AbstractEloquentDataModel
        {
            public function modelClass(): string
            {
                return {$model}::class;
            }
        }

        PHP;
    }

    private function migrationStub(string $table): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table): void {
                    \$table->id();
                    \$table->string('name');
                    \$table->timestamps();
                    \$table->softDeletes();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        };

        PHP;
    }
}
