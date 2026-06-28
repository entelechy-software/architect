<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Contracts;

/**
 * Marker interface for all items that can be placed in a Toolbar.
 *
 * Implementations: ToolbarButton, ToolbarButtonGroup, ToolbarRadioGroup,
 *                  ToolbarDropdown, ToolbarSeparator, ToolbarSpacer, ToolbarBadge.
 */
interface ToolbarItem
{
    /**
     * Unique machine key for this item within its toolbar.
     * Used as the state key in ToolbarEngine and in Alpine store lookups.
     */
    public function getKey(): string;

    /**
     * Item type identifier used by the Blade partial dispatcher.
     * e.g. 'button' | 'button-group' | 'radio-group' | 'dropdown' | 'separator' | 'spacer' | 'badge'
     */
    public function getItemType(): string;

    /**
     * Horizontal position within the toolbar flex row.
     * 'left' items are grouped together; 'right' items are pushed to the far right.
     */
    public function getPosition(): string;
}
