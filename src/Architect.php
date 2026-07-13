<?php

declare(strict_types=1);

namespace Entelechy\Architect;

use Entelechy\Architect\Actions\ActionBuilder;
use Entelechy\Architect\Content\ContentBuilder;
use Entelechy\Architect\Forms\FormBuilder;
use Entelechy\Architect\Forms\WizardBuilder;
use Entelechy\Architect\Navigator\NavigatorBuilder;
use Entelechy\Architect\Notifications\NotificationBuilder;
use Entelechy\Architect\Panels\DashboardBuilder;
use Entelechy\Architect\Persistence\Models\ArchitectUploads;
use Entelechy\Architect\Stats\StatBuilder;
use Entelechy\Architect\Supersearch\SupersearchBuilder;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Toolbar\ToolbarBuilder;

/**
 * Unified entry point for the Architect framework.
 *
 * Architect consolidates the CRUD table engine and the navigation builder
 * into a single named concept. All elements are accessed via Architect::make()
 * with an element type, followed by chainable configuration methods.
 *
 * Usage:
 *
 *   // Table
 *   Architect::make('table')
 *       ->title('Committees')
 *       ->model(CommitteesTableModel::class)
 *       ->permissions(read: '...', create: '...', modify: '...', remove: '...')
 *       ->column(...)
 *       ->field(...)
 *       ->build()
 *
 *   // Navigator (all variants)
 *   Architect::make('navigator')->type('tabs')->style('page')->tab(...)->build()
 *   Architect::make('navigator')->type('tabs')->spa()->tab(...)->build()
 *   Architect::make('navigator')->type('sidebar')->tab(...)->build()
 *   Architect::make('navigator')->type('stepper')->step(...)->build()
 *   Architect::make('navigator')->type('workspace-tabs')->workspaceKey('advice-center')->pinnedTab(...)->build()
 *
 *   // Convenience alias for tables (returns TableBuilder with IDE type safety)
 *   Architect::table()->title(...)->build()
 */
final class Architect
{
    private function __construct() {}

    /**
     * Resolves the singleton instance bound in the container for the
     * Architect facade (see ArchitectServiceProvider::register()).
     * Architect holds no instance state — every real method is `public
     * static` — this exists purely so Illuminate\Support\Facades\Facade
     * can resolve *some* instance to dispatch calls onto, since the
     * container cannot call a private constructor via reflection.
     *
     * @internal
     */
    public static function instance(): self
    {
        return new self;
    }

    /**
     * Generic factory — dispatches to the correct builder based on $type.
     *
     * @param  string  $type  'table' | 'navigator' | 'stat'
     */
    public static function make(string $type): TableBuilder|NavigatorBuilder|StatBuilder
    {
        return match ($type) {
            'table' => TableBuilder::make(),
            'navigator' => NavigatorBuilder::make(),
            'stat' => StatBuilder::make(),
            default => throw new \InvalidArgumentException(
                "Unknown Architect element '{$type}'. Valid elements: table, navigator, stat."
            ),
        };
    }

    // ─── Convenience alias ─────────────────────────────────────────────────

    /**
     * Convenience alias for Architect::make('table').
     * Preferred when IDE type inference on TableBuilder is needed.
     */
    public static function table(): TableBuilder
    {
        return TableBuilder::make();
    }

    /**
     * Convenience alias for Architect::make('stat').
     * Call ->type('dashboard') for a full stats dashboard with date filter and sections,
     * or ->type('chart') / ->type('metrics') etc. for standalone section definitions.
     */
    public static function stats(): StatBuilder
    {
        return StatBuilder::make();
    }

    /**
     * Create a rich stateful Toolbar — supports buttons, radio groups,
     * dropdowns with toggles, badges, spacers, and (Phase 3) search.
     *
     * Usage:
     *   Architect::toolbar()
     *       ->key('my-toolbar')
     *       ->item(ToolbarButton::make('create')->label('New')->color('primary'))
     *       ->build();
     */
    public static function toolbar(): ToolbarBuilder
    {
        return ToolbarBuilder::make();
    }

    /**
     * Alias for the existing link-based Navigator toolbar (navigation buttons only).
     * Use Architect::toolbar() for the rich stateful toolbar.
     *
     * @deprecated Prefer named Architect::tabs(), Architect::pills(), etc. for navigation.
     */
    public static function navToolbar(): NavigatorBuilder
    {
        return NavigatorBuilder::make()->type('toolbar');
    }

    /**
     * Create an Architect Supersearch command-palette definition.
     *
     * Usage:
     *   Architect::supersearch()
     *       ->key('global')
     *       ->placeholder('Search or jump to…')
     *       ->shortcut('cmd+k')
     *       ->searchSet(MyNavSet::make())
     *       ->searchSet(ModelSearchSet::for(Member::class)->fields(['name']))
     *       ->build();
     */
    public static function supersearch(): SupersearchBuilder
    {
        return SupersearchBuilder::make();
    }

