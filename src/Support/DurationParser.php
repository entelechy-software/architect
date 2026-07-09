<?php

declare(strict_types=1);

namespace Entelechy\Architect\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Turns a storage/file-retention contract's human-readable duration string
 * (e.g. '2 years', '90 days', '6 months') into a cutoff timestamp: any record
 * whose relevant stage timestamp is at or before the cutoff is due for the
 * next stage transition.
 *
 * Deliberately built on PHP's native relative-time parser (via strtotime)
 * rather than Carbon's own interval parsing — it is the most battle-tested
 * implementation of exactly this grammar and fails loudly (returns false)
 * on nonsense input instead of silently producing a zero-length interval.
 */
final class DurationParser
{
    private function __construct()
    {
        // Static-only utility class.
    }

    /**
     * @param  string|null  $duration  Null/empty means "never due" — the
     *                                 caller's contract has no duration set
     *                                 for this transition (e.g. cold_storage
     *                                 or purge left unset = never auto-purge).
     * @param  CarbonInterface|null  $now  Injectable for tests; defaults to now().
     */
    public static function cutoff(?string $duration, ?CarbonInterface $now = null): ?CarbonImmutable
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return null;
        }

        $reference = $now ?? CarbonImmutable::now();

        $timestamp = strtotime('-'.$duration, $reference->getTimestamp());

        if ($timestamp === false) {
            throw new InvalidArgumentException("Invalid storage contract duration: \"{$duration}\".");
        }

        return CarbonImmutable::createFromTimestamp($timestamp, $reference->getTimezone());
    }
}
