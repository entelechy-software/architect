<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Livewire;

use Entelechy\Architect\Breadcrumbs\AutomaticBreadcrumbsResolver;
use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Navigator\ArchitectNavigatorDefinition;
use Entelechy\Architect\Navigator\Items\Tab;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Livewire engine for SPA-mode navigator definition classes.
 *
 * Mirrors the pattern of Entelechy\Architect\Table\Livewire\Engine:
 * a route passes the FQCN of a definition class via ->defaults('definitionClass', ...),
 * this component calls ::definition() on it, and renders the appropriate
 * navigator partial with SPA mode active.
 *
 * Definition class contract:
 *   - Must have a public static definition(): ArchitectNavigatorDefinition method
 *   - The definition must be built with ->spa() set
 *
 * Route example:
 *   Route::get('/advice/settings', SpaTabsEngine::class)
 *       ->defaults('definitionClass', AdviceOptionsDefinition::class)
 *       ->name('advice.settings.index');
 */
#[Layout('layouts.app')]
class SpaTabsEngine extends Component
{
    /**
     * FQCN of the class whose ::definition() returns the
     * ArchitectNavigatorDefinition (with ->spa() set) this engine drives.
     */
    public string $definitionClass = '';

    /** Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    public function mount(string $definitionClass): void
    {
        abort_unless(
            class_exists($definitionClass) && method_exists($definitionClass, 'definition'),
            404
        );

        $this->definitionClass = $definitionClass;
    }

    public function render(): View
    {
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            /** @var ArchitectNavigatorDefinition $definition */
            $definition = ($this->definitionClass)::definition();

            if ($definition->permission !== null
                && ! app(PermissionResolver::class)->can(auth()->user(), $definition->permission)) {
                throw new AuthorizationException('You do not have permission to view this page.');
            }

            // ── Breadcrumbs ────────────────────────────────────────────────
            // Build a per-tab breadcrumb map for inherit mode, and share a DTO
            // with the layout so the topbar can render the correct crumbs.
            /** @var array<string, array<int, array{title: string, url?: string}>> $tabBreadcrumbs */
            $tabBreadcrumbs = [];

            if ($definition->inheritBreadcrumbs) {
                foreach ($definition->items as $item) {
                    if (! ($item instanceof Tab) || $item->getArchitectClass() === null) {
                        continue;
                    }

                    /** @var class-string $tableClass */
                    $tableClass = $item->getArchitectClass();

                    if (method_exists($tableClass, 'definition')) {
                        $tableDef = $tableClass::definition();
                        $tabBreadcrumbs[$item->getSlug()] = $tableDef->breadcrumbMode === 'automatic'
                            ? app(AutomaticBreadcrumbsResolver::class)->forTable($tableDef, request())
                            : ($tableDef->breadcrumbs ?? []);
                    }
                }

                $initialSlug = $definition->initialTab(
                    request()->query($definition->urlParam ?? '', '')
                );

                view()->share('definition', new SpaSharedDefinition(
                    breadcrumbs: $tabBreadcrumbs[$initialSlug] ?? [],
                    inheritBreadcrumbs: true,
                ));
            } elseif ($definition->breadcrumbs !== []) {
                view()->share('definition', new SpaSharedDefinition(
                    breadcrumbs: $definition->breadcrumbs,
                ));
            }
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            $this->dispatch('architect:unauthorized');

            return view('architect::navigator.spa-engine', [
                'definition' => null,
                'tabBreadcrumbs' => [],
            ]);
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while loading this page. Please try again.';
            report($e);

            return view('architect::navigator.spa-engine', [
                'definition' => null,
                'tabBreadcrumbs' => [],
            ]);
        }

        return view('architect::navigator.spa-engine', [
            'definition' => $definition,
            'tabBreadcrumbs' => $tabBreadcrumbs,
        ]);
    }
}
