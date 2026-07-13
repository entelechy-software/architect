<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Validation;

/**
 * Queryable, introspectable reference of the baseline validation rules a
 * control's value type guarantees (FORMS_FEATURE_PLAN.md Phase 4,
 * "Default validation profile registry keyed by field type").
 *
 * This does NOT shadow or replace any Field subclass's own getRules()
 * override, which remains the actual runtime source of truth and may add
 * further constraints (e.g. IntegerField's min()/max()). It exists so
 * host apps and coding agents building new controls have a documented,
 * testable answer to "what does ->validate() guarantee at minimum for a
 * control of this value type", keyed by the same valueType() strings
 * already used in ControlRegistry (see Phase 3).
 */
final class DefaultProfileRegistry
{
    /** @var array<string, array<int, string>> */
    private const PROFILES = [
        'string' => ['string'],
        'integer' => ['integer'],
        'decimal' => ['numeric'],
        'boolean' => ['boolean'],
        'array' => ['array'],
        'date' => ['date'],
        'datetime' => ['date'],
    ];

    /** @return array<int, string> */
    public static function forValueType(string $valueType): array
    {
        return self::PROFILES[$valueType] ?? [];
    }

    public static function has(string $valueType): bool
    {
        return isset(self::PROFILES[$valueType]);
    }

    /** @return array<string, array<int, string>> */
    public static function all(): array
    {
        return self::PROFILES;
    }
}
