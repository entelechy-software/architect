<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Fields\CronScheduleBuilderField;
use Entelechy\Architect\Forms\Fields\DataMappingField;
use Entelechy\Architect\Forms\Fields\FormulaExpressionEditorField;
use Entelechy\Architect\Forms\Fields\MathEquationEditorField;
use Entelechy\Architect\Forms\Fields\NodeGraphEditorField;
use Entelechy\Architect\Forms\Fields\QueryBuilderField;
use Entelechy\Architect\Forms\Fields\RoleBuilderField;
use Entelechy\Architect\Forms\Fields\RulesWorkflowBuilderField;
use Entelechy\Architect\Forms\Fields\SchemaDrivenObjectEditorField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Forms\Contracts\ProvidesFormDefinition;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

/**
 * Per-field integration tests for ARCHITECT_IMPROVEMENT_PLAN.md Phase 1
 * Wave 4's "meta/builder / mini-DSL editors" tier — same approach as
 * Wave1FieldIntegrationTest/Wave2FieldIntegrationTest: prove the PHP
 * half of the contract every hand-rolled Alpine component in
 * resources/js/components/architectForms.js relies on
 * ($wire.set(wireField, value) lands in formData and passes/fails
 * validation exactly as the field's getRules() promises). Client-side
 * interaction itself (dragging nodes, building nested query groups,
 * etc.) isn't exercised here — that would require a real browser.
 */
class Wave4FieldIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_formula_expression_editor_field_round_trips_an_expression_string(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => FormulaExpressionEditorFormDefinition::class])
            ->set('formData.formula', '(total - discount) * tax_rate')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('(total - discount) * tax_rate', FormulaExpressionEditorFormDefinition::$savedData['formula']);
    }

    public function test_math_equation_editor_field_round_trips_a_latex_string(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => MathEquationEditorFormDefinition::class])
            ->set('formData.equation', '\\frac{a}{b} + \\sqrt{c}')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('\\frac{a}{b} + \\sqrt{c}', MathEquationEditorFormDefinition::$savedData['equation']);
    }

    public function test_query_builder_field_round_trips_nested_condition_groups(): void
    {
        $query = [
            'operator' => 'and',
            'conditions' => [
                ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                ['operator' => 'or', 'conditions' => [
                    ['field' => 'role', 'operator' => '=', 'value' => 'admin'],
                    ['field' => 'role', 'operator' => '=', 'value' => 'editor'],
                ]],
            ],
        ];

        Livewire::test(FormEngine::class, ['definitionClass' => QueryBuilderFormDefinition::class])
            ->set('formData.query', $query)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($query, QueryBuilderFormDefinition::$savedData['query']);
    }

    public function test_rules_workflow_builder_field_round_trips_nodes_and_edges(): void
    {
        $workflow = [
            'nodes' => [
                ['id' => 'node-0', 'type' => 'trigger', 'config' => []],
                ['id' => 'node-1', 'type' => 'action', 'config' => ['channel' => 'email']],
            ],
            'edges' => [
                ['from' => 'node-0', 'to' => 'node-1'],
            ],
        ];

        Livewire::test(FormEngine::class, ['definitionClass' => RulesWorkflowBuilderFormDefinition::class])
            ->set('formData.workflow', $workflow)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($workflow, RulesWorkflowBuilderFormDefinition::$savedData['workflow']);
    }

    public function test_schema_driven_object_editor_field_round_trips_an_object_matching_its_schema(): void
    {
        $object = ['name' => 'Jane Doe', 'age' => 32, 'active' => true];

        Livewire::test(FormEngine::class, ['definitionClass' => SchemaDrivenObjectEditorFormDefinition::class])
            ->set('formData.profile', $object)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($object, SchemaDrivenObjectEditorFormDefinition::$savedData['profile']);
    }

    public function test_node_graph_editor_field_round_trips_positioned_nodes_and_edges(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'node-0', 'type' => 'start', 'x' => 10.5, 'y' => 20.5],
                ['id' => 'node-1', 'type' => 'end', 'x' => 120.5, 'y' => 60.5],
            ],
            'edges' => [
                ['from' => 'node-0', 'to' => 'node-1'],
            ],
        ];

        Livewire::test(FormEngine::class, ['definitionClass' => NodeGraphEditorFormDefinition::class])
            ->set('formData.graph', $graph)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($graph, NodeGraphEditorFormDefinition::$savedData['graph']);
    }

    public function test_data_mapping_field_round_trips_mapping_rows(): void
    {
        $mapping = [
            ['source' => 'first_name', 'destination' => 'given_name', 'transform' => null],
            ['source' => 'last_name', 'destination' => 'family_name', 'transform' => 'upper'],
        ];

        Livewire::test(FormEngine::class, ['definitionClass' => DataMappingFormDefinition::class])
            ->set('formData.mapping', $mapping)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($mapping, DataMappingFormDefinition::$savedData['mapping']);
    }

    public function test_cron_schedule_builder_field_validates_a_five_field_cron_expression(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => CronScheduleBuilderFormDefinition::class])
            ->set('formData.schedule', 'not-a-cron-expression')
            ->call('submit')
            ->assertHasErrors(['formData.schedule']);

        Livewire::test(FormEngine::class, ['definitionClass' => CronScheduleBuilderFormDefinition::class])
            ->set('formData.schedule', '0 9 * * 1-5')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('0 9 * * 1-5', CronScheduleBuilderFormDefinition::$savedData['schedule']);
    }

    public function test_role_builder_field_round_trips_permissions_scope_and_exceptions(): void
    {
        $role = [
            'permissions' => ['events.create', 'events.publish'],
            'inherits_from' => 'editor',
            'scope' => 'committee:activities',
            'exceptions' => ['events.delete'],
        ];

        Livewire::test(FormEngine::class, ['definitionClass' => RoleBuilderFormDefinition::class])
            ->set('formData.role', $role)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($role, RoleBuilderFormDefinition::$savedData['role']);
    }
}

final class FormulaExpressionEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-formula-expression-editor')
            ->structure([FormulaExpressionEditorField::make('formula')->availableFields(['total', 'discount', 'tax_rate'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class MathEquationEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-math-equation-editor')
            ->structure([MathEquationEditorField::make('equation')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class QueryBuilderFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-query-builder')
            ->structure([QueryBuilderField::make('query')->availableFields(['status', 'role'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class RulesWorkflowBuilderFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-rules-workflow-builder')
            ->structure([RulesWorkflowBuilderField::make('workflow')->availableNodeTypes(['trigger', 'action'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class SchemaDrivenObjectEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-schema-driven-object-editor')
            ->structure([
                SchemaDrivenObjectEditorField::make('profile')->schema([
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'title' => 'Name'],
                        'age' => ['type' => 'integer', 'title' => 'Age'],
                        'active' => ['type' => 'boolean', 'title' => 'Active'],
                    ],
                ]),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class NodeGraphEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-node-graph-editor')
            ->structure([NodeGraphEditorField::make('graph')->availableNodeTypes(['start', 'end'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class DataMappingFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-data-mapping')
            ->structure([
                DataMappingField::make('mapping')
                    ->sourceFields(['first_name', 'last_name'])
                    ->destinationFields(['given_name', 'family_name']),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class CronScheduleBuilderFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-cron-schedule-builder')
            ->structure([CronScheduleBuilderField::make('schedule')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class RoleBuilderFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave4-role-builder')
            ->structure([
                RoleBuilderField::make('role')
                    ->availablePermissions(['events.create', 'events.publish', 'events.delete'])
                    ->availableRolesToInheritFrom(['editor', 'viewer']),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}
