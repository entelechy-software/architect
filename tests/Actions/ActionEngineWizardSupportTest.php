<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Actions;

use Entelechy\Architect\Actions\Actions\CreateAction;
use Entelechy\Architect\Actions\Livewire\ActionEngine;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class ActionEngineWizardSupportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_form_class_is_wizard_returns_false_for_a_plain_form(): void
    {
        Livewire::test(ActionEngine::class)
            ->assertSet('openPanel', null)
            ->call('triggerAction', PlainFormCreateAction::class, null)
            ->assertSet('openPanel', 'form-class');

        $this->assertFalse(
            (new ActionEngine)->formClassIsWizard(PlainFormCreateAction::FORM_CLASS)
        );
    }

    public function test_form_class_is_wizard_returns_true_for_a_wizard(): void
    {
        $this->assertTrue(
            (new ActionEngine)->formClassIsWizard(WizardCreateAction::FORM_CLASS)
        );
    }

    public function test_trigger_action_opens_form_class_panel_for_a_wizard_form_class(): void
    {
        Livewire::test(ActionEngine::class)
            ->call('triggerAction', WizardCreateAction::class, null)
            ->assertSet('openPanel', 'form-class')
            ->assertSet('activeActionClass', WizardCreateAction::class);
    }

    public function test_wizard_completed_event_closes_panel_and_dispatches_action_completed(): void
    {
        Livewire::test(ActionEngine::class)
            ->call('triggerAction', WizardCreateAction::class, null)
            ->assertSet('openPanel', 'form-class')
            ->call('onNestedWizardCompleted')
            ->assertSet('openPanel', null)
            ->assertSet('activeActionClass', null)
            ->assertDispatched('architect:action:completed');
    }

    public function test_wizard_completed_event_is_ignored_when_no_form_class_panel_open(): void
    {
        Livewire::test(ActionEngine::class)
            ->call('onNestedWizardCompleted')
            ->assertNotDispatched('architect:action:completed');
    }
}

final class PlainFormActionForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('action-plain-form')
            ->structure([TextField::make('title')->required()])
            ->build();
    }
}

final class PlainFormCreateAction extends CreateAction
{
    public const FORM_CLASS = PlainFormActionForm::class;

    protected ?string $formClass = self::FORM_CLASS;
}

final class WizardActionForm
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('action-wizard-form')
            ->step('Only', [TextField::make('name')])
            ->build();
    }
}

final class WizardCreateAction extends CreateAction
{
    public const FORM_CLASS = WizardActionForm::class;

    protected ?string $formClass = self::FORM_CLASS;
}
