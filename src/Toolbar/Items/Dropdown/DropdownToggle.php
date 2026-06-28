<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

/**
 * Named alias for DropdownCheckbox::toggle() — same API, pill/switch
 * rendering instead of a standard checkbox.
 *
 * Usage:
 *   DropdownToggle::make('show-archived')
 *       ->label('Show archived')
 *       ->dispatchOnChange('architect:filter-toggle', ['key' => 'archived'])
 */
final class DropdownToggle
{
    public static function make(string $key): DropdownCheckbox
    {
        return DropdownCheckbox::toggle($key);
    }
}
