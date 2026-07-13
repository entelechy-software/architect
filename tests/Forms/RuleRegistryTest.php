<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Validation\Rule;
use Entelechy\Architect\Forms\Validation\RuleRegistry;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class RuleRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        RuleRegistry::reset();

        parent::tearDown();
    }

    public function test_register_and_resolve(): void
    {
        RuleRegistry::register(
            'uk_mobile',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ! preg_match('/^\+44\d{10}$/', $value)) {
                    $fail('The :attribute must be a valid UK mobile number.');
                }
            }
        );

        $this->assertTrue(RuleRegistry::has('uk_mobile'));

        $resolved = RuleRegistry::resolve('uk_mobile');
        $validator = Validator::make(['phone' => '+447700900000'], ['phone' => [$resolved]]);
        $this->assertTrue($validator->passes());

        $failing = Validator::make(['phone' => 'not-a-number'], ['phone' => [$resolved]]);
        $this->assertTrue($failing->fails());
    }

    public function test_resolving_unregistered_rule_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RuleRegistry::resolve('does_not_exist');
    }

    public function test_dsl_custom_rule_integrates_with_validator(): void
    {
        RuleRegistry::register(
            'always_fails',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $fail('The :attribute is never valid.');
            }
        );

        $rule = Rule::custom('always_fails');
        $validator = Validator::make(['x' => 'anything'], ['x' => [$rule->compile()]]);

        $this->assertTrue($validator->fails());
    }

    public function test_reset_clears_registry(): void
    {
        RuleRegistry::register('temp', function (string $a, mixed $v, \Closure $fail): void {});
        RuleRegistry::reset();

        $this->assertFalse(RuleRegistry::has('temp'));
    }
}
