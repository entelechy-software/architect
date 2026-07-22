<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Builders;

use Entelechy\Architect\Breadcrumbs\AutomaticBreadcrumbsResolver;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Table\Actions\BulkDeleteAction;
use Entelechy\Architect\Table\Actions\BulkStatusAction;
use Entelechy\Architect\Table\Actions\RowAction;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Entelechy\Architect\Table\Contracts\ArchitectRowAction;
use Entelechy\Architect\Table\CustomForm;
use Entelechy\Architect\Table\ModuleTableFilterPipeline;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

class TableBuilderTest extends TestCase
{
    public function test_column_make_returns_column(): void
    {
        $this->assertInstanceOf(Column::class, Column::make('name'));
    }

    public function test_column_key_is_stored(): void
    {
        $this->assertSame('email', Column::make('email')->getKey());
    }

    public function test_column_auto_label_from_snake_case(): void
    {
        $this->assertSame('First Name', Column::make('first_name')->getLabel());
    }

    public function test_column_auto_label_from_simple_name(): void
    {
        $this->assertSame('Name', Column::make('name')->getLabel());
    }

    public function test_column_explicit_label_overrides_auto(): void
    {
        $this->assertSame('Email Address', Column::make('email')->label('Email Address')->getLabel());
    }

    public function test_column_methods_are_chainable(): void
    {
        $column = Column::make('name')
            ->label('Full Name')
            ->sortable()
            ->searchable();

        $this->assertInstanceOf(Column::class, $column);
        $this->assertSame('Full Name', $column->getLabel());
    }

    public function test_column_mode_specific_permission_nodes_resolve_with_fallback(): void
    {
        $column = Column::make('salary')
            ->visibleTo('users.salary.view')
            ->modifyVisibleTo('users.salary.modify.view')
            ->createEditableTo('users.salary.create.edit')
            ->modifyEditableTo('users.salary.modify.edit');

        $this->assertSame('users.salary.view', $column->visibilityNodeForMode(true));
        $this->assertSame('users.salary.modify.view', $column->visibilityNodeForMode(false));
        $this->assertSame('users.salary.create.edit', $column->editabilityNodeForMode(true));
        $this->assertSame('users.salary.modify.edit', $column->editabilityNodeForMode(false));
    }

    public function test_column_badge_profiles_support_color_icon_and_position(): void
    {
        $column = Column::make('verified')->badge([
            'Verified' => [
                'colors' => 'success',
                'icon' => 'fas fa-check',
                'position' => 'right',
            ],
        ]);

        $profile = $column->getBadgeProfileForValue('Verified');

        $this->assertSame('success', $profile['color']);
        $this->assertSame('fas fa-check', $profile['icon']);
        $this->assertSame('right', $profile['position']);
    }

    public function test_column_colors_still_drive_badge_profile_color(): void
    {
        $column = Column::make('status')
            ->colors(['Active' => 'success']);

        $profile = $column->getBadgeProfileForValue('Active');

        $this->assertSame('success', $profile['color']);
        $this->assertNull($profile['icon']);
        $this->assertSame('left', $profile['position']);
    }

    public function test_column_tooltip_is_null_by_default(): void
    {
        $this->assertNull(Column::make('status')->getTooltip());
    }

    public function test_column_tooltip_sets_and_returns_text(): void
    {
        $column = Column::make('status')->tooltip('Shown next to the header on hover');

        $this->assertSame('Shown next to the header on hover', $column->getTooltip());
    }

    public function test_column_tooltip_is_immutable_clone(): void
    {
        $original = Column::make('status');
        $withTooltip = $original->tooltip('Extra context');

        $this->assertNull($original->getTooltip());
        $this->assertSame('Extra context', $withTooltip->getTooltip());
    }

    public function test_table_builder_make_returns_builder(): void
    {
        $this->assertInstanceOf(TableBuilder::class, TableBuilder::make());
    }

    public function test_custom_row_action_is_stored_on_definition(): void
    {
        $customAction = new StubArchitectRowAction;

        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customRowAction($customAction)
            ->build();

        $this->assertSame([$customAction], $definition->customRowActions);
    }

    public function test_custom_row_actions_coexist_independently_with_row_actions(): void
    {
        $customAction = new StubArchitectRowAction;
        $rowAction = RowAction::make('notify')->label('Send Notification');

        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->rowAction($rowAction)
            ->customRowAction($customAction)
            ->build();

        $this->assertSame([$rowAction], $definition->rowActions);
        $this->assertSame([$customAction], $definition->customRowActions);
    }

