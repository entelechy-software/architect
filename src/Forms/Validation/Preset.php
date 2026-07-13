<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

/**
 * Out-of-box validation presets (FORMS_FEATURE_PLAN.md Phase 4, "Preset
 * pack families"). A preset is a named bundle of Rule instances applied
 * on top of a field's own default rules via ->validate(Preset::x()):
 *
 *   TextField::make('email')->validate(Preset::workEmail());
 *
 * Presets never replace a field's type-appropriate defaults (e.g.
 * TextField's 'string' rule) — they are always additive, merged in by
 * Field::getRules().
 */
final class Preset
{
    /** @param  array<int, Rule|string>  $rules */
    private function __construct(private readonly array $rules) {}

    /** @param  array<int, Rule|string>  $rules */
    public static function make(array $rules): self
    {
        return new self($rules);
    }

    // ─── Contact & identity ────────────────────────────────────────────────

    /** Email address, excluding the most common free/personal domains. */
    public static function workEmail(): self
    {
        return new self([
            Rule::email(),
            Rule::raw('not_regex:/@(gmail|yahoo|hotmail|outlook|icloud|aol)\.com$/i'),
        ]);
    }

    /** UK mobile or landline number, with or without a leading +44/0. */
    public static function ukPhone(): self
    {
        return new self([Rule::regex('/^(\+44\d{9,10}|0\d{9,10})$/')]);
    }

    public static function url(): self
    {
        return new self([Rule::url()]);
    }

    // ─── Date & time ───────────────────────────────────────────────────────

    /** The field's date must fall after $field's value. */
    public static function afterField(string $field): self
    {
        return new self([Rule::date(), Rule::after($field)]);
    }

    /** The field's date must fall before $field's value. */
    public static function beforeField(string $field): self
    {
        return new self([Rule::date(), Rule::before($field)]);
    }

    // ─── Numeric & finance ─────────────────────────────────────────────────

    /** Non-negative currency amount. */
    public static function currency(): self
    {
        return new self([Rule::numeric(), Rule::min(0)]);
    }

    /** Percentage between 0 and 100. */
    public static function percentage(): self
    {
        return new self([Rule::numeric(), Rule::min(0), Rule::max(100)]);
    }

    // ─── Files ──────────────────────────────────────────────────────────────

    public static function image(): self
    {
        return new self([Rule::mimes(['jpg', 'jpeg', 'png', 'gif', 'webp'])]);
    }

    public static function document(): self
    {
        return new self([Rule::mimes(['pdf', 'doc', 'docx'])]);
    }

    // ─── Compilation ────────────────────────────────────────────────────────

    /** @return array<int, string|\Illuminate\Contracts\Validation\ValidationRule> */
    public function compile(): array
    {
        return array_map(
            static fn (Rule|string $rule): string|\Illuminate\Contracts\Validation\ValidationRule => $rule instanceof Rule ? $rule->compile() : $rule,
            $this->rules
        );
    }
}
