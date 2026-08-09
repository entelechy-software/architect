<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Fields\DualListboxField;
use Entelechy\Architect\Forms\Fields\EntityPickerField;
use Entelechy\Architect\Forms\Fields\GradientEditorField;
use Entelechy\Architect\Forms\Fields\ImageComparisonSliderField;
use Entelechy\Architect\Forms\Fields\ImageCropperField;
use Entelechy\Architect\Forms\Fields\KanbanBoardField;
use Entelechy\Architect\Forms\Fields\MentionEditorField;
use Entelechy\Architect\Forms\Fields\RegexBuilderTesterField;
use Entelechy\Architect\Forms\Fields\RelationshipPickerField;
use Entelechy\Architect\Forms\Fields\TemplateEditorField;
use Entelechy\Architect\Forms\Fields\TreeSelectField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Forms\Contracts\ProvidesFormDefinition;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Per-field integration tests for ARCHITECT_IMPROVEMENT_PLAN.md Phase 1
 * Wave 2's "small, focused third-party JS dependency" tier — same
 * approach as Wave1FieldIntegrationTest: prove the PHP half of the
 * contract every hand-rolled Alpine component in
 * resources/js/components/architectForms.js relies on
 * ($wire.set(wireField, value) lands in formData and passes/fails
 * validation exactly as the field's getRules() promises). Client-side
 * drag/crop/mention interaction itself isn't exercised here — that would
 * require a real browser.
 */
class Wave2FieldIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_kanban_board_field_round_trips_a_column_arrangement(): void
    {
        $board = ['todo' => ['task-2'], 'done' => ['task-1']];

        Livewire::test(FormEngine::class, ['definitionClass' => KanbanBoardFormDefinition::class])
            ->set('formData.board', $board)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($board, KanbanBoardFormDefinition::$savedData['board']);
    }

    public function test_image_cropper_field_accepts_a_cropped_image_upload(): void
    {
        Storage::fake('public');

        Livewire::test(FormEngine::class, ['definitionClass' => ImageCropperFormDefinition::class])
            ->set('formData.photo', UploadedFile::fake()->image('cropped.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNotNull(ImageCropperFormDefinition::$savedData['photo']);
    }

    public function test_image_comparison_slider_field_validates_within_zero_and_one_hundred(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => ImageComparisonSliderFormDefinition::class])
            ->set('formData.position', 150)
            ->call('submit')
            ->assertHasErrors(['formData.position']);

        Livewire::test(FormEngine::class, ['definitionClass' => ImageComparisonSliderFormDefinition::class])
            ->set('formData.position', 37.5)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(37.5, ImageComparisonSliderFormDefinition::$savedData['position']);
    }

    public function test_gradient_editor_field_round_trips_angle_and_stops(): void
    {
        $gradient = ['angle' => 45, 'stops' => [['color' => '#3b82f6', 'position' => 0], ['color' => '#8b5cf6', 'position' => 100]]];

        Livewire::test(FormEngine::class, ['definitionClass' => GradientEditorFormDefinition::class])
            ->set('formData.gradient', $gradient)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($gradient, GradientEditorFormDefinition::$savedData['gradient']);
    }

    public function test_entity_picker_field_enforces_an_integer_id(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => EntityPickerFormDefinition::class])
            ->set('formData.author', 'not-an-id')
            ->call('submit')
            ->assertHasErrors(['formData.author']);

        Livewire::test(FormEngine::class, ['definitionClass' => EntityPickerFormDefinition::class])
            ->set('formData.author', 42)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(42, EntityPickerFormDefinition::$savedData['author']);
    }

    public function test_relationship_picker_field_round_trips_a_type_and_id(): void
    {
        $relationship = ['type' => 'event', 'id' => 7];

        Livewire::test(FormEngine::class, ['definitionClass' => RelationshipPickerFormDefinition::class])
            ->set('formData.related', $relationship)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($relationship, RelationshipPickerFormDefinition::$savedData['related']);
    }

    public function test_tree_select_field_round_trips_a_selected_node_key(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => TreeSelectFormDefinition::class])
            ->set('formData.category', 'parent.child-a')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('parent.child-a', TreeSelectFormDefinition::$savedData['category']);
    }

    public function test_dual_listbox_field_enforces_the_configured_option_keys(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => DualListboxFormDefinition::class])
            ->set('formData.roles', ['not-an-option'])
            ->call('submit')
            ->assertHasErrors(['formData.roles']);

        Livewire::test(FormEngine::class, ['definitionClass' => DualListboxFormDefinition::class])
            ->set('formData.roles', ['editor', 'admin'])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(['editor', 'admin'], DualListboxFormDefinition::$savedData['roles']);
    }

    public function test_template_editor_field_round_trips_a_template_string(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => TemplateEditorFormDefinition::class])
            ->set('formData.body', 'Hello {{ first_name }}!')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Hello {{ first_name }}!', TemplateEditorFormDefinition::$savedData['body']);
    }

    public function test_mention_editor_field_round_trips_html_with_mention_tokens(): void
    {
        $html = 'Hey <span data-mention="42">@Jane</span>, thoughts?';

        Livewire::test(FormEngine::class, ['definitionClass' => MentionEditorFormDefinition::class])
            ->set('formData.note', $html)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($html, MentionEditorFormDefinition::$savedData['note']);
    }

    public function test_regex_builder_tester_field_round_trips_a_pattern_and_flags(): void
    {
        $regex = ['pattern' => '\\d+', 'flags' => 'g'];

        Livewire::test(FormEngine::class, ['definitionClass' => RegexBuilderTesterFormDefinition::class])
            ->set('formData.matcher', $regex)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame($regex, RegexBuilderTesterFormDefinition::$savedData['matcher']);
    }
}

