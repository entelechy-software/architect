<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Navigator\Contracts\NavigatorItem;

/**
 * A visual separator between navigator items.
 *
 * Renders as a <li class="nav-item"> spacer or a vertical divider line
 * depending on the navigator type.
 */
final class NavSeparator implements NavigatorItem
{
    public static function make(): self
    {
        return new self;
    }

    public function getItemType(): string
    {
        return 'separator';
    }
}
