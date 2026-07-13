<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Events\FormEvents;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class FormEngineHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        HooksSuccessFormDefinition::reset();
        HooksFailureFormDefinition::reset();
    }

    public function test_submit_dispatches_custom_event_and_calls_success_hook(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => HooksSuccessFormDefinition::class])
            ->set('formData.title', 'Hello world')
            ->call('submit')
            ->assertDispatched('architect:custom:refresh')
            ->assertDispatched(FormEvents::SAVED);

        $this->assertTrue(HooksSuccessFormDefinition::$successCalled);
        $this->assertFalse(HooksSuccessFormDefinition::$failureCalled);
    }

    public function test_submit_calls_failure_hook_and_rethrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('save failed');

        try {
            Livewire::test(FormEngine::class, ['definitionClass' => HooksFailureFormDefinition::class])
                ->set('formData.title', 'Hello world')
                ->call('submit');
        } finally {
            $this->assertTrue(HooksFailureFormDefinition::$failureCalled);
        }
    }

    public function test_autosave_does_not_rethrow_on_failure_but_still_calls_hook(): void
    {
        HooksFailureFormDefinition::reset();

        Livewire::test(FormEngine::class, ['definitionClass' => HooksFailureFormDefinition::class])
            ->set('formData.title', 'Hello world')
            ->call('autosave');

        $this->assertTrue(HooksFailureFormDefinition::$failureCalled);
    }
}

final class HooksSuccessFormDefinition
{
    public static bool $successCalled = false;

    public static bool $failureCalled = false;

    public static function reset(): void
    {
        self::$successCalled = false;
        self::$failureCalled = false;
    }

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('hooks-success')
            ->structure([TextField::make('title')->required()])
            ->saveUsing(fn (array $data) => null)
            ->notifyOnSave(
                success: function () {
                    self::$successCalled = true;
                },
                failure: function () {
                    self::$failureCalled = true;
                },
            )
            ->onSavedDispatch('architect:custom:refresh', ['foo' => 'bar'])
            ->build();
    }
}

final class HooksFailureFormDefinition
{
    public static bool $successCalled = false;

    public static bool $failureCalled = false;

    public static function reset(): void
    {
        self::$successCalled = false;
        self::$failureCalled = false;
    }

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('hooks-failure')
            ->structure([TextField::make('title')->required()])
            ->autosave()
            ->saveUsing(function (array $data): void {
                throw new \RuntimeException('save failed');
            })
            ->notifyOnSave(
                success: function () {
                    self::$successCalled = true;
                },
                failure: function () {
                    self::$failureCalled = true;
                },
            )
            ->build();
    }
}
