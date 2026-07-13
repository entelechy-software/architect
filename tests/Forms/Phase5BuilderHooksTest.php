<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;

class Phase5BuilderHooksTest extends TestCase
{
    public function test_form_builder_on_saved_dispatch_is_carried_onto_definition(): void
    {
        $definition = FormBuilder::make('x')
            ->onSavedDispatch('architect:notes:refresh', ['foo' => 'bar'])
            ->build();

        $this->assertSame('architect:notes:refresh', $definition->onSavedDispatchEvent);
        $this->assertSame(['foo' => 'bar'], $definition->onSavedDispatchPayload);
    }

    public function test_form_builder_notify_on_save_is_carried_onto_definition(): void
    {
        $success = fn () => null;
        $failure = fn () => null;

        $definition = FormBuilder::make('x')
            ->notifyOnSave($success, $failure)
            ->build();

        $this->assertSame($success, $definition->onSaveSuccess);
        $this->assertSame($failure, $definition->onSaveFailure);
    }

    public function test_form_builder_expose_to_supersearch_is_carried_onto_definition(): void
    {
        $definition = FormBuilder::make('x')
            ->exposeToSupersearch('Create Quick Note')
            ->build();

        $this->assertSame('Create Quick Note', $definition->supersearchLabel);
    }

    public function test_wizard_builder_has_the_same_three_hooks(): void
    {
        $definition = WizardBuilder::make('x')
            ->step('Only', [])
            ->onSavedDispatch('architect:app:refresh')
            ->notifyOnSave(fn () => null, fn () => null)
            ->exposeToSupersearch('Start Application')
            ->build();

        $this->assertSame('architect:app:refresh', $definition->onSavedDispatchEvent);
        $this->assertNotNull($definition->onSaveSuccess);
        $this->assertNotNull($definition->onSaveFailure);
        $this->assertSame('Start Application', $definition->supersearchLabel);
    }
}
