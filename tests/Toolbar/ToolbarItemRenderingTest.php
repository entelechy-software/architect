<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Toolbar;

use Entelechy\Architect\Tests\TestCase;
use Entelechy\Architect\Toolbar\ArchitectToolbarDefinition;
use Entelechy\Architect\Toolbar\Contracts\ProvidesToolbarDefinition;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownAction;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownCheckbox;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownLinkGroup;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownRadioGroup as DropdownRadioGroupItem;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownSeparator;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownSubmenu;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownTextInput;
use Entelechy\Architect\Toolbar\Items\ToolbarBadge;
use Entelechy\Architect\Toolbar\Items\ToolbarButton;
use Entelechy\Architect\Toolbar\Items\ToolbarButtonGroup;
use Entelechy\Architect\Toolbar\Items\ToolbarDropdown;
use Entelechy\Architect\Toolbar\Items\ToolbarRadioGroup;
use Entelechy\Architect\Toolbar\Items\ToolbarSearch;
use Entelechy\Architect\Toolbar\Items\ToolbarSeparator;
use Entelechy\Architect\Toolbar\Items\ToolbarSpacer;
use Entelechy\Architect\Toolbar\Livewire\ToolbarEngine;
use Livewire\Livewire;

/**
 * Regression coverage for a Phase 2 wiring-audit finding: every Toolbar item
 * Blade partial (button-group, dropdown, dropdown-checkbox, dropdown-radio-group,
 * dropdown-text-input, dropdown-submenu, dropdown-toggle, radio-group, search)
 * called the undefined method ->key() instead of the real ->getKey() accessor.
 * ToolbarItem (and DropdownItem) only ever defined getKey(), so rendering any
 * ToolbarRadioGroup, ToolbarSearch, ToolbarDropdown (with any child), or
 * ToolbarButtonGroup threw a fatal Error — silently surfaced by ToolbarEngine
 * as a generic "An error occurred" message. There was previously zero test
 * coverage for the Toolbar subsystem, which is why this went undetected.
 */
class ToolbarItemRenderingTest extends TestCase
{
    public function test_radio_group_dropdown_search_and_button_group_render_without_error(): void
    {
        $component = Livewire::test(ToolbarEngine::class, ['definitionClass' => FullToolbarDefinition::class]);

        $component->assertOk();
        $component->assertSet('hasError', false);
        $component->assertSee('List');
        $component->assertSee('Show archived');
        $component->assertSee('Search…');
    }

    public function test_button_group_applies_size_to_wrapping_element(): void
    {
        $component = Livewire::test(ToolbarEngine::class, ['definitionClass' => FullToolbarDefinition::class]);

        $component->assertSeeHtml('btn-group-sm');
    }
}

final class FullToolbarDefinition implements ProvidesToolbarDefinition
{
    public static function definition(): ArchitectToolbarDefinition
    {
        return new ArchitectToolbarDefinition(
            key: 'full-toolbar-test',
            items: [
                ToolbarButtonGroup::make('view-controls')
                    ->size('sm')
                    ->add(ToolbarButton::make('list')->label('List'))
                    ->add(ToolbarButton::make('card')->label('Cards')),
                ToolbarRadioGroup::make('view')
                    ->option('list', 'List view')
                    ->option('card', 'Card view')
                    ->default('list'),
                ToolbarSearch::make('q')->placeholder('Search…'),
                ToolbarButton::make('export')
                    ->label('Export')
                    ->href('/export')
                    ->icon('fas fa-file-export')
                    ->tooltip('Export the current view')
                    ->badge('New'),
                ToolbarButton::make('refresh')
                    ->label('Refresh')
                    ->wireClick('refresh')
                    ->color('secondary'),
                ToolbarButton::make('broadcast')
                    ->label('Broadcast')
                    ->dispatch('some-event', ['foo' => 'bar']),
                ToolbarBadge::make('status')->label('Live')->color('success')->tooltip('Status'),
                ToolbarSeparator::make(),
                ToolbarSpacer::make()->mode('fixed')->width('24px'),
                ToolbarDropdown::make('options')
                    ->label('Options')
                    ->item(DropdownCheckbox::toggle('archived')->label('Show archived'))
                    ->item(DropdownRadioGroupItem::make('sort')->option('name', 'Name')->option('date', 'Date'))
                    ->item(DropdownTextInput::make('filter')->label('Filter'))
                    ->item(
                        DropdownAction::make('delete')
                            ->label('Delete')
                            ->icon('fas fa-trash')
                            ->color('danger')
                            ->confirm('Are you sure?')
                            ->badge('!')
                    )
                    ->item(DropdownSubmenu::make('more')->label('More')->icon('fas fa-ellipsis')
                        ->item(DropdownAction::make('archive')->label('Archive'))
                        ->item(DropdownSeparator::make('sep'))
                        ->item(DropdownLinkGroup::make('links')->label('Links'))),
            ],
            persistConfig: [],
            boundTarget: null,
            size: 'sm',
            bordered: true,
            sticky: false,
        );
    }
}
