<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Fields\DateField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Validation\Preset;
use Entelechy\Architect\Forms\Validation\Rule;
use Entelechy\Architect\Tests\TestCase;

class FieldValidateMethodTest extends TestCase
{
    public function test_validate_with_no_preset_does_not_change_existing_rules(): void
    {
        $withoutValidate = TextField::make('email')->maxLength(255)->getRules();
        $withValidate = TextField::make('email')->maxLength(255)->validate()->getRules();

        $this->assertSame($withoutValidate, $withValidate);
    }

    public function test_validate_with_preset_merges_preset_rules_additively(): void
    {
        $rules = TextField::make('email')->validate(Preset::workEmail())->getRules();

        $this->assertContains('string', $rules);
        $this->assertContains('email', $rules);
        $this->assertContains('not_regex:/@(gmail|yahoo|hotmail|outlook|icloud|aol)\.com$/i', $rules);
    }

    public function test_ruleset_is_additive_not_replacing(): void
    {
        $rules = DateField::make('end_date')
            ->rules('some_base_rule')
            ->ruleset([
                Rule::requiredIf('is_scheduled', 1),
                Rule::after('start_date'),
            ])
            ->getRules();

        $this->assertContains('some_base_rule', $rules);
        $this->assertContains('required_if:is_scheduled,1', $rules);
        $this->assertContains('after:start_date', $rules);
        // Base DateField default (date_format) still present alongside.
        $this->assertContains('date_format:d/m/Y', $rules);
    }

    public function test_ruleset_and_validate_compose_together(): void
    {
        $rules = TextField::make('email')
            ->validate(Preset::workEmail())
            ->ruleset([Rule::max(255)])
            ->getRules();

        $this->assertContains('email', $rules);
        $this->assertContains('max:255', $rules);
    }
}
