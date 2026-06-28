<?php

declare(strict_types=1);

namespace Entelechy\Architect\Panels\Contracts;

use Entelechy\Architect\Panels\ArchitectPanelDefinition;

/**
 * Contract for every Architect dashboard panel type.
 *
 * Each panel implementation is both a fluent builder and a self-contained
 * unit of dashboard content.  Call build() to produce the immutable
 * ArchitectPanelDefinition value object consumed by PanelEngine.
 */
interface Panel
{
    public function getType(): string;

    public function getTitle(): ?string;

    public function build(): ArchitectPanelDefinition;
}
