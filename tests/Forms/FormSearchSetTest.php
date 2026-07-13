<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\FormSearchSet;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;

class FormSearchSetTest extends TestCase
{
    public function test_for_builds_a_navigation_search_set_from_an_exposed_form(): void
    {
        $set = FormSearchSet::for(ExposedQuickNoteForm::class, url: '/notes/create');

        $this->assertNotNull($set);
    }

    public function test_for_throws_when_form_never_exposed_itself(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('never called ->exposeToSupersearch()');

        FormSearchSet::for(UnexposedQuickNoteForm::class, url: '/notes/create');
    }

    public function test_for_works_for_wizard_definitions_too(): void
    {
        $set = FormSearchSet::for(ExposedOnboardingWizard::class, url: '/onboarding');

        $this->assertNotNull($set);
    }

    public function test_for_propagates_permission_and_filters_denied_users(): void
    {
        $set = FormSearchSet::for(
            ExposedQuickNoteForm::class,
            url: '/notes/create',
            permission: 'notes.create',
        );

        $user = $this->createMock(Authenticatable::class);

        $denyingResolver = new class implements PermissionResolver
        {
            public function can(?Authenticatable $user, string $node): bool
            {
                return false;
            }

            public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
            {
                return false;
            }
        };

        $this->assertSame([], $set->resolveResults('Quick Note', $user, $denyingResolver));

        $allowingResolver = new class implements PermissionResolver
        {
            public function can(?Authenticatable $user, string $node): bool
            {
                return true;
            }

            public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
            {
                return true;
            }
        };

        $results = $set->resolveResults('Quick Note', $user, $allowingResolver);

        $this->assertCount(1, $results);
        $this->assertSame('Create Quick Note', $results[0]['title']);
    }
}

final class ExposedQuickNoteForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('quick-note')
            ->structure([TextField::make('title')->required()])
            ->exposeToSupersearch('Create Quick Note')
            ->build();
    }
}

final class UnexposedQuickNoteForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('quick-note-unexposed')
            ->structure([TextField::make('title')->required()])
            ->build();
    }
}

final class ExposedOnboardingWizard
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('onboarding-exposed')
            ->step('Only', [TextField::make('name')])
            ->exposeToSupersearch('Start Onboarding')
            ->build();
    }
}
