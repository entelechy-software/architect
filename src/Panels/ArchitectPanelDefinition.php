<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels;

use Entelechy\Architect\Panels\Contracts\Panel;

/**
 * Immutable value object representing a single dashboard panel.
 *
 * Produced by each Panel implementation's build() method.
 * Consumed by PanelEngine and its Blade partials via $panel->type dispatch.
 */
final class ArchitectPanelDefinition
{
    /**
     * @param  string  $type  'stats' | 'chart' | 'image-carousel' | 'quick-form' | 'embedded-table'
     * @param  string|null  $title  Panel heading shown in the card header.
     * @param  array<string, mixed>  $config  Type-specific configuration bag.
     * @param  Panel  $panel  The originating Panel instance (for type-safe rendering).
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $title,
        public readonly array $config,
        public readonly Panel $panel,
    ) {}
}
