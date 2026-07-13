<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Exceptions\DuplicateFormKeyException;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

/**
 * Coverage matrix scenario: "multi-form page" (FORMS_FEATURE_PLAN.md Phase 7,
 * "mandatory coverage matrix tests for simple form, multi-form page, linear
 * wizard, branching wizard, and integrations"). Two independent forms
 * mounted together must not share state, and the FormKeyRegistry
 * uniqueness contract (FORMS_API_COMPATIBILITY_CONTRACT.md) must actually
 * be enforced at the FormEngine mount() level, not just against the
 * registry class in isolation.
 */
class MultiFormPageIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_two_distinct_forms_on_the_same_page_maintain_independent_state(): void
    {
        $newsletter = Livewire::test(FormEngine::class, ['definitionClass' => NewsletterSignupForm::class])
            ->set('formData.email', 'reader@example.com');

        $contact = Livewire::test(FormEngine::class, ['definitionClass' => ContactRequestForm::class])
            ->set('formData.subject', 'Question about membership');

        $newsletter->assertSet('formData.email', 'reader@example.com');
        $contact->assertSet('formData.subject', 'Question about membership');

        // Submitting one does not affect the other's fields.
        $newsletter->call('submit')->assertDispatched(\Entelechy\Architect\Forms\Events\FormEvents::SAVED);
        $contact->assertSet('formData.subject', 'Question about membership')
            ->assertNotDispatched(\Entelechy\Architect\Forms\Events\FormEvents::SAVED);
    }

    public function test_two_different_definition_classes_reusing_the_same_key_throws_at_mount(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => NewsletterSignupForm::class]);

        try {
            Livewire::test(FormEngine::class, ['definitionClass' => ConflictingKeyForm::class]);
            $this->fail('Expected a DuplicateFormKeyException to be thrown (possibly wrapped) during mount.');
        } catch (\Throwable $e) {
            // mount() runs during Livewire's initial render, so Laravel's
            // view engine may wrap the original exception in a
            // ViewException — assert on whichever one actually carries it.
            $root = $e;
            while ($root->getPrevious() !== null && ! $root instanceof DuplicateFormKeyException) {
                $root = $root->getPrevious();
            }

            $this->assertInstanceOf(DuplicateFormKeyException::class, $root);
            $this->assertStringContainsString("Form key 'newsletter-signup' is already registered", $root->getMessage());
        }
    }

    public function test_two_components_mounting_the_same_definition_class_is_not_a_conflict(): void
    {
        // The same form legitimately rendered twice on one page (e.g. a
        // shared "quick contact" widget in a header and a footer) must not
        // be treated as a key collision — only two *different* classes
        // reusing a key is an error.
        Livewire::test(FormEngine::class, ['definitionClass' => NewsletterSignupForm::class])
            ->assertSet('formData.email', null);

        Livewire::test(FormEngine::class, ['definitionClass' => NewsletterSignupForm::class])
            ->assertSet('formData.email', null);
    }
}

final class NewsletterSignupForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('newsletter-signup')
            ->structure([TextField::make('email')->required()])
            ->saveUsing(fn (array $data) => null)
            ->build();
    }
}

final class ContactRequestForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('contact-request')
            ->structure([TextField::make('subject')->required()])
            ->saveUsing(fn (array $data) => null)
            ->build();
    }
}

final class ConflictingKeyForm
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('newsletter-signup') // deliberately reuses NewsletterSignupForm's key
            ->structure([TextField::make('email')->required()])
            ->build();
    }
}