    public function test_custom_row_action_defaults_to_empty_array(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->build();

        $this->assertSame([], $definition->customRowActions);
    }

    public function test_bulk_actions_named_arguments_are_stored_on_definition(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->bulkActions(delete: true, archive: true, restore: true)
            ->build();

        $this->assertTrue($definition->selectableRows);
        $this->assertCount(3, $definition->bulkActions);
        $this->assertSame('delete', $definition->bulkActions[0]->getKey());
        $this->assertSame('archive', $definition->bulkActions[1]->getKey());
        $this->assertSame('restore', $definition->bulkActions[2]->getKey());
    }

    public function test_bulk_actions_legacy_array_style_remains_supported(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->bulkActions([
                'delete' => true,
                'archive' => true,
                'restore' => true,
            ])
            ->build();

        $this->assertTrue($definition->selectableRows);
        $this->assertCount(3, $definition->bulkActions);
        $this->assertSame('delete', $definition->bulkActions[0]->getKey());
        $this->assertSame('archive', $definition->bulkActions[1]->getKey());
        $this->assertSame('restore', $definition->bulkActions[2]->getKey());
    }

    public function test_bulk_actions_named_options_are_applied(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->bulkActions(
                delete: ['reasonRequired' => true],
                status: ['options' => ['open', 'closed', 'pending']],
            )
            ->build();

        $this->assertCount(2, $definition->bulkActions);
        $this->assertInstanceOf(BulkDeleteAction::class, $definition->bulkActions[0]);
        $this->assertTrue($definition->bulkActions[0]->requiresReason());
        $this->assertInstanceOf(BulkStatusAction::class, $definition->bulkActions[1]);
        $this->assertSame(['open', 'closed', 'pending'], $definition->bulkActions[1]->options());
    }

    public function test_custom_filter_registers_like_filter(): void
    {
        $filter = StubArchitectFilter::make('queue_preset');

        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customFilter($filter)
            ->build();

        $this->assertCount(1, $definition->filters);
        $this->assertSame('queue_preset', $definition->filters[0]->name());
    }

    public function test_filter_renderer_defaults_to_blade(): void
    {
        $filter = StubArchitectFilter::make('queue_preset');

        $this->assertSame('architect::table.filters.select', $filter->renderer());
    }

    public function test_filter_renderer_accepts_blade_override(): void
    {
        $filter = StubArchitectFilter::make('queue_preset')
            ->render('architect::table.filters.text');

        $this->assertSame('architect::table.filters.text', $filter->renderer());
        $this->assertSame('architect::table.filters.select', StubArchitectFilter::make('queue_preset')->renderer());
    }

    public function test_filter_renderer_accepts_renderable_override(): void
    {
        $renderable = new class implements Renderable
        {
            public function render(): string
            {
                return '<div>Custom filter control</div>';
            }
        };

        $filter = StubArchitectFilter::make('queue_preset')
            ->render($renderable);

        $this->assertSame($renderable, $filter->renderer());
    }

    public function test_duplicate_filter_names_throw_during_build(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate filter names detected: queue_preset');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->filter(StubArchitectFilter::make('queue_preset'))
            ->customFilter(StubArchitectFilter::make('queue_preset'))
            ->build();
    }

    public function test_auto_refresh_fingerprint_on_is_stored_on_definition(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->autoRefresh(seconds: 30, countdown: true, fingerprintOn: 'updated_at')
            ->build();

        $this->assertSame(30, $definition->autoRefreshSeconds);
        $this->assertTrue($definition->autoRefreshCountdown);
        $this->assertSame('updated_at', $definition->autoRefreshFingerprintOn);
    }

    public function test_auto_refresh_rejects_empty_fingerprint_on(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('autoRefresh fingerprintOn must be a non-empty string when provided');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->autoRefresh(seconds: 30, fingerprintOn: '   ')
            ->build();
    }

    public function test_breadcrumbs_support_menu_metadata(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->breadcrumbs([
                [
                    'title' => 'Admin',
                    'url' => '/admin',
                    'menu' => [
                        ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                    ],
                ],
                ['title' => 'Widgets'],
            ])
            ->build();

        $this->assertSame('manual', $definition->breadcrumbMode);
        $this->assertSame('/admin/dashboard', $definition->breadcrumbs[0]['menu'][0]['url'] ?? null);
    }

