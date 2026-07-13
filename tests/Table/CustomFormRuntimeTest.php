<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\ArchitectWizardDefinition;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Livewire\Livewire;

class CustomFormRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_form_panel_resolves_custom_form_engine_component(): void
    {
        Livewire::test(FormPanel::class, ['definitionClass' => RuntimeTableDefinition::class])
            ->call(
                'openCustomForm',
                definitionClass: RuntimeTableDefinition::class,
                title: 'Create Runtime Widget',
                customDefinitionClass: RuntimeFormDefinition::class,
                customMode: 'modal',
            )
            ->assertSet('panelState', 'custom')
            ->assertSet('open', true)
            ->assertSet('customData.engineComponent', 'architect-form-engine');
    }

    public function test_form_panel_resolves_custom_wizard_engine_component(): void
    {
        Livewire::test(FormPanel::class, ['definitionClass' => RuntimeTableDefinition::class])
            ->call(
                'openCustomForm',
                definitionClass: RuntimeTableDefinition::class,
                title: 'Edit Runtime Widget',
                customDefinitionClass: RuntimeWizardDefinition::class,
                customMode: 'slide-over',
                recordId: 7,
            )
            ->assertSet('panelState', 'custom')
            ->assertSet('open', true)
            ->assertSet('recordId', 7)
            ->assertSet('customData.engineComponent', 'architect-wizard-engine');
    }

    public function test_engine_renders_custom_form_return_hook_options_and_new_window_metadata(): void
    {
        Livewire::test(Engine::class, ['definitionClass' => RuntimeTableDefinition::class])
            ->assertSee('customFormReturnQueryKeys')
            ->assertSee('arch_refresh')
            ->assertSee('modify_refresh')
            ->assertSee('customFormPostMessageEnabled: true')
            ->assertSee('architect_table_instance')
            ->assertSee('architect_table_refresh_key')
            ->assertSee('architect_table_return_url');
    }
}

final class RuntimeTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('Runtime Widgets')
            ->model(RuntimeStubDataModel::class)
            ->permissions(
                read: 'runtime.widgets.read',
                create: 'runtime.widgets.create',
                modify: 'runtime.widgets.modify',
                remove: 'runtime.widgets.remove',
            )
            ->customForm(
                for: 'create',
                definitionClass: RuntimeFormDefinition::class,
                mode: 'new-window',
                url: '/admin/runtime-widgets/create',
                callbackQueryKey: 'arch_refresh',
                postMessageRefresh: false,
            )
            ->customForm(
                for: 'modify',
                definitionClass: RuntimeWizardDefinition::class,
                mode: 'new-window',
                url: '/admin/runtime-widgets/{id}/edit',
                callbackQueryKey: 'modify_refresh',
                postMessageRefresh: true,
            )
            ->build();
    }
}

final class RuntimeStubDataModel implements ArchitectDataModel
{
    public function forList(QueryContext $context): LengthAwarePaginator
    {
        $row = ['id' => 7, 'name' => 'Widget 7'];

        return new ConcreteLengthAwarePaginator([$row], 1, 25, 1);
    }

    /** @return array<string, mixed>|null */
    public function forForm(int $id): ?array
    {
        return ['id' => $id, 'name' => 'Widget '.$id];
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return 7;
    }

    /** @param array<string, mixed> $input */
    public function modify(int $id, array $input): void
    {
        // no-op in runtime assertions
    }

    public function archive(int $id, ?string $reason = null): void
    {
        // no-op in runtime assertions
    }

    public function restore(int $id): void
    {
        // no-op in runtime assertions
    }

    public function delete(int $id, ?string $reason = null): void
    {
        // no-op in runtime assertions
    }

    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return Model::class;
    }
}

final class RuntimeFormDefinition
{
    public static function definition(): ArchitectFormDefinition
    {
        return new ArchitectFormDefinition(
            key: 'runtime-form',
            structure: [],
            saveUsing: null,
            fillData: null,
        );
    }
}

final class RuntimeWizardDefinition
{
    public static function definition(): ArchitectWizardDefinition
    {
        return WizardBuilder::make('runtime-wizard')
            ->step(id: 'step-1', label: 'Step 1', structure: [])
            ->build();
    }
}
