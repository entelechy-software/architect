<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Contracts;

/**
 * Contract for all items that can be placed inside a NavigatorBuilder.
 *
 * Implementations: Tab, NavButton, StepItem, NavSeparator.
 * The type-tag lets the Blade component dispatch rendering without
 * an instanceof chain.
 */
interface NavigatorItem
{
    /**
     * A short string identifying the item variant.
     * Used by Blade partials to decide how to render.
     * e.g. 'tab', 'button', 'step', 'separator'
     */
    public function getItemType(): string;
}
