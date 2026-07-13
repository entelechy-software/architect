<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Panels;

use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Panels\ArchitectDashboardDefinition;
use Entelechy\Architect\Panels\DashboardBuilder;
use Entelechy\Architect\Panels\Livewire\PanelEngine;
use Entelechy\Architect\Panels\Panels\QuickFormPanel;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class PanelEngineQuickFormHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        QuickFormHooksSuccessDashboard::reset();
        QuickFormHooksFailureDashboard::reset();
    }

    public function test_submit_dispatches_custom_event_and_calls_success_hook(): void
    {
        Livewire::test(PanelEngine::class, ['definitionClass' => QuickFormHooksSuccessDashboard::class])
            ->set('formData.title', 'Hello world')
            ->call('submitQuickForm', 0)
            ->assertDispatched('architect:custom:panel-refresh');

        $this->assertTrue(QuickFormHooksSuccessDashboard::$successCalled);
        $this->assertFalse(QuickFormHooksSuccessDashboard::$failureCalled);
    }

    public function test_submit_calls_failure_hook_and_rethrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('save failed');

        try {
            Livewire::test(PanelEngine::class, ['definitionClass' => QuickFormHooksFailureDashboard::class])
                ->set('formData.title', 'Hello world')
                ->call('submitQuickForm', 0);
        } finally {
            $this->assertTrue(QuickFormHooksFailureDashboard::$failureCalled);
        }
    }
}

final class QuickFormHooksSuccessDashboard
{
    public static bool $successCalled = false;

    public static bool $failureCalled = false;

    public static function reset(): void
    {
        self::$successCalled = false;
        self::$failureCalled = false;
    }

    public static function definition(): ArchitectDashboardDefinition
    {
        return DashboardBuilder::make('quick-form-hooks-success')
            ->panel(
                QuickFormPanel::make()
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
                    ->onSavedDispatch('architect:custom:panel-refresh', ['foo' => 'bar']),
            )
            ->build();
    }
}

final class QuickFormHooksFailureDashboard
{
    public static bool $successCalled = false;

    public static bool $failureCalled = false;

    public static function reset(): void
    {
        self::$successCalled = false;
        self::$failureCalled = false;
    }

    public static function definition(): ArchitectDashboardDefinition
    {
        return DashboardBuilder::make('quick-form-hooks-failure')
            ->panel(
                QuickFormPanel::make()
                    ->structure([TextField::make('title')->required()])
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
                    ),
            )
            ->build();
    }
}
