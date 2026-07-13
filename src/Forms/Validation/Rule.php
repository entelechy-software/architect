<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

use Illuminate\Contracts\Validation\ValidationRule as LaravelValidationRule;

/**
 * Architect's fluent validation DSL — every method compiles 1:1 to a
 * native Laravel validation rule (string or ValidationRule object), so
 * there is never a behavior gap between the DSL and hand-written
 * ->rules('...') strings (FORMS_FEATURE_PLAN.md Phase 4).
 *
 * Used via Field::ruleset([...]):
 *
 *   DateField::make('end_date')
 *       ->validate()
 *       ->ruleset([
 *           Rule::requiredIf('is_scheduled', true),
 *           Rule::after('start_date'),
 *       ]);
 */
final class Rule
{
    private function __construct(private readonly string|LaravelValidationRule $compiled) {}

    public static function required(): self
    {
        return new self('required');
    }

    public static function nullable(): self
    {
        return new self('nullable');
    }

    public static function email(): self
    {
        return new self('email');
    }

    public static function url(): self
    {
        return new self('url');
    }

    public static function numeric(): self
    {
        return new self('numeric');
    }

    public static function integer(): self
    {
        return new self('integer');
    }

    public static function string(): self
    {
        return new self('string');
    }

    public static function boolean(): self
    {
        return new self('boolean');
    }

    public static function array(): self
    {
        return new self('array');
    }

    public static function date(): self
    {
        return new self('date');
    }

    public static function dateFormat(string $format): self
    {
        return new self("date_format:{$format}");
    }

    public static function timezone(): self
    {
        return new self('timezone');
    }

    public static function min(int|float $value): self
    {
        return new self("min:{$value}");
    }

    public static function max(int|float $value): self
    {
        return new self("max:{$value}");
    }

    public static function size(int|float $value): self
    {
        return new self("size:{$value}");
    }

    public static function between(int|float $min, int|float $max): self
    {
        return new self("between:{$min},{$max}");
    }

    /** @param  array<int, int|string>  $values */
    public static function in(array $values): self
    {
        return new self('in:'.implode(',', $values));
    }

    /** @param  array<int, int|string>  $values */
    public static function notIn(array $values): self
    {
        return new self('not_in:'.implode(',', $values));
    }

    public static function regex(string $pattern): self
    {
        return new self("regex:{$pattern}");
    }

    public static function confirmed(): self
    {
        return new self('confirmed');
    }

    public static function after(string $field): self
    {
        return new self("after:{$field}");
    }

    public static function afterOrEqual(string $field): self
    {
        return new self("after_or_equal:{$field}");
    }

    public static function before(string $field): self
    {
        return new self("before:{$field}");
    }

    public static function beforeOrEqual(string $field): self
    {
        return new self("before_or_equal:{$field}");
    }

    public static function requiredIf(string $field, mixed $value): self
    {
        return new self("required_if:{$field},{$value}");
    }

    public static function requiredUnless(string $field, mixed $value): self
    {
        return new self("required_unless:{$field},{$value}");
    }

    public static function requiredWith(string ...$fields): self
    {
        return new self('required_with:'.implode(',', $fields));
    }

    public static function requiredWithout(string ...$fields): self
    {
        return new self('required_without:'.implode(',', $fields));
    }

    public static function same(string $field): self
    {
        return new self("same:{$field}");
    }

    public static function different(string $field): self
    {
        return new self("different:{$field}");
    }

    /** @param  array<int, string>  $extensions */
    public static function mimes(array $extensions): self
    {
        return new self('mimes:'.implode(',', $extensions));
    }

    /** @param  array<int, string>  $types */
    public static function mimetypes(array $types): self
    {
        return new self('mimetypes:'.implode(',', $types));
    }

    public static function distinct(): self
    {
        return new self('distinct');
    }

    /**
     * A rule registered via RuleRegistry::register(). Throws if the name
     * was never registered — this is a developer-facing configuration
     * error, not a runtime user-input error.
     */
    public static function custom(string $name): self
    {
        return new self(RuleRegistry::resolve($name));
    }

    /** Escape hatch within the DSL itself, for any rule not modeled above. */
    public static function raw(string $rule): self
    {
        return new self($rule);
    }

    public function compile(): string|LaravelValidationRule
    {
        return $this->compiled;
    }

    public function __toString(): string
    {
        return is_string($this->compiled) ? $this->compiled : self::class;
    }
}
