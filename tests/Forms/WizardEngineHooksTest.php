<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Events\FormEvents;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Livewire\WizardEngine;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Entelechy\Architect\Forms\Contracts\ProvidesWizardDefinition;

class WizardEngineHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        WizardHooksDefinition::reset();
    }

    public function test_submit_dispatches_custom_event_and_calls_success_hook(): void
    {
        Livewire::test(WizardEngine::class, ['definitionClass' => WizardHooksDefinition::class])
            ->set('formData.name', 'Ada Lovelace')
            ->call('submit')
            ->assertDispatched('architect:wizard:custom:refresh')
            ->assertDispatched(FormEvents::WIZARD_COMPLETED);

        $this->assertTrue(WizardHooksDefinition::$successCalled);
    }

    public function test_mount_honors_a_valid_url_provided_step_id(): void
    {
        Livewire::withQueryParams(['step' => 'second'])
            ->test(WizardEngine::class, ['definitionClass' => WizardHooksDefinition::class])
            ->assertSet('currentStepId', 'second');
    }

    public function test_mount_falls_back_to_first_step_when_url_step_id_is_invalid(): void
    {
        Livewire::withQueryParams(['step' => 'does-not-exist'])
            ->test(WizardEngine::class, ['definitionClass' => WizardHooksDefinition::class])
            ->assertSet('currentStepId', 'first');
    }

    public function test_mount_falls_back_to_first_step_when_url_step_id_is_absent(): void
    {
        Livewire::test(WizardEngine::class, ['definitionClass' => WizardHooksDefinition::class])
            ->assertSet('currentStepId', 'first');
    }
}

final class WizardHooksDefinition implements ProvidesWizardDefinition
{
    public static bool $successCalled = false;

    public static function reset(): void
    {
        self::$successCalled = false;
    }

    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('wizard-hooks')
            ->step(id: 'first', label: 'First', structure: [TextField::make('name')->required()])
            ->step(id: 'second', label: 'Second', structure: [TextField::make('nickname')])
            ->saveUsing(fn (array $data) => null)
            ->notifyOnSave(success: function () {
                self::$successCalled = true;
            })
            ->onSavedDispatch('architect:wizard:custom:refresh')
            ->build();
    }
}
