<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Builders;

use Entelechy\Architect\Table\Actions\RowAction;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\ArchitectRowAction;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function canActOn(\Illuminate\Database\Eloquent\Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return \Illuminate\Database\Eloquent\Model::class;
    }
}
