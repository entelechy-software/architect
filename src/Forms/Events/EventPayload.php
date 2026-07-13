<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Events;

/**
 * Builds the versioned event payload shape required by
 * FORMS_API_COMPATIBILITY_CONTRACT.md's "Event payload versioning" section.
 *
 * Every payload always carries version, form_key, and timestamp; callers
 * merge in event-specific keys (e.g. step_id for wizard step events).
 */
final class EventPayload
{
    public const VERSION = 1;

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function make(string $formKey, array $extra = []): array
    {
        return array_merge([
            'version' => self::VERSION,
            'form_key' => $formKey,
            'timestamp' => now()->toIso8601String(),
        ], $extra);
    }
}
