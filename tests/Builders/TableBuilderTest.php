<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Builders;

use Entelechy\Architect\Table\Actions\BulkDeleteAction;
use Entelechy\Architect\Table\Actions\BulkStatusAction;
use Entelechy\Architect\Table\Actions\RowAction;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Entelechy\Architect\Table\Contracts\ArchitectRowAction;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

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
