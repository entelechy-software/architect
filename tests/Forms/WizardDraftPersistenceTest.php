<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Livewire\WizardEngine;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

/**
 * Verifies drafts survive a fresh WizardEngine mount within the same
 * browser session (FORMS_FEATURE_PLAN.md Phase 5, "Draft route-restore").
 * The underlying persistence (draftStoreKeys() always includes a
 * session-based key) was implemented in Phase 2 — this test documents and
 * confirms the behavior rather than introducing new production code.
 */
class WizardDraftPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_a_fresh_mount_restores_form_data_and_step_position_from_a_session_draft(): void
    {
        Livewire::test(WizardEngine::class, ['definitionClass' => DraftableWizardDefinition::class])
            ->set('formData.name', 'Ada Lovelace')
            ->call('nextStep')
            ->assertSet('currentStepId', 'second');

        // A brand new component instance, same test/browser session.
        Livewire::test(WizardEngine::class, ['definitionClass' => DraftableWizardDefinition::class])
            ->assertSet('currentStepId', 'second')
            ->assertSet('formData.name', 'Ada Lovelace')
            ->assertSet('history', ['first', 'second']);
    }

    public function test_a_fresh_mount_does_not_restore_step_position_when_resume_to_step_is_disabled(): void
    {
        Livewire::test(WizardEngine::class, ['definitionClass' => DraftableWithoutStepResumeDefinition::class])
            ->set('formData.name', 'Grace Hopper')
            ->call('nextStep')
            ->assertSet('currentStepId', 'second');

        Livewire::test(WizardEngine::class, ['definitionClass' => DraftableWithoutStepResumeDefinition::class])
            ->assertSet('currentStepId', 'first')
            ->assertSet('formData.name', 'Grace Hopper');
    }
}

final class DraftableWizardDefinition
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('draftable-wizard')
            ->step(id: 'first', label: 'First', structure: [TextField::make('name')->required()])
            ->step(id: 'second', label: 'Second', structure: [TextField::make('nickname')])
            ->drafts()
            ->resumeToStepFromDraft()
            ->saveUsing(fn (array $data) => null)
            ->build();
    }
}

final class DraftableWithoutStepResumeDefinition
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('draftable-wizard-no-step-resume')
            ->step(id: 'first', label: 'First', structure: [TextField::make('name')->required()])
            ->step(id: 'second', label: 'Second', structure: [TextField::make('nickname')])
            ->drafts()
            ->saveUsing(fn (array $data) => null)
            ->build();
    }
}
