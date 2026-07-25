<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Table\AbstractEloquentDataModel;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class RowActionViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        Schema::create('view_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('internal_ref')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('view_widgets');

        parent::tearDown();
    }

    public function test_engine_dispatches_open_view_for_the_view_row_action(): void
    {
        $widget = ViewWidgetModel::query()->create(['name' => 'Ada', 'internal_ref' => 'REF-1']);

        Livewire::test(Engine::class, ['definitionClass' => ViewWidgetTableDefinition::class])
            ->call('handleRowAction', 'view', $widget->getKey())
            ->assertDispatched('architect:open-view', definitionClass: ViewWidgetTableDefinition::class, id: $widget->getKey());
    }

    public function test_view_panel_falls_back_to_a_generic_label_value_list_built_from_columns(): void
    {
        $widget = ViewWidgetModel::query()->create(['name' => 'Ada', 'internal_ref' => 'REF-1']);

        Livewire::test(FormPanel::class, ['definitionClass' => ViewWidgetTableDefinition::class])
            ->call('openView', ViewWidgetTableDefinition::class, $widget->getKey())
            ->assertSet('panelState', 'view')
            ->assertSet('viewRecord', [
                ['label' => 'ID', 'value' => $widget->getKey()],
                ['label' => 'Name', 'value' => 'Ada'],
            ]);
    }
}

final class ViewWidgetModel extends Model
{
    protected $table = 'view_widgets';

    protected $guarded = [];
}

final class ViewWidgetDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return ViewWidgetModel::class;
    }
}

final class ViewWidgetTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('View Widgets')
            ->model(ViewWidgetDataModel::class)
            ->permissions(
                read: 'view-widgets.read',
                create: 'view-widgets.create',
                modify: 'view-widgets.modify',
                remove: 'view-widgets.remove',
            )
            ->column(Column::make('name')->label('Name')->type('text')->sortable())
            ->column(Column::make('internal_ref')->label('Internal Ref')->hideOnIndex())
            ->viewable()
            ->build();
    }
}
