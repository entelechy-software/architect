<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule as LaravelValidationRule;
use InvalidArgumentException;

/**
 * Registry for custom, named validation rules (FORMS_FEATURE_PLAN.md
 * Phase 4, "Custom rule authoring API"). Rules can be registered from
 * host-app code — typically a service provider's boot() method — without
 * modifying any Architect package internals:
 *
 *   RuleRegistry::register(
 *       'uk_mobile',
 *       function (string $attribute, mixed $value, Closure $fail): void {
 *           if (! is_string($value) || ! preg_match('/^\+44\d{10}$/', $value)) {
 *               $fail('The :attribute must be a valid UK mobile number.');
 *           }
 *       }
 *   );
 *
 * Then used via the DSL: Rule::custom('uk_mobile') inside ->ruleset([...]).
 *
 * The callback signature matches Laravel's own ValidationRule::validate()
 * contract exactly, so registering a rule here is functionally identical
 * to writing a full ValidationRule class — this is a convenience
 * registration point, not a parallel validation engine.
 */
final class RuleRegistry
{
    /** @var array<string, Closure(string, mixed, Closure): void> */
    private static array $rules = [];

    /** @param  Closure(string, mixed, Closure): void  $callback */
    public static function register(string $name, Closure $callback): void
    {
        self::$rules[$name] = $callback;
    }

    public static function has(string $name): bool
    {
        return isset(self::$rules[$name]);
    }

    /** @return array<string, Closure(string, mixed, Closure): void> */
    public static function all(): array
    {
        return self::$rules;
    }

    /**
     * Resolves a registered rule name into a Laravel ValidationRule
     * instance, ready to drop into any rules array (native or DSL).
     *
     * @throws InvalidArgumentException if the name was never registered.
     */
    public static function resolve(string $name): LaravelValidationRule
    {
        if (! isset(self::$rules[$name])) {
            throw new InvalidArgumentException(
                "No custom validation rule registered under '{$name}'. Register it first via RuleRegistry::register()."
            );
        }

        $callback = self::$rules[$name];

        return new class($callback) implements LaravelValidationRule
        {
            public function __construct(private readonly Closure $callback) {}

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                ($this->callback)($attribute, $value, $fail);
            }
        };
    }

    /** Clears all registered rules. Intended for test isolation between cases. */
    public static function reset(): void
    {
        self::$rules = [];
    }
}
