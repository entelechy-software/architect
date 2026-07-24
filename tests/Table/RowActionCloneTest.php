<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Table\AbstractEloquentDataModel;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class RowActionCloneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        Schema::create('clone_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('clone_widgets');

        parent::tearDown();
    }

    public function test_clone_row_action_duplicates_the_record_via_default_data_model(): void
    {
        $widget = CloneWidgetModel::query()->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        Livewire::test(\Entelechy\Architect\Table\Livewire\Engine::class, ['definitionClass' => CloneWidgetTableDefinition::class])
            ->call('handleRowAction', 'clone', $widget->getKey())
            ->assertSet('rowActionMessage', 'Record cloned successfully.')
            ->assertSet('rowActionError', null);

        $this->assertSame(2, CloneWidgetModel::query()->count());

        $copy = CloneWidgetModel::query()->where('id', '!=', $widget->getKey())->firstOrFail();
        $this->assertSame('Ada', $copy->name);
        // 'email' is declared via ->clonable(['email']) and must not be copied.
        $this->assertNull($copy->email);
    }
}

final class CloneWidgetModel extends Model
{
    protected $table = 'clone_widgets';

    protected $guarded = [];
}

final class CloneWidgetDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return CloneWidgetModel::class;
    }
}

final class CloneWidgetTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('Clone Widgets')
            ->model(CloneWidgetDataModel::class)
            ->permissions(
                read: 'clone-widgets.read',
                create: 'clone-widgets.create',
                modify: 'clone-widgets.modify',
                remove: 'clone-widgets.remove',
            )
            ->column(Column::make('name')->label('Name')->type('text')->sortable())
            ->clonable(['email'])
            ->build();
    }
}
