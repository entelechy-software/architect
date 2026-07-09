<?php

declare(strict_types=1);

namespace Entelechy\Architect\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Entelechy\Architect\Table\TableBuilder make(string $type)
 * @method static \Entelechy\Architect\Table\TableBuilder table()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder tabs()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder navTabs()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder pills()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder buttons()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder toolbar()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder stepper()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder sidebar()
 * @method static \Entelechy\Architect\Navigator\NavigatorBuilder dropdown()
 * @method static \Entelechy\Architect\Navigator\WorkspaceTabsDefinition workspaceTabs(string $key)
 * @method static \Entelechy\Architect\Stats\StatBuilder stats()
 * @method static \Entelechy\Architect\Toolbar\ToolbarBuilder navToolbar()
 * @method static \Entelechy\Architect\Supersearch\SupersearchBuilder supersearch()
 * @method static \Entelechy\Architect\Persistence\Models\ArchitectUploads trackUpload(string $path, string $disk = 'public', ?string $contract = null)
 *
 * @see \Entelechy\Architect\Architect
 */
class Architect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Entelechy\Architect\Architect::class;
    }
}
