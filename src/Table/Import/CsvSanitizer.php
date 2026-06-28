<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import;

/**
 * Defends against CSV injection (a.k.a. "Formula Injection",
 * OWASP CSV Formula Injection / CVE-2014-3524 family).
 *
 * When a user opens an imported CSV in Excel / LibreOffice / Numbers
 * any cell that begins with `=`, `+`, `-`, `@`, TAB or CR is
 * interpreted as a formula by the spreadsheet — which can ex-filtrate
 * data, run arbitrary commands, or DoS the spreadsheet. We strip those
 * leading characters on ingest so the imported value is safe to render
 * and safe to re-export later.
 *
 * UTF-8 validation also happens here: rows containing invalid byte
 * sequences are rejected at the row level by the processor; this class
 * only reports validity, it does not transcode.
 */
final class CsvSanitizer
{
    /**
     * Characters that, when leading, trigger formula evaluation in
     * common spreadsheet apps. Includes whitespace prefixes (TAB, CR)
     * because Excel will silently drop them and then evaluate the
     * remaining string.
     */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Strip dangerous leading characters from a single cell value.
     *
     * Trims whitespace first so " =FOO" is sanitised the same as "=FOO".
     * Empty / null values short-circuit to an empty string so callers
     * can rely on a string return.
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        // Strip every leading formula-trigger character.
        // Loop because an attacker may stack multiple (e.g. `=+1+1`).
        while ($trimmed !== '' && in_array($trimmed[0], self::FORMULA_PREFIXES, true)) {
            $trimmed = substr($trimmed, 1);
        }

        return $trimmed;
    }

    /**
     * Validate that a string contains only valid UTF-8 byte sequences.
     */
    public static function isValidUtf8(string $value): bool
    {
        return mb_check_encoding($value, 'UTF-8');
    }
}