    public function test_breadcrumbs_automatic_sets_expected_definition_flags(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->breadcrumbsAutomatic(
                enabled: true,
                includeHome: true,
                homeTitle: 'Portal',
                homeUrl: '/portal',
                includeCurrent: false,
            )
            ->build();

        $this->assertSame('automatic', $definition->breadcrumbMode);
        $this->assertTrue($definition->breadcrumbAutoIncludeHome);
        $this->assertSame('Portal', $definition->breadcrumbAutoHomeTitle);
        $this->assertSame('/portal', $definition->breadcrumbAutoHomeUrl);
        $this->assertFalse($definition->breadcrumbAutoIncludeCurrent);
    }

    public function test_automatic_breadcrumbs_resolver_builds_path_based_trail(): void
    {
        $definition = TableBuilder::make()
            ->title('Project Tasks')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->breadcrumbsAutomatic(homeTitle: 'Admin', homeUrl: '/admin')
            ->build();

        $resolver = new AutomaticBreadcrumbsResolver;
        $request = Request::create('/admin/projects/tasks');
        $trail = $resolver->forTable($definition, $request);

        $this->assertSame('Admin', $trail[0]['title']);
        $this->assertSame('/admin', $trail[1]['url']);
        $this->assertSame('/admin/projects', $trail[2]['url']);
        $this->assertSame('Project Tasks', $trail[array_key_last($trail)]['title']);
        $this->assertFalse($trail[array_key_last($trail)]['url']);
    }

    public function test_form_mode_accepts_wizard(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(for: 'create', definitionClass: StubWizardFormDefinition::class)
            ->customForm(for: 'modify', definitionClass: StubWizardFormDefinition::class)
            ->formMode(create: 'wizard', modify: 'wizard')
            ->build();

        $this->assertSame('wizard', $definition->formMode);
    }

    public function test_form_mode_rejects_page(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('create form mode must be one of: slide-over, modal, wizard');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->formMode(create: 'page', modify: 'modal')
            ->build();
    }

    public function test_custom_form_is_stored_for_create(): void
    {
        $definition = TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'create',
                definitionClass: StubStandardFormDefinition::class,
                mode: 'new-window',
                url: '/admin/widgets/create',
                callbackQueryKey: 'refresh',
            )
            ->build();

