<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Maps a subset of high-frequency, deterministic Laravel-style validation
 * rules to native HTML5 form-control attributes (FORMS_FEATURE_PLAN.md
 * Phase 4, "progressive client-side subset validation").
 *
 * This is deliberately a thin, additive layer: the browser's own HTML5
 * validation (required, min/max, pattern, input type) gives instant
 * feedback for the rules it can express, while every submission is still
 * fully re-validated server-side (FormEngine/WizardEngine's existing
 * ->validate() calls are completely unchanged and remain authoritative —
 * this class never replaces or bypasses them). Rules with no HTML5
 * equivalent (custom rules, cross-field rules, etc.) are simply skipped
 * here and rely entirely on the server-side pass, which is always
 * correct behavior since the server check always runs regardless.
 */
final class ClientValidationMapper
{
    /**
     * @param  array<int, string|ValidationRule>  $rules
     * @return array<string, string|int|float|bool>
     */
    public static function toHtmlAttributes(array $rules): array
    {
        $attributes = [];

        foreach ($rules as $rule) {
            if (! is_string($rule)) {
                // Rule objects (custom/DSL-resolved) have no generic HTML5
                // mapping — they run server-side only.
                continue;
            }

            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

            if ($name === 'required') {
                $attributes['required'] = true;

                continue;
            }

            if ($name === 'email') {
                $attributes['type'] = 'email';

                continue;
            }

            if ($name === 'url') {
                $attributes['type'] = 'url';

                continue;
            }

            if ($parameter === null) {
                continue;
            }

            match ($name) {
                'min' => $attributes['min'] = $parameter,
                'max' => $attributes['max'] = $parameter,
                'size' => $attributes['maxlength'] = $parameter,
                'regex' => $attributes['pattern'] = self::stripRegexDelimiters($parameter),
                default => null,
            };
        }

        return $attributes;
    }

    private static function stripRegexDelimiters(string $pattern): string
    {
        return preg_replace('/^\/(.*)\/[a-zA-Z]*$/', '$1', $pattern) ?? $pattern;
    }
}