final class KanbanBoardFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-kanban-board')
            ->structure([
                KanbanBoardField::make('board')
                    ->columns(['todo', 'done'])
                    ->items(['task-1' => 'Task One', 'task-2' => 'Task Two']),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class ImageCropperFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-image-cropper')
            ->structure([ImageCropperField::make('photo')->aspectRatio(1.0)])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class ImageComparisonSliderFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-image-comparison-slider')
            ->structure([
                ImageComparisonSliderField::make('position')
                    ->beforeImageUrl('https://example.test/before.jpg')
                    ->afterImageUrl('https://example.test/after.jpg'),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class GradientEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-gradient-editor')
            ->structure([GradientEditorField::make('gradient')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class EntityPickerFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-entity-picker')
            ->structure([EntityPickerField::make('author')->searchUrl('/api/authors/search')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class RelationshipPickerFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-relationship-picker')
            ->structure([
                RelationshipPickerField::make('related')
                    ->allowedTypes(['event', 'committee'])
                    ->searchUrl('/api/relationships/search'),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class TreeSelectFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-tree-select')
            ->structure([
                TreeSelectField::make('category')->tree([
                    ['key' => 'parent', 'label' => 'Parent', 'children' => [
                        ['key' => 'parent.child-a', 'label' => 'Child A'],
                        ['key' => 'parent.child-b', 'label' => 'Child B'],
                    ]],
                ]),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class DualListboxFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-dual-listbox')
            ->structure([
                DualListboxField::make('roles')->options(['editor' => 'Editor', 'admin' => 'Admin', 'viewer' => 'Viewer']),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class TemplateEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-template-editor')
            ->structure([TemplateEditorField::make('body')->availableVariables(['first_name', 'last_name'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class MentionEditorFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-mention-editor')
            ->structure([MentionEditorField::make('note')->mentionableUrl('/api/mentionables/search')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class RegexBuilderTesterFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave2-regex-builder-tester')
            ->structure([RegexBuilderTesterField::make('matcher')->sampleText('call 555-1234 or 555-5678')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}
