<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Fields\IntegerField;
use Entelechy\Architect\Forms\Fields\SelectField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Tests\TestCase;

class FieldValidationTest extends TestCase
{
    public function test_field_is_nullable_by_default(): void
    {
        $rules = TextField::make('x')->getRules();

        $this->assertContains('nullable', $rules);
    }

    public function test_required_adds_required_rule(): void
    {
        $rules = TextField::make('x')->required()->getRules();

        $this->assertContains('required', $rules);
        $this->assertNotContains('nullable', $rules);
    }

    public function test_text_field_includes_string_rule(): void
    {
        $rules = TextField::make('x')->getRules();

        $this->assertContains('string', $rules);
    }

    public function test_text_field_max_length_adds_max_rule(): void
    {
        $rules = TextField::make('x')->maxLength(50)->getRules();

        $this->assertContains('max:50', $rules);
    }

    public function test_text_field_without_max_has_no_max_rule(): void
    {
        $rules = TextField::make('x')->getRules();

        $this->assertEmpty(array_filter($rules, fn ($r) => str_starts_with((string) $r, 'max:')));
    }

    public function test_integer_field_includes_integer_rule(): void
    {
        $rules = IntegerField::make('x')->getRules();

        $this->assertContains('integer', $rules);
    }

    public function test_integer_field_min_adds_min_rule(): void
    {
        $rules = IntegerField::make('x')->min(1)->getRules();

        $this->assertContains('min:1', $rules);
    }

    public function test_integer_field_max_adds_max_rule(): void
    {
        $rules = IntegerField::make('x')->max(100)->getRules();

        $this->assertContains('max:100', $rules);
    }

    public function test_integer_field_min_and_max_together(): void
    {
        $rules = IntegerField::make('x')->min(0)->max(255)->getRules();

        $this->assertContains('min:0', $rules);
        $this->assertContains('max:255', $rules);
    }

    public function test_select_field_static_options_add_in_rule(): void
    {
        $rules = SelectField::make('x')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->getRules();

        $this->assertContains('in:active,inactive', $rules);
    }

    public function test_select_field_required_with_options(): void
    {
        $rules = SelectField::make('x')
            ->options(['a' => 'A', 'b' => 'B'])
            ->required()
            ->getRules();

        $this->assertContains('required', $rules);
        $this->assertContains('in:a,b', $rules);
    }

    public function test_select_field_closure_options_skip_in_rule(): void
    {
        $rules = SelectField::make('x')
            ->options(fn () => ['a' => 'A', 'b' => 'B'])
            ->getRules();

        $inRules = array_filter($rules, fn ($r) => str_starts_with((string) $r, 'in:'));
        $this->assertEmpty($inRules);
    }
}
