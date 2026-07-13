<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Fields\SelectField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Livewire\WizardEngine;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Livewire\Livewire;

/**
 * Regression test for the "hardening" decision noted in
 * FORMS_FEATURE_PLAN.md: proving a Table's customForm() opens a wizard
 * definition that uses Phase 2 branching without any new
 * Table/Panel/WizardEngine glue code, since Table's FormPanel only ever
 * needs the definition's FQCN to resolve the correct Livewire engine
 * component — branching, drafts, and everything else Phase 2 added live
 * entirely inside the wizard definition and WizardEngine itself.
 */
class CustomFormWizardBranchingRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_form_panel_resolves_a_branching_wizard_exposed_via_custom_form(): void
    {
        Livewire::test(FormPanel::class, ['definitionClass' => BranchingRuntimeTableDefinition::class])
            ->call(
                'openCustomForm',
                definitionClass: BranchingRuntimeTableDefinition::class,
                title: 'Edit Application',
                customDefinitionClass: BranchingApplicationWizard::class,
                customMode: 'modal',
            )
            ->assertSet('panelState', 'custom')
            ->assertSet('customData.engineComponent', 'architect-wizard-engine');
    }

    public function test_the_branching_wizard_itself_navigates_correctly_when_opened_this_way(): void
    {
        Livewire::test(WizardEngine::class, ['definitionClass' => BranchingApplicationWizard::class])
            ->assertSet('currentStepId', 'applicant_type')
            ->set('formData.applicant_type', 'company')
            ->call('nextStep')
            ->assertSet('currentStepId', 'company_details')
            ->set('formData.company_name', 'Acme Ltd')
            ->call('nextStep')
            ->assertSet('currentStepId', 'summary');
    }
}

final class BranchingRuntimeTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('Applications')
            ->model(BranchingRuntimeDataModel::class)
            ->permissions(
                read: 'applications.read',
                create: 'applications.create',
                modify: 'applications.modify',
                remove: 'applications.remove',
            )
            ->customForm(
                for: 'modify',
                definitionClass: BranchingApplicationWizard::class,
                mode: 'modal',
                url: '/admin/applications/{id}/edit',
            )
            ->build();
    }
}

final class BranchingApplicationWizard
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('branching-application')
            ->step(id: 'applicant_type', label: 'Applicant Type', structure: [
                SelectField::make('applicant_type')->options(['individual' => 'Individual', 'company' => 'Company']),
            ])
            ->step(id: 'individual_details', label: 'Individual Details', structure: [
                TextField::make('first_name'),
            ])
            ->step(id: 'company_details', label: 'Company Details', structure: [
                TextField::make('company_name'),
            ])
            ->step(id: 'summary', label: 'Summary', structure: [])
            ->branch(from: 'applicant_type', map: ['individual' => 'individual_details', 'company' => 'company_details'])
            ->then('summary')
            ->build();
    }
}

final class BranchingRuntimeDataModel implements ArchitectDataModel
{
    public function forList(QueryContext $context): LengthAwarePaginator
    {
        return new ConcreteLengthAwarePaginator([['id' => 1, 'name' => 'Application 1']], 1, 25, 1);
    }

    /** @return array<string, mixed>|null */
    public function forForm(int $id): ?array
    {
        return ['id' => $id];
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return 1;
    }

    /** @param array<string, mixed> $input */
    public function modify(int $id, array $input): void {}

    public function archive(int $id, ?string $reason = null): void {}

    public function restore(int $id): void {}

    public function delete(int $id, ?string $reason = null): void {}

    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return Model::class;
    }
}
