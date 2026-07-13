<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Validation\ClientValidationMapper;
use Entelechy\Architect\Forms\Validation\DefaultProfileRegistry;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidationSupportTest extends TestCase
{
    public function test_default_profile_registry_returns_expected_baseline(): void
    {
        $this->assertSame(['string'], DefaultProfileRegistry::forValueType('string'));
        $this->assertSame(['integer'], DefaultProfileRegistry::forValueType('integer'));
        $this->assertSame(['numeric'], DefaultProfileRegistry::forValueType('decimal'));
        $this->assertSame([], DefaultProfileRegistry::forValueType('unknown-type'));
        $this->assertTrue(DefaultProfileRegistry::has('boolean'));
        $this->assertFalse(DefaultProfileRegistry::has('unknown-type'));
    }

    public function test_client_validation_mapper_maps_common_rules(): void
    {
        $attributes = ClientValidationMapper::toHtmlAttributes(['required', 'email', 'min:5', 'max:20']);

        $this->assertTrue($attributes['required']);
        $this->assertSame('email', $attributes['type']);
        $this->assertSame('5', $attributes['min']);
        $this->assertSame('20', $attributes['max']);
    }

    public function test_client_validation_mapper_strips_regex_delimiters_for_pattern(): void
    {
        $attributes = ClientValidationMapper::toHtmlAttributes(['regex:/^\d+$/']);

        $this->assertSame('^\d+$', $attributes['pattern']);
    }

    public function test_client_validation_mapper_ignores_non_string_rules(): void
    {
        $attributes = ClientValidationMapper::toHtmlAttributes(['required', new class implements ValidationRule
        {
            public function validate(string $attribute, mixed $value, \Closure $fail): void {}
        }]);

        $this->assertTrue($attributes['required']);
        $this->assertCount(1, $attributes);
    }

    public function test_client_validation_mapper_ignores_unmapped_rules(): void
    {
        $attributes = ClientValidationMapper::toHtmlAttributes(['confirmed', 'distinct']);

        $this->assertSame([], $attributes);
    }
}
