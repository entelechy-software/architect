<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

/**
 * Coerces a lookup form payload into a scalar primary key.
 *
 * The browser-side combobox posts the selected option as either:
 *   - {"val": 42, "txt": "Football"}  (single-select)
 *   - [{"val": 42, "txt": "Football"}, ...]  (multi-select)
 *   - 42  (already coerced — server-rendered hydration)
 *   - "" or null (cleared)
 *
 * Used by data model layers in multiple modules.
 */
final class LookupCoercion
{
    /**
     * Reduce a lookup payload to a single integer ID, or null if blank.
     */
    public static function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            if (array_key_exists('val', $value)) {
                $val = $value['val'];

                return $val === '' || $val === null ? null : (int) $val;
            }

            $first = reset($value);
            if (is_array($first) && array_key_exists('val', $first)) {
                return self::toInt($first);
            }

            return null;
        }

        if (is_int($value) || is_string($value) || is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Reduce a multi-lookup payload to an array of integer IDs.
     *
     * @return list<int>
     */
    public static function toIntArray(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (! is_array($value)) {
            $single = self::toInt($value);

            return $single !== null ? [$single] : [];
        }

        if (isset($value[0]) && is_array($value[0]) && array_key_exists('val', $value[0])) {
            return array_values(array_filter(
                array_map(fn (array $item): ?int => self::toInt($item), $value),
                fn (?int $id): bool => $id !== null,
            ));
        }

        return array_values(array_filter(array_map('intval', $value)));
    }
}
