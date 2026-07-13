<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Exceptions\WizardGraphException;
use Entelechy\Architect\Forms\Fields\SelectField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;

class WizardBuilderTest extends TestCase
{
    public function test_existing_two_argument_step_call_still_works(): void
    {
        $definition = WizardBuilder::make('onboarding')
            ->step('Account', [TextField::make('email')->required()])
            ->step('Preferences', [TextField::make('role')])
            ->build();

        $this->assertSame(2, $definition->totalSteps());
        $this->assertSame('account', $definition->steps[0]['id']);
        $this->assertSame('preferences', $definition->steps[1]['id']);
    }

    public function test_explicit_id_is_honored(): void
    {
        $definition = WizardBuilder::make('onboarding')
            ->step(id: 'acct', label: 'Account', structure: [TextField::make('email')])
            ->build();

        $this->assertSame('acct', $definition->steps[0]['id']);
    }

    public function test_branch_and_then_build_a_valid_graph(): void
    {
        $definition = WizardBuilder::make('application')
            ->step(id: 'applicant_type', label: 'Applicant Type', structure: [
                SelectField::make('applicant_type')->options(['individual' => 'Individual', 'company' => 'Company']),
            ])
            ->step(id: 'individual_details', label: 'Individual Details', structure: [TextField::make('first_name')])
            ->step(id: 'company_details', label: 'Company Details', structure: [TextField::make('company_name')])
            ->step(id: 'summary', label: 'Summary', structure: [])
            ->branch(from: 'applicant_type', map: ['individual' => 'individual_details', 'company' => 'company_details'])
            ->then('summary')
            ->build();

        $this->assertSame('individual_details', $definition->graph->nextStepId('applicant_type', ['applicant_type' => 'individual']));
        $this->assertSame('summary', $definition->graph->nextStepId('individual_details', []));
        $this->assertSame('summary', $definition->graph->nextStepId('company_details', []));
    }

    public function test_branch_referencing_missing_field_throws(): void
    {
        $this->expectException(WizardGraphException::class);
        $this->expectExceptionMessage("no field with that name exists");

        WizardBuilder::make('application')
            ->step(id: 'type', label: 'Type', structure: [TextField::make('unrelated_field')])
            ->step(id: 'next', label: 'Next', structure: [])
            ->branch(from: 'type', map: ['a' => 'next'])
            ->build();
    }

    public function test_drafts_and_guard_flags_are_carried_onto_definition(): void
    {
        $definition = WizardBuilder::make('application')
            ->step('Only', [TextField::make('name')])
            ->drafts()
            ->resumeUsingKey('application_id')
            ->resumeToStepFromDraft()
            ->guardDirtyNavigation()
            ->build();

        $this->assertTrue($definition->draftsEnabled);
        $this->assertSame('application_id', $definition->resumeKey);
        $this->assertTrue($definition->resumeToStepFromDraft);
        $this->assertTrue($definition->guardDirtyNavigation);
    }
}
