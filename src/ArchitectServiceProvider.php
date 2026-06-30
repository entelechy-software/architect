<?php

declare(strict_types=1);

namespace Entelechy\Architect;

use Composer\InstalledVersions;
use Entelechy\Architect\Actions\Livewire\ActionEngine;
use Entelechy\Architect\Content\Livewire\ContentEngine;
use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Forms\Livewire\FormEngine;
use Entelechy\Architect\Forms\Livewire\WizardEngine;
use Entelechy\Architect\Navigator\Livewire\ModuleTabsManager;
use Entelechy\Architect\Navigator\Livewire\SpaTabsEngine;
use Entelechy\Architect\Notifications\Livewire\AlertBannerManager;
use Entelechy\Architect\Notifications\Livewire\AnnouncementBanner;
use Entelechy\Architect\Notifications\Livewire\NotificationCentre;
use Entelechy\Architect\Notifications\Livewire\ToastManager;
use Entelechy\Architect\Notifications\NotificationRuleEngine;
use Entelechy\Architect\Notifications\TriggerRegistry;
use Entelechy\Architect\Panels\Livewire\PanelEngine;
use Entelechy\Architect\Permissions\AllowAllPermissionResolver;
use Entelechy\Architect\Stats\Livewire\DashboardEngine;
use Entelechy\Architect\Supersearch\Livewire\SupersearchEngine;
use Entelechy\Architect\Table\Http\LookupController;
use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Table\Livewire\ImportWizard;
use Entelechy\Architect\Tenancy\NullTenantResolver;
use Entelechy\Architect\Toolbar\Livewire\ToolbarEngine;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ArchitectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/architect.php',
            'architect'
        );

        // Bind the permission resolver — host apps override in their AppServiceProvider
        // or via config('architect.permissions.resolver').
        $this->app->singleton(
            PermissionResolver::class,
            fn () => $this->app->make(
                config('architect.permissions.resolver', AllowAllPermissionResolver::class)
            )
        );

        // Bind the tenant resolver — defaults to NullTenantResolver (single-tenant apps).
        // Multi-tenant host apps bind their own implementation.
        $this->app->singleton(
            TenantResolver::class,
            fn () => $this->app->make(
                config('architect.tenant.resolver', NullTenantResolver::class)
            )
        );

        // Notification subsystem singletons.
        $this->app->singleton(TriggerRegistry::class);
        $this->app->singleton(NotificationRuleEngine::class);
    }

    public function boot(): void
    {
        // Views (namespaced 'architect::')
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'architect');

        // Translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'architect');

        // Migrations (package-owned tables — architect_import_batches, etc.)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publishable assets
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/architect'),
        ], 'architect-assets');

        $this->publishes([
            __DIR__.'/../config/architect.php' => config_path('architect.php'),
        ], 'architect-config');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/architect'),
        ], 'architect-lang');

        // Blade directives
        Blade::directive('architectStyles', function (): string {
            $v = $this->assetVersion();
            $url = asset('vendor/architect/architect.css').'?v='.$v;

            return "<link rel=\"stylesheet\" href=\"{$url}\">";
        });

        Blade::directive('architectScripts', function (): string {
            $v = $this->assetVersion();
            $url = asset('vendor/architect/architect.js').'?v='.$v;

            return "<script src=\"{$url}\"><\/script>";
        });

        // Blade components (architect.* prefix)
        $this->registerBladeComponents();

        // Livewire components
        $this->registerLivewireComponents();

        // Package routes (lookup endpoint, optional playground)
        $this->registerRoutes();
    }

    private function assetVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            $v = InstalledVersions::getPrettyVersion('entelechy/architect');
            if ($v !== null) {
                return $v;
            }
        }

        return (string) config('architect.asset_version', '1');
    }

    private function registerBladeComponents(): void
    {
        Blade::componentNamespace('Entelechy\\Architect\\View\\Components', 'architect');

        // Anonymous (view-only) components — no PHP class needed.
        // componentNamespace() above only resolves class-backed components;
        // anonymous ones (badge, icon, field-wrapper, etc.) need their own
        // path registered so <x-architect.badge> resolves to
        // resources/views/components/badge.blade.php.
        // Usage: <x-architect.badge color="success">Active</x-architect.badge>
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'architect');
    }

    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component(
            'architect-engine',
            Engine::class,
        );

        Livewire::component(
            'architect-form-panel',
            FormPanel::class,
        );

        Livewire::component(
            'architect-import-wizard',
            ImportWizard::class,
        );

        Livewire::component(
            'architect-form-engine',
            FormEngine::class,
        );

        Livewire::component(
            'architect-content-engine',
            ContentEngine::class,
        );

        Livewire::component(
            'architect-toolbar',
            ToolbarEngine::class,
        );

        Livewire::component(
            'architect-spa-tabs',
            SpaTabsEngine::class,
        );

        Livewire::component(
            'architect-module-tabs',
            ModuleTabsManager::class,
        );

        Livewire::component(
            'architect-dashboard',
            DashboardEngine::class,
        );

        Livewire::component(
            'architect-supersearch',
            SupersearchEngine::class,
        );

        Livewire::component(
            'architect-action-engine',
            ActionEngine::class,
        );

        Livewire::component(
            'architect-panel-engine',
            PanelEngine::class,
        );

        Livewire::component(
            'architect-wizard-engine',
            WizardEngine::class,
        );

        Livewire::component(
            'architect-toast-manager',
            ToastManager::class,
        );

        Livewire::component(
            'architect-alert-banner-manager',
            AlertBannerManager::class,
        );

        Livewire::component(
            'architect-notification-centre',
            NotificationCentre::class,
        );

        Livewire::component(
            'architect-announcement-banner',
            AnnouncementBanner::class,
        );
    }

    private function registerRoutes(): void
    {
        if (config('architect.features.tables', true)) {
            $guard = config('architect.auth_guard', 'web');

            Route::middleware(['web', 'auth:'.$guard])
                ->get('/_architect/lookup', LookupController::class)
                ->name('architect.lookup');
        }

        if (config('architect.playground.enabled', false)) {
            Route::middleware(['web', 'auth:'.config('architect.auth_guard', 'web')])
                ->get('/_architect/playground', function () {
                    return response()->json(['status' => 'playground not yet implemented']);
                })
                ->name('architect.playground');
        }
    }
}
