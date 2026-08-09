<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\ArchitectFormDefinition;
use Entelechy\Architect\Forms\Fields\CardInputField;
use Entelechy\Architect\Forms\Fields\DialKnobField;
use Entelechy\Architect\Forms\Fields\HierarchicalCheckboxTreeField;
use Entelechy\Architect\Forms\Fields\KeyboardShortcutRecorderField;
use Entelechy\Architect\Forms\Fields\MaskedInputField;
use Entelechy\Architect\Forms\Fields\PasswordStrengthField;
use Entelechy\Architect\Forms\Fields\RankingField;
use Entelechy\Architect\Forms\Fields\SortableListField;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Forms\Contracts\ProvidesFormDefinition;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

/**
 * Per-field integration tests (ARCHITECT_IMPROVEMENT_PLAN.md Phase 1
 * Wave 1's "definition of done": prove each field round-trips a real
 * value through FormEngine's submit/validation, not just string-match
 * generated output). These fields render via wire:ignore + a hand-rolled
 * Alpine component (see resources/js/components/architectForms.js), so
 * the client-side drag/keypress/pointer interaction itself isn't
 * exercised here (Livewire::test() doesn't run a browser) — what this
 * proves is the PHP half of the contract every one of those Alpine
 * components relies on: $wire.set(wireField, value) lands in formData
 * and passes/fails validation exactly as the field's getRules() promises.
 */
class Wave1FieldIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    public function test_password_strength_field_enforces_min_length(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => PasswordStrengthFormDefinition::class])
            ->set('formData.password', 'short')
            ->call('submit')
            ->assertHasErrors(['formData.password']);

        Livewire::test(FormEngine::class, ['definitionClass' => PasswordStrengthFormDefinition::class])
            ->set('formData.password', 'a-genuinely-long-passphrase')
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_ranking_field_round_trips_a_reordered_array(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => RankingFormDefinition::class])
            ->set('formData.priority', ['b', 'a', 'c'])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(['b', 'a', 'c'], RankingFormDefinition::$savedData['priority']);
    }

    public function test_sortable_list_field_round_trips_a_reordered_array(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => SortableListFormDefinition::class])
            ->set('formData.order', ['c', 'a', 'b'])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(['c', 'a', 'b'], SortableListFormDefinition::$savedData['order']);
    }

    public function test_hierarchical_checkbox_tree_round_trips_selected_keys(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => CheckboxTreeFormDefinition::class])
            ->set('formData.permissions', ['parent', 'parent.child-a'])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(['parent', 'parent.child-a'], CheckboxTreeFormDefinition::$savedData['permissions']);
    }

    public function test_keyboard_shortcut_recorder_round_trips_a_combo_string(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => ShortcutFormDefinition::class])
            ->set('formData.shortcut', 'cmd+shift+k')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('cmd+shift+k', ShortcutFormDefinition::$savedData['shortcut']);
    }

    public function test_dial_knob_field_validates_within_min_and_max(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => DialKnobFormDefinition::class])
            ->set('formData.gain', 150)
            ->call('submit')
            ->assertHasErrors(['formData.gain']);

        Livewire::test(FormEngine::class, ['definitionClass' => DialKnobFormDefinition::class])
            ->set('formData.gain', 42.5)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(42.5, DialKnobFormDefinition::$savedData['gain']);
    }

    public function test_masked_input_field_enforces_the_configured_mask(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => MaskedInputFormDefinition::class])
            ->set('formData.sort_code', 'not-a-code')
            ->call('submit')
            ->assertHasErrors(['formData.sort_code']);

        Livewire::test(FormEngine::class, ['definitionClass' => MaskedInputFormDefinition::class])
            ->set('formData.sort_code', '12-34-56')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('12-34-56', MaskedInputFormDefinition::$savedData['sort_code']);
    }

    public function test_card_input_field_round_trips_the_providers_returned_token(): void
    {
        Livewire::test(FormEngine::class, ['definitionClass' => CardInputFormDefinition::class])
            ->set('formData.card', 'tok_provider_abc123')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('tok_provider_abc123', CardInputFormDefinition::$savedData['card']);
    }
}

final class PasswordStrengthFormDefinition implements ProvidesFormDefinition
{
    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-password-strength')
            ->structure([PasswordStrengthField::make('password')->minLength(12)])
            ->saveUsing(fn (array $data) => null)
            ->build();
    }
}

final class RankingFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-ranking')
            ->structure([RankingField::make('priority')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class SortableListFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-sortable-list')
            ->structure([SortableListField::make('order')->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class CheckboxTreeFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-checkbox-tree')
            ->structure([
                HierarchicalCheckboxTreeField::make('permissions')->tree([
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

final class ShortcutFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-shortcut')
            ->structure([KeyboardShortcutRecorderField::make('shortcut')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class DialKnobFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-dial-knob')
            ->structure([DialKnobField::make('gain')->min(0)->max(100)])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class MaskedInputFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-masked-input')
            ->structure([MaskedInputField::make('sort_code')->mask('99-99-99')])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}

final class CardInputFormDefinition implements ProvidesFormDefinition
{
    /** @var array<string, mixed> */
    public static array $savedData = [];

    public static function definition(): ArchitectFormDefinition
    {
        return FormBuilder::make('wave1-card-input')
            ->structure([
                CardInputField::make('card')
                    ->providerScriptUrl('https://example.test/provider.js')
                    ->publishableKey('pk_test_123'),
            ])
            ->saveUsing(function (array $data) {
                self::$savedData = $data;
            })
            ->build();
    }
}
