<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Fields\CurrencyField;
use Entelechy\Architect\Forms\Fields\DateRangeField;
use Entelechy\Architect\Forms\Fields\OtpField;
use Entelechy\Architect\Forms\Fields\PercentageField;
use Entelechy\Architect\Forms\Fields\PhoneField;
use Entelechy\Architect\Forms\Fields\RatingField;
use Entelechy\Architect\Tests\TestCase;

class NewControlFieldsTest extends TestCase
{
    public function test_currency_field_rules(): void
    {
        $rules = CurrencyField::make('amount')->min(0)->max(1000)->getRules();

        $this->assertContains('numeric', $rules);
        $this->assertContains('min:0', $rules);
        $this->assertContains('max:1000', $rules);
    }

    public function test_currency_field_defaults(): void
    {
        $field = CurrencyField::make('amount');

        $this->assertSame('GBP', $field->getCurrency());
        $this->assertSame(2, $field->getDecimals());
    }

    public function test_percentage_field_rules_default_bounds(): void
    {
        $rules = PercentageField::make('completion')->getRules();

        $this->assertContains('numeric', $rules);
        $this->assertContains('min:0', $rules);
        $this->assertContains('max:100', $rules);
    }

    public function test_date_range_field_rules(): void
    {
        $rules = DateRangeField::make('availability')->getRules();

        $this->assertContains('array', $rules);
        $this->assertContains('size:2', $rules);
    }

    public function test_phone_field_rules(): void
    {
        $rules = PhoneField::make('mobile')->getRules();

        $this->assertContains('string', $rules);
        $this->assertContains('regex:/^\+?[1-9]\d{6,14}$/', $rules);
    }

    public function test_otp_field_rules_use_configured_length(): void
    {
        $rules = OtpField::make('code')->length(4)->getRules();

        $this->assertContains('size:4', $rules);
        $this->assertContains('regex:/^\d+$/', $rules);
    }

    public function test_rating_field_rules_use_configured_max(): void
    {
        $rules = RatingField::make('score')->max(10)->getRules();

        $this->assertContains('integer', $rules);
        $this->assertContains('min:1', $rules);
        $this->assertContains('max:10', $rules);
    }

    public function test_all_new_fields_report_correct_view_names(): void
    {
        $this->assertSame('architect::forms.fields.currency', CurrencyField::make('x')->getViewName());
        $this->assertSame('architect::forms.fields.percentage', PercentageField::make('x')->getViewName());
        $this->assertSame('architect::forms.fields.date-range', DateRangeField::make('x')->getViewName());
        $this->assertSame('architect::forms.fields.phone', PhoneField::make('x')->getViewName());
        $this->assertSame('architect::forms.fields.otp', OtpField::make('x')->getViewName());
        $this->assertSame('architect::forms.fields.rating', RatingField::make('x')->getViewName());
    }
}
