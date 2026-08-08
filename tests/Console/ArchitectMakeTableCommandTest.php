<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

class ArchitectMakeTableCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Modules'));

        foreach (File::glob(database_path('migrations/*_create_members_table.php')) as $migration) {
            File::delete($migration);
        }

        parent::tearDown();
    }

    public function test_scaffolds_table_definition_data_model_and_migration(): void
    {
        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'Member',
            '--module' => 'Members',
        ])->assertExitCode(0);

        $definitionPath = app_path('Modules/Members/Components/Tables/MembersTableDefinition.php');
        $modelPath = app_path('Modules/Members/Models/Member.php');
        $dataModelPath = app_path('Modules/Members/Models/MemberDataModel.php');

        $this->assertFileExists($definitionPath);
        $this->assertFileExists($modelPath);
        $this->assertFileExists($dataModelPath);

        $definitionContents = File::get($definitionPath);
        $this->assertStringContainsString('namespace App\Modules\Members\Components\Tables;', $definitionContents);
        $this->assertStringContainsString('final class MembersTableDefinition', $definitionContents);
        $this->assertStringContainsString('use App\Modules\Members\Models\MemberDataModel;', $definitionContents);
        $this->assertStringContainsString('->model(MemberDataModel::class)', $definitionContents);
        $this->assertStringContainsString("read: 'members.read',", $definitionContents);

        $modelContents = File::get($modelPath);
        $this->assertStringContainsString('namespace App\Modules\Members\Models;', $modelContents);
        $this->assertStringContainsString('class Member extends Model', $modelContents);
        $this->assertStringContainsString("protected \$table = 'members';", $modelContents);

        $dataModelContents = File::get($dataModelPath);
        $this->assertStringContainsString('namespace App\Modules\Members\Models;', $dataModelContents);
        $this->assertStringContainsString('class MemberDataModel extends AbstractEloquentDataModel', $dataModelContents);
        $this->assertStringContainsString('return Member::class;', $dataModelContents);

        $migrations = File::glob(database_path('migrations/*_create_members_table.php'));
        $this->assertNotEmpty($migrations);
        $this->assertStringContainsString("Schema::create('members'", File::get($migrations[0]));
    }

    public function test_fails_without_overwriting_existing_files_unless_forced(): void
    {
        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'Member',
            '--module' => 'Members',
        ])->assertExitCode(0);

        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'Member',
            '--module' => 'Members',
        ])->assertExitCode(1);

        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'Member',
            '--module' => 'Members',
            '--force' => true,
        ])->assertExitCode(0);
    }

    public function test_rejects_non_pascal_case_module_and_model(): void
    {
        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--module' => 'not-pascal',
        ])->assertExitCode(1);

        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'not-pascal',
        ])->assertExitCode(1);
    }

    /**
     * End-to-end smoke test: generate a table exactly the way a user does
     * (no manual edits, no data-model overrides) and drive the real
     * Livewire components through create, edit, and search — the three
     * flows a "first CRUD table" walkthrough promises work out of the box.
     *
     * This exists because each of those three flows was independently
     * broken by a latent bug (missing formMode()/column type(), an
     * over-eager field-visibility strip, and a no-op default search) that
     * unit tests exercising each mechanism in isolation never caught.
     */
    public function test_generated_table_supports_create_edit_and_search_out_of_the_box(): void
    {
        $this->artisan('make:architect-table', [
            'name' => 'Members',
            '--model' => 'Member',
            '--module' => 'Members',
        ])->assertExitCode(0);

        $migrations = File::glob(database_path('migrations/*_create_members_table.php'));
        $this->assertNotEmpty($migrations);
        (require $migrations[0])->up();

        // The testbench sandbox has no PSR-4 mapping for `App\`, unlike a
        // real consuming application, so the generated classes must be
        // required manually (model before data model before definition).
        require_once app_path('Modules/Members/Models/Member.php');
        require_once app_path('Modules/Members/Models/MemberDataModel.php');
        require_once app_path('Modules/Members/Components/Tables/MembersTableDefinition.php');

        $definitionClass = 'App\Modules\Members\Components\Tables\MembersTableDefinition';
        $modelClass = 'App\Modules\Members\Models\Member';

        // Create: the "New" panel must expose an editable Name field and
        // persist it — not silently render zero fields.
        Livewire::test(FormPanel::class, ['definitionClass' => $definitionClass])
            ->call('openCreate', $definitionClass)
            ->assertSet('panelState', 'create')
            ->set('form.name', 'Ada Lovelace')
            ->call('submit')
            ->assertDispatched('architect:created');

        /** @var Model $member */
        $member = $modelClass::query()->firstOrFail();
        $this->assertSame('Ada Lovelace', $member->name);

        // Edit: opening the panel must pre-populate the current value, not
        // strip it down to just the id.
        Livewire::test(FormPanel::class, ['definitionClass' => $definitionClass])
            ->call('openEdit', $definitionClass, $member->getKey())
            ->assertSet('panelState', 'edit')
            ->assertSet('form.name', 'Ada Lovelace');

        // Search: free-text search over ->searchable() columns must filter
        // the list with zero data-model overrides required.
        $modelClass::query()->create(['name' => 'Bob Someone Else']);

        Livewire::test(Engine::class, ['definitionClass' => $definitionClass])
            ->set('search', 'Ada')
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Bob Someone Else');
    }
}
