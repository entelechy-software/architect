<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Builders;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Fields\SelectField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Fields\Toggle;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Tests\TestCase;

class FormBuilderTest extends TestCase
{
    public function test_make_returns_builder_instance(): void
    {
        $this->assertInstanceOf(FormBuilder::class, FormBuilder::make());
    }

    public function test_make_accepts_custom_key(): void
    {
        $this->assertInstanceOf(FormBuilder::class, FormBuilder::make('my-form'));
    }

    public function test_build_returns_definition(): void
    {
        $definition = FormBuilder::make()
            ->structure([
                TextField::make('name'),
            ])
            ->build();

        $this->assertInstanceOf(ArchitectFormDefinition::class, $definition);
    }

    public function test_build_with_multiple_field_types(): void
    {
        $definition = FormBuilder::make()
            ->structure([
                TextField::make('name')->required(),
                TextField::make('email')->required(),
                SelectField::make('role')->options(['admin' => 'Admin', 'user' => 'User']),
                Toggle::make('active'),
            ])
            ->build();

        $this->assertInstanceOf(ArchitectFormDefinition::class, $definition);
    }

    public function test_build_with_empty_structure(): void
    {
        $definition = FormBuilder::make()->structure([])->build();

        $this->assertInstanceOf(ArchitectFormDefinition::class, $definition);
    }
}
