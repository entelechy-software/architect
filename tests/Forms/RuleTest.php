<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Forms;

use Entelechy\Architect\Forms\Validation\Rule;
use Entelechy\Architect\Tests\TestCase;

class RuleTest extends TestCase
{
    public function test_simple_rules_compile_to_expected_strings(): void
    {
        $this->assertSame('required', Rule::required()->compile());
        $this->assertSame('nullable', Rule::nullable()->compile());
        $this->assertSame('email', Rule::email()->compile());
        $this->assertSame('string', Rule::string()->compile());
        $this->assertSame('integer', Rule::integer()->compile());
        $this->assertSame('boolean', Rule::boolean()->compile());
        $this->assertSame('array', Rule::array()->compile());
        $this->assertSame('date', Rule::date()->compile());
        $this->assertSame('confirmed', Rule::confirmed()->compile());
        $this->assertSame('distinct', Rule::distinct()->compile());
    }

    public function test_parameterized_rules_compile_correctly(): void
    {
        $this->assertSame('min:5', Rule::min(5)->compile());
        $this->assertSame('max:10.5', Rule::max(10.5)->compile());
        $this->assertSame('size:3', Rule::size(3)->compile());
        $this->assertSame('between:1,10', Rule::between(1, 10)->compile());
        $this->assertSame('date_format:d/m/Y', Rule::dateFormat('d/m/Y')->compile());
        $this->assertSame('in:a,b,c', Rule::in(['a', 'b', 'c'])->compile());
        $this->assertSame('not_in:a,b', Rule::notIn(['a', 'b'])->compile());
        $this->assertSame('regex:/^\d+$/', Rule::regex('/^\d+$/')->compile());
        $this->assertSame('after:start_date', Rule::after('start_date')->compile());
        $this->assertSame('after_or_equal:start_date', Rule::afterOrEqual('start_date')->compile());
        $this->assertSame('before:end_date', Rule::before('end_date')->compile());
        $this->assertSame('before_or_equal:end_date', Rule::beforeOrEqual('end_date')->compile());
        $this->assertSame('required_if:is_scheduled,1', Rule::requiredIf('is_scheduled', 1)->compile());
        $this->assertSame('required_unless:type,none', Rule::requiredUnless('type', 'none')->compile());
        $this->assertSame('required_with:a,b', Rule::requiredWith('a', 'b')->compile());
        $this->assertSame('required_without:a,b', Rule::requiredWithout('a', 'b')->compile());
        $this->assertSame('same:password', Rule::same('password')->compile());
        $this->assertSame('different:old_password', Rule::different('old_password')->compile());
        $this->assertSame('mimes:jpg,png', Rule::mimes(['jpg', 'png'])->compile());
        $this->assertSame('mimetypes:image/jpeg', Rule::mimetypes(['image/jpeg'])->compile());
    }

    public function test_raw_escape_hatch_passes_through_unchanged(): void
    {
        $this->assertSame('some_custom_rule:1,2', Rule::raw('some_custom_rule:1,2')->compile());
    }
}
