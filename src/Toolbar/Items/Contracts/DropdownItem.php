<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Contracts;

/**
 * Marker interface for all items that can be placed inside a ToolbarDropdown.
 *
 * Implementations: DropdownAction, DropdownCheckbox (checkbox & toggle via ::toggle()),
 *                  DropdownSeparator, DropdownLinkGroup (Phase 1);
 *                  DropdownRadioGroup, DropdownTextInput, DropdownSubmenu (Phase 2).
 */
interface DropdownItem
{
    /**
     * Unique machine key within its parent ToolbarDropdown.
     */
    public function getKey(): string;

    /**
     * Item type identifier used by the Blade partial dispatcher.
     * e.g. 'action' | 'checkbox' | 'separator' | 'link-group'
     */
    public function getItemType(): string;
}
