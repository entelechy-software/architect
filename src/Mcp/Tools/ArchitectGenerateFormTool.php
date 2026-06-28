<?php

declare(strict_types=1);

namespace Entelechy\Architect\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Generates a ready-to-use Architect FormBuilder PHP stub from a list of
 * form fields specified by the developer or an AI assistant.
 */
class ArchitectGenerateFormTool extends Tool
{
    protected string $name = 'architect_generate_form';

    protected string $description = 'Generate a complete Architect FormBuilder PHP stub from a list of field specifications. Returns a PHP class string, does not write files.';

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'form_key' => $schema->string()->required()->description('Unique key for this form, kebab-case (e.g. member-profile, venue-booking).'),
            'class_name' => $schema->string()->required()->description('PHP class name for the definition class, PascalCase (e.g. MemberProfileFormDefinition).'),
            'namespace' => $schema->string()->nullable()->description('PHP namespace for the class (e.g. App\\Modules\\Members\\Components\\Forms). Defaults to App\\Components\\Forms.'),
            'title' => $schema->string()->nullable()->description('Human-readable form title shown in the UI.'),
            'save_route' => $schema->string()->nullable()->description('Named route to redirect to after successful save.'),
            'fields' => $schema->array()->items($schema->object([
                'name' => $schema->string()->description('Field name in camelCase.'),
                'type' => $schema->string()->description('Field type: text | integer | decimal | date | datetime | checkbox | select | textarea | toggle | file | richtext | tags | hidden | display.'),
                'label' => $schema->string()->description('Human-readable label.'),
                'required' => $schema->boolean()->description('Whether the field is required. Default true.'),
                'options' => $schema->array()->description('For select/radio/checkboxlist: array of {value, label} pairs. Omit to leave as placeholder.'),
            ]))->required()->description('List of fields in declaration order.'),
        ];
    }

    public function handle(): Response
    {
        /** @var Request $request */
        $request = app('mcp.request');

        $formKey = (string) $request->get('form_key', '');
        $className = (string) $request->get('class_name', '');

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $formKey)) {
            return Response::error('form_key must be kebab-case (e.g. member-profile).');
        }

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $className)) {
            return Response::error('class_name must be PascalCase (e.g. MemberProfileFormDefinition).');
        }

        $namespace = (string) ($request->get('namespace') ?? 'App\\Components\\Forms');
        $title = (string) ($request->get('title') ?? ucwords(str_replace('-', ' ', $formKey)));
        $saveRoute = is_string($request->get('save_route')) ? $request->get('save_route') : null;

        /** @var array<int, array{name: string, type: string, label: string, required?: bool, options?: array<int, array{value: string, label: string}>}> $fields */
        $fields = is_array($request->get('fields')) ? $request->get('fields') : [];

        if (empty($fields)) {
            return Response::error('At least one field is required.');
        }

        $code = $this->generate($formKey, $className, $namespace, $title, $saveRoute, $fields);

        return Response::json([
            'class' => $code,
            'file_path' => str_replace('\\', '/', $namespace).'/'.$className.'.php',
            'instructions' => [
                'Place this class at app/'.str_replace(['App/', '\\'], ['', '/'], $namespace)."/{$className}.php",
                "Mount with: <livewire:architect-form-engine definition-class=\"{$namespace}\\\\{$className}\" />",
                "Or in a Blade view: <x-architect definition-class=\"{$namespace}\\\\{$className}\" />",
            ],
        ]);
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, options?: array<int, array{value: string, label: string}>}>  $fields
     */
    private function generate(
        string $formKey,
        string $className,
        string $namespace,
        string $title,
        ?string $saveRoute,
        array $fields
    ): string {
        $imports = $this->buildImports($fields);
        $fieldBlocks = $this->buildFields($fields);
        $saveUsing = $this->buildSaveUsing($fields);
        $redirect = $saveRoute !== null
            ? "\n            ->redirectAfterSave(route('{$saveRoute}'))"
            : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Entelechy\Architect\Architect;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Illuminate\Support\Arr;
{$imports}

final class {$className}
{
    public static function definition(): ArchitectFormDefinition
    {
        return Architect::form('{$formKey}')
            ->title('{$title}')
            ->structure([
{$fieldBlocks}
            ])
            ->saveUsing(static function (array \$data): void {
{$saveUsing}
            }){$redirect}
            ->build();
    }
}
PHP;
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, options?: array<int, array{value: string, label: string}>}>  $fields
     */
    private function buildImports(array $fields): string
    {
        $classes = [];

        foreach ($fields as $field) {
            $class = $this->typeToClass($field['type']);
            $classes["Entelechy\\Architect\\Forms\\Fields\\{$class}"] = "use Entelechy\\Architect\\Forms\\Fields\\{$class};";
        }

        return implode("\n", array_unique(array_values($classes)));
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, options?: array<int, array{value: string, label: string}>}>  $fields
     */
    private function buildFields(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $class = $this->typeToClass($field['type']);
            $required = ($field['required'] ?? true) ? "\n                    ->required()" : '';
            $options = '';

            if (in_array($field['type'], ['select', 'radio', 'checkboxlist'], true)) {
                if (! empty($field['options'])) {
                    $optArray = implode(', ', array_map(
                        static fn (array $o): string => "'{$o['value']}' => '{$o['label']}'",
                        $field['options']
                    ));
                    $options = "\n                    ->options([{$optArray}])";
                } else {
                    $options = "\n                    ->options(/* TODO: provide options array or Closure */)";
                }
            }

            $lines[] = "                {$class}::make('{$field['name']}')\n                    ->label('{$field['label']}'){$required}{$options},";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array{name: string, type: string, label: string, required?: bool, options?: array<int, array{value: string, label: string}>}>  $fields
     */
    private function buildSaveUsing(array $fields): string
    {
        $names = implode(', ', array_map(
            static fn (array $f): string => "'{$f['name']}'",
            $fields
        ));

        return <<<PHP
                // TODO: implement save logic
                // \$validated = Arr::only(\$data, [{$names}]);
                // YourModel::create(\$validated);
PHP;
    }

    private function typeToClass(string $type): string
    {
        return match ($type) {
            'integer' => 'IntegerField',
            'decimal' => 'DecimalField',
            'date' => 'DateField',
            'datetime' => 'DateTimeField',
            'checkbox' => 'CheckboxField',
            'select' => 'SelectField',
            'radio' => 'Radio',
            'checkboxlist' => 'CheckboxList',
            'textarea' => 'TextareaField',
            'toggle' => 'Toggle',
            'file' => 'FileUpload',
            'richtext' => 'RichEditor',
            'markdown' => 'MarkdownEditor',
            'tags' => 'TagsInput',
            'hidden' => 'Hidden',
            'display' => 'DisplayField',
            default => 'TextField',
        };
    }
}
