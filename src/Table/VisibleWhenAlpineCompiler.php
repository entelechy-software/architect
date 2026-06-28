<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Table\Contracts\HasVisibleWhen;

/**
 * Translates a field's visibleWhen() rules into a single Alpine.js
 * expression suitable for x-show on the field's wrapper element.
 *
 * Multiple rules combine with AND. The compared value lives in the
 * Livewire $form state so we read it via $wire.form.<name>. Supported
 * operators:
 *   - 'equals' / '='     : strict equality
 *   - 'not'  / '!='      : strict inequality
 *   - 'in'               : array membership
 *   - 'filled'           : value is non-null and non-empty string
 *   - 'empty'            : value is null or empty string
 *   - 'truthy'           : JS truthy
 *   - 'falsy'            : JS falsy
 *
 * Returns null when the field has no visibleWhen rules so callers can
 * skip emitting an x-show attribute.
 */
final class VisibleWhenAlpineCompiler
{
    /**
     * Per-request compiled-expression cache keyed by spl_object_id of the
     * field. Definitions are immutable readonly value-objects rebuilt per
     * Livewire mount, so spl_object_id is a stable identity for the
     * duration of a request and lets repeated form-panel renders skip
     * the rule walk + JSON encoding work.
     *
     * @var array<int, string|null>
     */
    private static array $cache = [];

    public static function compile(HasVisibleWhen $field): ?string
    {
        $cacheKey = spl_object_id($field);
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $rules = $field->getVisibleWhen();
        if ($rules === []) {
            return self::$cache[$cacheKey] = null;
        }

        $clauses = [];
        foreach ($rules as $rule) {
            $clauses[] = self::compileRule($rule['field'], $rule['op'], $rule['value']);
        }

        return self::$cache[$cacheKey] = implode(' && ', $clauses);
    }

    /**
     * @param  mixed  $value
     */
    private static function compileRule(string $field, string $op, $value): string
    {
        $ref = '$wire.form.'.self::escapeIdent($field);

        return match ($op) {
            '=', '==', 'equals' => $ref.' === '.self::jsLiteral($value),
            '!=', '!==', 'not' => $ref.' !== '.self::jsLiteral($value),
            'in' => self::jsLiteral(is_array($value) ? array_values($value) : [$value]).'.includes('.$ref.')',
            'filled' => '('.$ref." !== null && {$ref} !== '' && {$ref} !== undefined)",
            'empty' => '('.$ref." === null || {$ref} === '' || {$ref} === undefined)",
            'truthy' => '!!('.$ref.')',
            'falsy' => '!('.$ref.')',
            default => throw new \LogicException(
                "VisibleWhenAlpineCompiler: unsupported operator [{$op}]"
            ),
        };
    }

    private static function escapeIdent(string $field): string
    {
        // Field names are validated by the builder to match
        // /^[a-z_][a-z0-9_]*$/i, so direct interpolation is safe.
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $field)) {
            throw new \LogicException(
                "VisibleWhenAlpineCompiler: invalid field name [{$field}]"
            );
        }

        return $field;
    }

    /**
     * @param  mixed  $value
     */
    private static function jsLiteral($value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \LogicException('VisibleWhenAlpineCompiler: failed to encode value as JSON');
        }

        return $json;
    }
}
