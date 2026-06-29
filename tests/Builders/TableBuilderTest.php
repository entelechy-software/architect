<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Builders;

use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;

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
}