    /**
     * Create a standalone Architect form — fields, sections, and grids
     * rendered by the FormEngine Livewire component, independent of the
     * Table form panel.
     *
     * Usage:
     *   Architect::form('member')
     *       ->structure([TextField::make('name')->required()])
     *       ->saveUsing(fn (array $data) => Member::create($data))
     *       ->build();
     */
    public static function form(string $key = 'default'): FormBuilder
    {
        return FormBuilder::make($key);
    }

    /**
     * Create a standalone Architect content panel — read-only entries
     * rendered by the ContentEngine Livewire component, for record-detail
     * views independent of the Table form panel.
     *
     * Usage:
     *   Architect::content()
     *       ->record($member)
     *       ->structure([TextEntry::make('name'), IconEntry::make('status')])
     *       ->build();
     */
    public static function content(): ContentBuilder
    {
        return ContentBuilder::make();
    }

    /**
     * Create an ordered set of Architect actions.
     *
     * Usage:
     *   Architect::action('member-row-actions')
     *       ->add(EditMemberAction::class)
     *       ->add(DeleteMemberAction::class)
     *       ->build();
     */
    public static function action(string $key = 'default'): ActionBuilder
    {
        return ActionBuilder::make($key);
    }

    /**
     * Create an Architect dashboard — a grid of typed panel widgets.
     *
     * Usage:
     *   Architect::dashboard('home')
     *       ->panel(StatsPanel::make()->cards([...]), span: 12)
     *       ->panel(ChartPanel::make()->style('area'), span: 8)
     *       ->build();
     */
    public static function dashboard(string $key = 'main'): DashboardBuilder
    {
        return DashboardBuilder::make($key);
    }

    /**
     * Create a multi-step Architect wizard form.
     *
     * Usage:
     *   Architect::wizard('onboarding')
     *       ->step('Details', [TextField::make('name')])
     *       ->step('Preferences', [SelectField::make('role')])
     *       ->saveUsing(fn ($data) => User::create($data))
     *       ->completedRoute('dashboard')
     *       ->build();
     */
    public static function wizard(string $key = 'wizard'): WizardBuilder
    {
        return WizardBuilder::make($key);
    }

    /**
     * The Forms control registry — the catalog of every field type
     * (control) available to FormBuilder/WizardBuilder, seeded at boot
     * with every shipped Wave A-D control (see
     * ArchitectServiceProvider::registerControlLibrary()).
     *
     * Usage:
     *   Architect::controls()->get('currency');
     *   Architect::controls()->all();
     */
    public static function controls(): \Entelechy\Architect\Forms\ControlRegistry
    {
        return app(\Entelechy\Architect\Forms\ControlRegistry::class);
    }

    /**
     * Fluent notification builder — for toast, alert, inbox, and announcement types.
     *
     * Usage:
     *   Architect::notification()->success('Record saved.')->send();         // toast
     *   Architect::notification()->as('alert')->warning('Action needed.')->send();
     *   Architect::notification()->as('announcement')->message('Down for maintenance.')->severity('warning')->send();
     */
    public static function notification(): NotificationBuilder
    {
        return new NotificationBuilder;
    }

    /**
     * Shorthand for a toast notification (default type).
     *
     * Usage:
     *   Architect::toast()->success('Saved!')->send();
     */
    public static function toast(): NotificationBuilder
    {
        return (new NotificationBuilder)->as('toast');
    }

    /**
     * Shorthand for an alert banner notification.
     *
     * Usage:
     *   Architect::alert()->warning('Please verify your email.')->send();
     */
    public static function alert(): NotificationBuilder
    {
        return (new NotificationBuilder)->as('alert');
    }

    /**
     * Shorthand for an inbox notification to a specific user.
     *
     * Usage:
     *   Architect::notify($user)->inbox('member.welcome', ['name' => $user->name])->send();
     */
    public static function notify(mixed $user): NotificationBuilder
    {
        return (new NotificationBuilder)->for($user);
    }

    /**
     * Shorthand for a persistent announcement banner.
     *
     * Usage:
     *   Architect::announce()->message('Scheduled maintenance tonight.')->severity('warning')->send();
     */
    public static function announce(): NotificationBuilder
    {
        return (new NotificationBuilder)->as('announcement');
    }

    /**
     * Register a standalone/orphan uploaded file with the File Retention
     * ledger (architect_uploads) — for files not attached to any Eloquent
     * model column. Falls back to config('architect.file_retention.default_contract')
     * when no contract is given.
     *
     * Usage:
     *   Architect::trackUpload($path, disk: 'uploads', contract: 'sensitive-files');
     */
    public static function trackUpload(string $path, string $disk = 'public', ?string $contract = null): ArchitectUploads
    {
        return ArchitectUploads::query()->create([
            'path' => $path,
            'disk' => $disk,
            'contract_key' => $contract ?? (string) config('architect.file_retention.default_contract'),
            'stage' => ArchitectUploads::STAGE_ACTIVE,
            'last_accessed_at' => now(),
        ]);
    }
}
