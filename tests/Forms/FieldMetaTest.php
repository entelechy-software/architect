<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Tests\TestCase;

class FieldMetaTest extends TestCase
{
    public function test_get_name_returns_name(): void
    {
        $this->assertSame('email', TextField::make('email')->getName());
    }

    public function test_auto_label_from_snake_case_name(): void
    {
        $this->assertSame('First Name', TextField::make('first_name')->getLabel());
    }

    public function test_auto_label_from_simple_name(): void
    {
        $this->assertSame('Email', TextField::make('email')->getLabel());
    }

    public function test_explicit_label_overrides_auto(): void
    {
        $this->assertSame(
            'Email Address',
            TextField::make('email')->label('Email Address')->getLabel()
        );
    }

    public function test_not_required_by_default(): void
    {
        $this->assertFalse(TextField::make('name')->isRequired());
    }

    public function test_required_sets_flag(): void
    {
        $this->assertTrue(TextField::make('name')->required()->isRequired());
    }

    public function test_make_is_chainable(): void
    {
        $field = TextField::make('name')
            ->label('Full Name')
            ->required()
            ->placeholder('Enter name');

        $this->assertSame('Full Name', $field->getLabel());
        $this->assertTrue($field->isRequired());
    }

    public function test_tooltip_is_null_by_default(): void
    {
        $this->assertNull(TextField::make('name')->getTooltip());
    }

    public function test_tooltip_sets_and_returns_text(): void
    {
        $field = TextField::make('name')->tooltip('Shown on hover next to the label');

        $this->assertSame('Shown on hover next to the label', $field->getTooltip());
    }

    public function test_tooltip_is_immutable_clone(): void
    {
        $original = TextField::make('name');
        $withTooltip = $original->tooltip('Extra context');

        $this->assertNull($original->getTooltip());
        $this->assertSame('Extra context', $withTooltip->getTooltip());
    }
}
