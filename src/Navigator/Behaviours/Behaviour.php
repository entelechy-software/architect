<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Behaviours;

/**
 * Factory for all navigator item behaviour types.
 *
 * Phase A implements: link()
 * Phase B will add:  filterTable(), ajax(), emits(), modal(), script()
 *
 * Usage:
 *   Tab::make('Elections')->behaviour(Behaviour::link('/elections'))
 */
final class Behaviour
{
    /**
     * Navigate to a URL when the item is activated.
     *
     * This is the Phase A behaviour — a plain anchor href.
     * The navigator's Blade template renders this as an <a> tag.
     */
    public static function link(string $url): LinkBehaviour
    {
        return new LinkBehaviour($url);
    }
}
