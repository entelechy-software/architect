<?php

declare(strict_types=1);

namespace Entelechy\Architect\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Lists all available Architect Field types with their descriptions and key method signatures.
 */
class ArchitectListFieldsTool extends Tool
{
    protected string $name = 'architect_list_fields';

    protected string $description = 'List all Architect form field types available in the package (28 total), with descriptions and key method signatures. Use this to choose the right field type when scaffolding forms.';

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->nullable()->description(
                'Optional substring filter applied to class name or description (case-insensitive).'
            ),
        ];
    }

    public function handle(): Response
    {
        /** @var Request $request */
        $request = app('mcp.request');

        $filter = $request->get('filter');
        $filter = is_string($filter) ? strtolower(trim($filter)) : null;

        $fields = $this->allFields();

        if ($filter !== null && $filter !== '') {
            $fields = array_values(array_filter(
                $fields,
                static fn (array $f): bool => str_contains(strtolower($f['class']), $filter)
                    || str_contains(strtolower($f['description']), $filter)
            ));
        }

        return Response::json([
            'fields' => $fields,
            'total' => count($fields),
            'note' => 'All fields live in Entelechy\\Architect\\Forms\\Fields\\. Use the class name in use statements.',
        ]);
    }

    /**
     * @return array<int, array{class: string, input_type: string, description: string, key_methods: array<int, string>}>
     */
    private function allFields(): array
    {
        return [
            ['class' => 'TextField',        'input_type' => 'text',       'description' => 'Single-line text input.',              'key_methods' => ['maxLength(int)', 'prefix(string)', 'suffix(string)', 'placeholder(string)']],
            ['class' => 'IntegerField',      'input_type' => 'number',     'description' => 'Whole-number input.',                  'key_methods' => ['min(int)', 'max(int)', 'step(int)']],
            ['class' => 'DecimalField',      'input_type' => 'number',     'description' => 'Decimal/float number input.',          'key_methods' => ['min(float)', 'max(float)', 'step(float)', 'decimalPlaces(int)']],
            ['class' => 'DateField',         'input_type' => 'date',       'description' => 'Date-only picker.',                    'key_methods' => ['minDate(string)', 'maxDate(string)', 'format(string)']],
            ['class' => 'DateTimeField',     'input_type' => 'datetime',   'description' => 'Date + time picker.',                  'key_methods' => ['mustBeAfter(string $otherField)', 'minDate(string)', 'maxDate(string)']],
            ['class' => 'CheckboxField',     'input_type' => 'checkbox',   'description' => 'Single boolean checkbox.',             'key_methods' => ['onValue(mixed)', 'offValue(mixed)', 'label(string)']],
            ['class' => 'SelectField',       'input_type' => 'select',     'description' => 'Dropdown select, single or multiple.', 'key_methods' => ['options(array|Closure)', 'searchable(bool)', 'multiple(bool)', 'placeholder(string)']],
            ['class' => 'LookupField',       'input_type' => 'async-select', 'description' => 'AJAX-powered select with server search.', 'key_methods' => ['source(string $url)', 'searchParam(string)', 'minChars(int)']],
            ['class' => 'TextareaField',     'input_type' => 'textarea',   'description' => 'Multi-line text area.',                'key_methods' => ['rows(int)', 'maxLength(int)', 'autosize(bool)']],
            ['class' => 'DisplayField',      'input_type' => 'display',    'description' => 'Read-only computed value display.',    'key_methods' => ['formatUsing(Closure)', 'content(string|Closure)']],
            ['class' => 'Toggle',            'input_type' => 'toggle',     'description' => 'Pill-style on/off toggle.',            'key_methods' => ['onLabel(string)', 'offLabel(string)', 'onColor(string)']],
            ['class' => 'CheckboxList',      'input_type' => 'checkbox-group', 'description' => 'Multiple-select checkbox group.',  'key_methods' => ['options(array|Closure)', 'columns(int)', 'searchable(bool)']],
            ['class' => 'Radio',             'input_type' => 'radio',      'description' => 'Radio button group.',                  'key_methods' => ['options(array|Closure)', 'inline(bool)']],
            ['class' => 'DateTimePicker',    'input_type' => 'calendar',   'description' => 'Enhanced calendar + time picker.',    'key_methods' => ['withTime(bool)', 'minDate(string)', 'maxDate(string)', 'format(string)']],
            ['class' => 'FileUpload',        'input_type' => 'file',       'description' => 'Drag-drop file uploader.',             'key_methods' => ['multiple(bool)', 'accept(string)', 'maxSize(int $kb)', 'disk(string)']],
            ['class' => 'RichEditor',        'input_type' => 'wysiwyg',    'description' => 'TipTap WYSIWYG rich text editor.',    'key_methods' => ['toolbar(array)', 'minHeight(int)']],
            ['class' => 'MarkdownEditor',    'input_type' => 'markdown',   'description' => 'Markdown textarea with live preview.', 'key_methods' => ['toolbar(array)', 'previewUsing(Closure)']],
            ['class' => 'Repeater',          'input_type' => 'repeater',   'description' => 'Repeatable group of sub-fields.',      'key_methods' => ['structure(array)', 'minItems(int)', 'maxItems(int)', 'addButtonLabel(string)']],
            ['class' => 'Builder',           'input_type' => 'block-builder', 'description' => 'Block-based structured content.',   'key_methods' => ['blocks(array<Block>)']],
            ['class' => 'TagsInput',         'input_type' => 'tags',       'description' => 'Free-text tag entry.',                 'key_methods' => ['suggestions(array|Closure)', 'allowCreate(bool)', 'maxTags(int)']],
            ['class' => 'KeyValue',          'input_type' => 'key-value',  'description' => 'Key/value pair editor.',               'key_methods' => ['keyLabel(string)', 'valueLabel(string)', 'addButtonLabel(string)']],
            ['class' => 'ColorPicker',       'input_type' => 'color',      'description' => 'Hex/RGB colour picker.',               'key_methods' => ['withAlpha(bool)', 'format(string)', 'swatches(array)']],
            ['class' => 'Fieldset',          'input_type' => 'fieldset',   'description' => 'Visual group wrapper for fields.',     'key_methods' => ['structure(array<StructureItem>)', 'columns(int)']],
            ['class' => 'ToggleButtons',     'input_type' => 'button-group', 'description' => 'Button-group selector.',              'key_methods' => ['options(array|Closure)', 'multiple(bool)', 'size(string)']],
            ['class' => 'Slider',            'input_type' => 'range',      'description' => 'Numeric range slider.',                'key_methods' => ['min(int)', 'max(int)', 'step(int)', 'displayFormat(Closure)']],
            ['class' => 'CodeEditor',        'input_type' => 'code',       'description' => 'Monaco code editor embed.',           'key_methods' => ['language(string)', 'height(int)', 'theme(string)', 'readOnly(bool)']],
            ['class' => 'Hidden',            'input_type' => 'hidden',     'description' => 'No-UI hidden value field.',            'key_methods' => []],
            ['class' => 'Custom',            'input_type' => 'custom',     'description' => 'Arbitrary Blade view field.',          'key_methods' => ['view(string)', 'viewData(array)']],
        ];
    }
}