        $this->assertInstanceOf(CustomForm::class, $definition->customCreateForm);
        $this->assertSame('new-window', $definition->customCreateForm?->mode);
        $this->assertSame('/admin/widgets/create', $definition->customCreateForm?->url);
        $this->assertSame('refresh', $definition->customCreateForm?->callbackQueryKey);
    }

    public function test_custom_form_tabs_manager_requires_tab_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('customForm mode tabs-manager requires a non-empty tabType.');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'modify',
                definitionClass: StubStandardFormDefinition::class,
                mode: 'tabs-manager',
                url: '/admin/widgets/{id}/edit',
            )
            ->build();
    }

    public function test_wizard_form_mode_requires_custom_forms_for_enabled_flows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("formMode('wizard') requires customForm(for: 'create', ...)");

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->formMode(create: 'wizard', modify: 'wizard')
            ->build();
    }

    public function test_wizard_form_mode_rejects_non_wizard_definition(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("formMode('wizard') requires customForm(for: 'create') definition");

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(for: 'create', definitionClass: StubStandardFormDefinition::class)
            ->customForm(for: 'modify', definitionClass: StubWizardFormDefinition::class)
            ->formMode(create: 'wizard', modify: 'wizard')
            ->build();
    }

    public function test_custom_form_rejects_unknown_mode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('customForm mode must be one of');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'create',
                definitionClass: StubWizardFormDefinition::class,
                mode: 'popup',
            )
            ->build();
    }

    public function test_custom_form_requires_url_for_new_window_and_same_window_modes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("customForm mode 'new-window' requires a non-empty url.");

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'create',
                definitionClass: StubWizardFormDefinition::class,
                mode: 'new-window',
            )
            ->build();
    }

    public function test_custom_form_requires_existing_definition_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('customForm definition class [App\\Forms\\MissingDefinition] does not exist.');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'create',
                definitionClass: 'App\\Forms\\MissingDefinition',
            )
            ->build();
    }

    public function test_custom_form_requires_definition_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must expose static definition()');

        TableBuilder::make()
            ->title('Widgets')
            ->model(StubArchitectDataModel::class)
            ->permissions(read: 'widgets.read', create: 'widgets.create', modify: 'widgets.modify', remove: 'widgets.remove')
            ->customForm(
                for: 'create',
                definitionClass: StubNoDefinitionMethod::class,
            )
            ->build();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_filter_pipeline_passes_structured_payload_to_custom_filter(): void
    {
        $spyFilter = SpyArchitectFilter::make('segment');

        $payload = [
            'regions' => ['emea', 'apac'],
            'tiers' => ['enterprise'],
        ];

        $context = new QueryContext(
            filters: ['segment' => $payload],
            filterDefinitions: ['segment' => $spyFilter],
        );

        $query = $this->createMock(Builder::class);

        ModuleTableFilterPipeline::apply($query, $context);

        $this->assertSame($payload, $spyFilter->lastValue);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_filter_pipeline_calls_filter_with_null_when_value_missing(): void
    {
        $spyFilter = SpyArchitectFilter::make('segment');

        $context = new QueryContext(
            filters: [],
            filterDefinitions: ['segment' => $spyFilter],
        );

        $query = $this->createMock(Builder::class);

        ModuleTableFilterPipeline::apply($query, $context);

        $this->assertNull($spyFilter->lastValue);
    }

    public function test_query_context_without_filter_removes_definition_and_value(): void
    {
        $segment = SpyArchitectFilter::make('segment');
        $status = SpyArchitectFilter::make('status');

        $context = new QueryContext(
            filters: [
                'segment' => ['regions' => ['emea']],
                'status' => 'active',
            ],
            filterDefinitions: [
                'segment' => $segment,
                'status' => $status,
            ],
        );

        $withoutSegment = $context->withoutFilter('segment');

        $this->assertArrayNotHasKey('segment', $withoutSegment->filters);
        $this->assertArrayNotHasKey('segment', $withoutSegment->filterDefinitions);
        $this->assertSame('active', $withoutSegment->filters['status']);
    }
}

final class StubArchitectFilter extends ArchitectFilter
{
    public function blade(): string
    {
        return 'architect::table.filters.select';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $query->where($this->name(), '=', $value);
    }
}

final class SpyArchitectFilter extends ArchitectFilter
{
    public mixed $lastValue = null;

    public function blade(): string
    {
        return 'architect::table.filters.select';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        $this->lastValue = $value;
    }
}

final class StubArchitectRowAction implements ArchitectRowAction
{
    public function getKey(): string
    {
        return 'stub-action';
    }

    public function getLabel(): string
    {
        return 'Stub Action';
    }

    public function icon(): ?string
    {
        return null;
    }

    public function color(): string
    {
        return 'primary';
    }

    public function confirm(): ?string
    {
        return null;
    }

    public function permissionNode(): ?string
    {
        return null;
    }

    /** @param array<string, mixed> $row */
    public function isVisibleFor(array $row): bool
    {
        return true;
    }

    /** @return array{success: bool, message: string} */
    public function handle(int $id, ArchitectDataModel $dataModel): array
    {
        return ['success' => true, 'message' => 'Stub handled.'];
    }
}

final class StubArchitectDataModel implements ArchitectDataModel
{
    public function forList(QueryContext $context): LengthAwarePaginator
    {
        return new ConcreteLengthAwarePaginator([], 0, 15);
    }

    /** @return array<string, mixed>|null */
    public function forForm(int $id): ?array
    {
        return null;
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return 1;
    }

    /** @param array<string, mixed> $input */
    public function modify(int $id, array $input): void
    {
        //
    }

    public function archive(int $id, ?string $reason = null): void
    {
        //
    }

    public function restore(int $id): void
    {
        //
    }

    public function delete(int $id, ?string $reason = null): void
    {
        //
    }

    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return Model::class;
    }
}

final class StubStandardFormDefinition
{
    public static function definition(): ArchitectFormDefinition
    {
        return new ArchitectFormDefinition(
            key: 'stub-standard-form',
            structure: [],
            saveUsing: null,
            fillData: null,
        );
    }
}

final class StubWizardFormDefinition
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('stub-wizard-form')
            ->step(id: 'step-1', label: 'Step 1', structure: [])
            ->build();
    }
}

final class StubNoDefinitionMethod {}
