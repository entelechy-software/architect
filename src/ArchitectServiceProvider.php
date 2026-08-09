<?php

declare(strict_types=1);

namespace Entelechy\Architect;

use Composer\InstalledVersions;
use Entelechy\Architect\Actions\Livewire\ActionEngine;
use Entelechy\Architect\Console\Commands\ArchitectDoctorCommand;
use Entelechy\Architect\Console\Commands\ArchitectFormsAuditKeysCommand;
use Entelechy\Architect\Console\Commands\ArchitectInitCommand;
use Entelechy\Architect\Console\Commands\ArchitectMakeTableCommand;
use Entelechy\Architect\Console\Commands\ArchitectSetupStatusCommand;
use Entelechy\Architect\Console\Commands\ArchitectStorageDiscoverCommand;
use Entelechy\Architect\Console\Commands\ArchitectStorageInitCommand;
use Entelechy\Architect\Console\Commands\ArchitectStorageSweepCommand;
use Entelechy\Architect\Content\Livewire\ContentEngine;
use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Contracts\StateStore;
use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Forms\Contracts\WizardDraftStore;
use Entelechy\Architect\Forms\ControlRegistry;
use Entelechy\Architect\Forms\Drafts\CacheWizardDraftStore;
use Entelechy\Architect\Forms\Fields\AddressAutocompleteField;
use Entelechy\Architect\Forms\Fields\AnnotationField;
use Entelechy\Architect\Forms\Fields\ApiRequestBuilderField;
use Entelechy\Architect\Forms\Fields\AudioRecorderField;
use Entelechy\Architect\Forms\Fields\AudioWaveformSelectionField;
use Entelechy\Architect\Forms\Fields\AutocompleteField;
use Entelechy\Architect\Forms\Fields\AvatarField;
use Entelechy\Architect\Forms\Fields\BarcodeQrScannerField;
use Entelechy\Architect\Forms\Fields\BlockEditor;
use Entelechy\Architect\Forms\Fields\Builder;
use Entelechy\Architect\Forms\Fields\ButtonGroupField;
use Entelechy\Architect\Forms\Fields\CameraCaptureField;
use Entelechy\Architect\Forms\Fields\CanvasManipulationField;
use Entelechy\Architect\Forms\Fields\CardInputField;
use Entelechy\Architect\Forms\Fields\CascadingSelectField;
use Entelechy\Architect\Forms\Fields\CheckboxField;
use Entelechy\Architect\Forms\Fields\CheckboxList;
use Entelechy\Architect\Forms\Fields\CodeEditor;
use Entelechy\Architect\Forms\Fields\ColorPicker;
use Entelechy\Architect\Forms\Fields\ComboboxField;
use Entelechy\Architect\Forms\Fields\CronScheduleBuilderField;
use Entelechy\Architect\Forms\Fields\CurrencyField;
use Entelechy\Architect\Forms\Fields\Custom;
use Entelechy\Architect\Forms\Fields\DataMappingField;
use Entelechy\Architect\Forms\Fields\DateField;
use Entelechy\Architect\Forms\Fields\DateRangeField;
use Entelechy\Architect\Forms\Fields\DateTimeField;
use Entelechy\Architect\Forms\Fields\DateTimePicker;
use Entelechy\Architect\Forms\Fields\DateTimeRangeField;
use Entelechy\Architect\Forms\Fields\DecimalField;
use Entelechy\Architect\Forms\Fields\DependencyBuilderField;
use Entelechy\Architect\Forms\Fields\DialKnobField;
use Entelechy\Architect\Forms\Fields\DiffMergeField;
use Entelechy\Architect\Forms\Fields\DisplayField;
use Entelechy\Architect\Forms\Fields\DocumentScannerField;
use Entelechy\Architect\Forms\Fields\DrawingSketchField;
use Entelechy\Architect\Forms\Fields\DualListboxField;
use Entelechy\Architect\Forms\Fields\DurationField;
use Entelechy\Architect\Forms\Fields\EntityPickerField;
use Entelechy\Architect\Forms\Fields\FileUpload;
use Entelechy\Architect\Forms\Fields\FormulaExpressionEditorField;
use Entelechy\Architect\Forms\Fields\GeographicBoundaryField;
use Entelechy\Architect\Forms\Fields\GradientEditorField;
use Entelechy\Architect\Forms\Fields\Hidden;
use Entelechy\Architect\Forms\Fields\HierarchicalCheckboxTreeField;
use Entelechy\Architect\Forms\Fields\ImageComparisonSliderField;
use Entelechy\Architect\Forms\Fields\ImageCropperField;
use Entelechy\Architect\Forms\Fields\IntegerField;
use Entelechy\Architect\Forms\Fields\KanbanBoardField;
use Entelechy\Architect\Forms\Fields\KeyboardShortcutRecorderField;
use Entelechy\Architect\Forms\Fields\KeyValue;
use Entelechy\Architect\Forms\Fields\LookupField;
use Entelechy\Architect\Forms\Fields\MapLocationField;
use Entelechy\Architect\Forms\Fields\MarkdownEditor;
use Entelechy\Architect\Forms\Fields\MaskedInputField;
use Entelechy\Architect\Forms\Fields\MathEquationEditorField;
use Entelechy\Architect\Forms\Fields\MatrixInputField;
use Entelechy\Architect\Forms\Fields\MeasurementField;
use Entelechy\Architect\Forms\Fields\MentionEditorField;
use Entelechy\Architect\Forms\Fields\MultiSelectField;
use Entelechy\Architect\Forms\Fields\NaturalLanguageDateField;
use Entelechy\Architect\Forms\Fields\NodeGraphEditorField;
use Entelechy\Architect\Forms\Fields\NumericStepperField;
use Entelechy\Architect\Forms\Fields\OtpField;
use Entelechy\Architect\Forms\Fields\PasswordStrengthField;
use Entelechy\Architect\Forms\Fields\PercentageField;
use Entelechy\Architect\Forms\Fields\PermissionMatrixField;
use Entelechy\Architect\Forms\Fields\PhoneField;
use Entelechy\Architect\Forms\Fields\PostalCodeLookupField;
use Entelechy\Architect\Forms\Fields\QueryBuilderField;
use Entelechy\Architect\Forms\Fields\QueryLanguageTextField;
use Entelechy\Architect\Forms\Fields\Radio;
use Entelechy\Architect\Forms\Fields\RankingField;
use Entelechy\Architect\Forms\Fields\RatingField;
use Entelechy\Architect\Forms\Fields\RecurrenceBuilderField;
use Entelechy\Architect\Forms\Fields\RegexBuilderTesterField;
use Entelechy\Architect\Forms\Fields\RelationshipPickerField;
use Entelechy\Architect\Forms\Fields\Repeater;
use Entelechy\Architect\Forms\Fields\RichEditor;
use Entelechy\Architect\Forms\Fields\RoleBuilderField;
use Entelechy\Architect\Forms\Fields\RulesWorkflowBuilderField;
use Entelechy\Architect\Forms\Fields\SchemaDrivenObjectEditorField;
use Entelechy\Architect\Forms\Fields\SearchWithFiltersField;
use Entelechy\Architect\Forms\Fields\SegmentedControlField;
use Entelechy\Architect\Forms\Fields\SelectField;
use Entelechy\Architect\Forms\Fields\SignaturePadField;
use Entelechy\Architect\Forms\Fields\Slider;
use Entelechy\Architect\Forms\Fields\SortableListField;
use Entelechy\Architect\Forms\Fields\TagsInput;
use Entelechy\Architect\Forms\Fields\TemplateEditorField;
use Entelechy\Architect\Forms\Fields\TextareaField;
use Entelechy\Architect\Forms\Fields\TextField;
use Entelechy\Architect\Forms\Fields\TimelineEditorField;
use Entelechy\Architect\Forms\Fields\TimezoneField;
use Entelechy\Architect\Forms\Fields\Toggle;
use Entelechy\Architect\Forms\Fields\ToggleButtons;
use Entelechy\Architect\Forms\Fields\TreeSelectField;
use Entelechy\Architect\Forms\Fields\VideoRecorderField;
use Entelechy\Architect\Forms\Fields\YesNoUnknownField;
use Entelechy\Architect\Forms\FormKeyRegistry;
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
use Entelechy\Architect\Persistence\DatabaseStateStore;
use Entelechy\Architect\Persistence\LocalStateStore;
use Entelechy\Architect\Stats\Livewire\DashboardEngine;
use Entelechy\Architect\Supersearch\Livewire\SupersearchEngine;
use Entelechy\Architect\Support\Maturity;
use Entelechy\Architect\Table\Http\LookupController;
use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\Livewire\FormPanel;
use Entelechy\Architect\Table\Livewire\ImportWizard;
use Entelechy\Architect\Tenancy\NullTenantResolver;
use Entelechy\Architect\Toolbar\Livewire\ToolbarEngine;
use Illuminate\Console\Scheduling\Schedule;
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

        // Bind the state store — selects backend strictly from the locked
        // `architect.state.mode` setup key (localStorage is a client-only,
        // fully inert no-op on the server; database persists via Eloquent).
        $this->app->singleton(
            StateStore::class,
            fn () => config('architect.state.mode') === 'database'
                ? $this->app->make(DatabaseStateStore::class)
                : $this->app->make(LocalStateStore::class)
        );

        // Notification subsystem singletons.
        $this->app->singleton(TriggerRegistry::class);
        $this->app->singleton(NotificationRuleEngine::class);

        // Forms subsystem singletons — FormKeyRegistry must be a per-request
        // singleton so every FormEngine/WizardEngine mounted on the same page
        // shares one registry, which is what actually catches two forms
        // reusing the same key within a single request.
        $this->app->singleton(FormKeyRegistry::class);

        // Default wizard draft persistence — cache-backed, no migration
        // required. Host apps may rebind WizardDraftStore to a
        // database-backed implementation if they need longer-lived,
        // queryable drafts.
        $this->app->singleton(WizardDraftStore::class, CacheWizardDraftStore::class);

        // Forms control registry — the Phase 3 control library catalog.
        $this->app->singleton(ControlRegistry::class);

        // The Architect facade root — see Architect::instance() for why
        // this can't be a plain reflection-based singleton() call.
        $this->app->singleton(Architect::class, fn () => Architect::instance());
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
            __DIR__.'/../resources/views' => resource_path('views/vendor/architect'),
        ], 'architect-views');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/architect'),
        ], 'architect-lang');

        // Blade directives
        Blade::directive('architectStyles', function (): string {
            $v = $this->assetVersion();
            // Use a root-relative URL so assets always load from the current origin/port.
            $url = '/vendor/architect/architect.css?v='.$v;

            return "<link rel=\"stylesheet\" href=\"{$url}\">";
        });

        Blade::directive('architectScripts', function (): string {
            $v = $this->assetVersion();
            // Use a root-relative URL so assets always load from the current origin/port.
            $url = '/vendor/architect/architect.js?v='.$v;

            return "<script src=\"{$url}\"></script>";
        });

        // Blade components (architect.* prefix)
        $this->registerBladeComponents();

        // Livewire components
        $this->registerLivewireComponents();

        // Package routes (lookup endpoint, optional playground)
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ArchitectInitCommand::class,
                ArchitectSetupStatusCommand::class,
                ArchitectDoctorCommand::class,
                ArchitectStorageInitCommand::class,
                ArchitectStorageDiscoverCommand::class,
                ArchitectStorageSweepCommand::class,
                ArchitectFormsAuditKeysCommand::class,
                ArchitectMakeTableCommand::class,
            ]);
        }

        $this->registerStorageContractsScheduler();

        $this->registerControlLibrary();
    }

    /**
     * Seeds Architect::controls() with every shipped Forms field.
     *
     * Wave A entries are the 26 pre-existing Field subclasses, registered
     * as-is (this is what "the registry formalizes Wave A rather than
     * replacing it" means — see FORMS_FEATURE_PLAN.md Phase 3). Wave B/C
     * entries are the new controls this plan actually implements;
     * Fieldset and Block are intentionally excluded — neither extends
     * Field (Fieldset implements StructureItem directly with no value of
     * its own; Block is a plain value object describing a block *type*
     * used inside Builder, not an independently renderable control).
     *
     * Every entry also declares a {@see Maturity}. This was assigned
     * mechanically (see ARCHITECT_IMPROVEMENT_PLAN.md Phase 0): a field is
     * Maturity::Experimental if its Blade view references an Alpine
     * component (`x-data="architectXyz(...)"`) that has no matching
     * `Alpine.data('architectXyz', ...)` registration anywhere under
     * resources/js — i.e. it is provably non-functional today. A handful
     * of additional fields are Maturity::Beta where manual inspection
     * found a real but narrower-than-advertised implementation (see the
     * inline comment on each). The Wave 3 hardware/media-capture fields
     * (camera, mic, canvas/signature capture) and the two address-lookup
     * fields (no provider chosen yet) are Maturity::Planned — deliberately
     * descoped from Phase 1's active work per ARCHITECT_IMPROVEMENT_PLAN.md,
     * not just unwired by accident. Everything else is Maturity::Stable.
     */
    private function registerControlLibrary(): void
    {
        $registry = $this->app->make(ControlRegistry::class);

        // Wave A — native & common, already shipped.
        $registry
            ->register('text', TextField::class, 'Text & Structured Text', 'string', 'Single-line text input.', Maturity::Stable)
            ->register('textarea', TextareaField::class, 'Text & Structured Text', 'string', 'Multi-line text input.', Maturity::Stable)
            ->register('code-editor', CodeEditor::class, 'Text & Structured Text', 'string', 'Syntax-highlighted code input.', Maturity::Stable)
            ->register('markdown-editor', MarkdownEditor::class, 'Text & Structured Text', 'string', 'Markdown input with preview.', Maturity::Stable)
            ->register('rich-editor', RichEditor::class, 'Text & Structured Text', 'string', 'WYSIWYG editor (TipTap-based).', Maturity::Stable)
            ->register('select', SelectField::class, 'Choice & Selection', 'string', 'Static or dynamic options select.', Maturity::Stable)
            ->register('checkbox', CheckboxField::class, 'Choice & Selection', 'boolean', 'Single checkbox toggle.', Maturity::Stable)
            ->register('checkbox-list', CheckboxList::class, 'Choice & Selection', 'array', 'Multiple checkboxes.', Maturity::Stable)
            ->register('radio', Radio::class, 'Choice & Selection', 'string', 'Radio button group.', Maturity::Stable)
            ->register('toggle-buttons', ToggleButtons::class, 'Choice & Selection', 'string', 'Segmented button-group selection.', Maturity::Stable)
            ->register('toggle', Toggle::class, 'Choice & Selection', 'boolean', 'On/off toggle switch.', Maturity::Stable)
            ->register('date', DateField::class, 'Date & Time', 'date', 'Date picker.', Maturity::Stable)
            ->register('datetime', DateTimeField::class, 'Date & Time', 'datetime', 'Date + time picker.', Maturity::Stable)
            ->register('datetime-picker', DateTimePicker::class, 'Date & Time', 'datetime', 'Flatpickr-powered calendar picker.', Maturity::Stable)
            ->register('integer', IntegerField::class, 'Numeric', 'integer', 'Integer number input.', Maturity::Stable)
            ->register('decimal', DecimalField::class, 'Numeric', 'decimal', 'Decimal/float input.', Maturity::Stable)
            ->register('slider', Slider::class, 'Numeric', 'integer', 'Range slider.', Maturity::Stable)
            ->register('lookup', LookupField::class, 'Relationships & Lookup', 'integer', 'AJAX-driven lookup with cascading support.', Maturity::Stable)
            ->register('repeater', Repeater::class, 'Structural', 'array', 'Repeatable group of fields.', Maturity::Stable)
            ->register('builder', Builder::class, 'Structural', 'array', 'Block-based page-builder-style field.', Maturity::Stable)
            ->register('file-upload', FileUpload::class, 'File & Media', 'string', 'Drag-drop file upload.', Maturity::Stable)
            ->register('key-value', KeyValue::class, 'Relationships & Lookup', 'array', 'Editable key-value pairs.', Maturity::Stable)
            ->register('tags-input', TagsInput::class, 'Choice & Selection', 'array', 'Free-text tag input.', Maturity::Stable)
            ->register('hidden', Hidden::class, 'Structural', 'mixed', 'Hidden form field.', Maturity::Stable)
            ->register('display', DisplayField::class, 'Structural', 'mixed', 'Read-only display value.', Maturity::Stable)
            ->register('custom', Custom::class, 'Structural', 'mixed', 'Escape hatch: renders an arbitrary Blade view.', Maturity::Stable)
            ->register('color-picker', ColorPicker::class, 'Visual & Spatial', 'string', 'Colour picker.', Maturity::Stable);

        // Wave B/C — new controls implemented in this plan.
        $registry
            ->register('currency', CurrencyField::class, 'Numeric', 'decimal', 'Currency amount input.', Maturity::Stable)
            ->register('percentage', PercentageField::class, 'Numeric', 'decimal', 'Percentage input.', Maturity::Stable)
            ->register('date-range', DateRangeField::class, 'Date & Time', 'array', 'Start/end date range input.', Maturity::Beta) // plain text inputs; no flatpickr enhancement unlike date/datetime
            ->register('phone', PhoneField::class, 'Formatted & Validated Text', 'string', 'International telephone input.', Maturity::Stable)
            ->register('otp', OtpField::class, 'Formatted & Validated Text', 'string', 'Fixed-length numeric OTP/PIN input.', Maturity::Stable)
            ->register('rating', RatingField::class, 'Visual & Spatial', 'integer', 'Star/numeric rating input.', Maturity::Stable)
            ->register('autocomplete', AutocompleteField::class, 'Choice & Selection', 'string', 'Text input with typeahead suggestions.', Maturity::Experimental)
            ->register('combobox', ComboboxField::class, 'Choice & Selection', 'string', 'Text input + dropdown, allows custom values.', Maturity::Stable)
            ->register('multi-select', MultiSelectField::class, 'Choice & Selection', 'array', 'Multi-value select with chip display.', Maturity::Stable)
            ->register('tree-select', TreeSelectField::class, 'Choice & Selection', 'string', 'Dropdown with hierarchical options.', Maturity::Stable)
            ->register('cascading-select', CascadingSelectField::class, 'Choice & Selection', 'string', 'Select whose options depend on another field.', Maturity::Stable)
            ->register('dual-listbox', DualListboxField::class, 'Choice & Selection', 'array', 'Transfer-list selection.', Maturity::Stable)
            ->register('hierarchical-checkbox-tree', HierarchicalCheckboxTreeField::class, 'Choice & Selection', 'array', 'Tree of checkboxes with partial selection.', Maturity::Stable)
            ->register('entity-picker', EntityPickerField::class, 'Relationships & Lookup', 'integer', 'Richer templated entity search picker.', Maturity::Stable)
            ->register('segmented-control', SegmentedControlField::class, 'Choice & Selection', 'string', 'Small exclusive choice set as a segmented control.', Maturity::Stable)
            ->register('button-group', ButtonGroupField::class, 'Choice & Selection', 'string', 'Single-select button group.', Maturity::Stable)
            ->register('yes-no-unknown', YesNoUnknownField::class, 'Choice & Selection', 'string', 'Tri-state yes/no/unknown control.', Maturity::Stable)
            ->register('datetime-range', DateTimeRangeField::class, 'Date & Time', 'array', 'Start/end date+time range input.', Maturity::Beta) // plain text inputs; no flatpickr enhancement unlike date/datetime
            ->register('duration', DurationField::class, 'Date & Time', 'integer', 'Duration input in minutes.', Maturity::Stable)
            ->register('timezone', TimezoneField::class, 'Date & Time', 'string', 'Searchable IANA timezone picker.', Maturity::Beta) // renders a plain <select>, not actually searchable yet
            ->register('recurrence-builder', RecurrenceBuilderField::class, 'Date & Time', 'array', 'Structured recurrence rule builder.', Maturity::Stable)
            ->register('natural-language-date', NaturalLanguageDateField::class, 'Date & Time', 'string', 'Free-text date input for natural-language parsing.', Maturity::Stable)
            ->register('measurement', MeasurementField::class, 'Numeric', 'array', 'Numeric input with a unit suffix.', Maturity::Stable)
            ->register('numeric-stepper', NumericStepperField::class, 'Numeric', 'integer', 'Numeric input with +/- stepper controls.', Maturity::Stable)
            ->register('masked-input', MaskedInputField::class, 'Formatted & Validated Text', 'string', 'Text input enforcing a fixed format mask.', Maturity::Stable)
            ->register('password-strength', PasswordStrengthField::class, 'Formatted & Validated Text', 'string', 'Password input with live strength feedback.', Maturity::Stable)
            ->register('address-autocomplete', AddressAutocompleteField::class, 'Formatted & Validated Text', 'array', 'Address search resolving to a structured address.', Maturity::Planned) // address-lookup provider not yet chosen — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('postal-code-lookup', PostalCodeLookupField::class, 'Formatted & Validated Text', 'array', 'Postcode lookup with address selection.', Maturity::Planned) // address-lookup provider not yet chosen — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('card-input', CardInputField::class, 'Formatted & Validated Text', 'string', 'Card entry via a payment provider\'s hosted fields.', Maturity::Stable)
            ->register('search-with-filters', SearchWithFiltersField::class, 'Formatted & Validated Text', 'array', 'Search input with structured filter chips.', Maturity::Experimental)
            ->register('query-language-text', QueryLanguageTextField::class, 'Formatted & Validated Text', 'string', 'Structured query-language text input.', Maturity::Stable)
            ->register('avatar', AvatarField::class, 'File & Media', 'string', 'Avatar upload with circular preview and initials fallback.', Maturity::Experimental)
            ->register('image-cropper', ImageCropperField::class, 'File & Media', 'string', 'Image upload with in-browser crop/rotate/zoom.', Maturity::Stable)
            ->register('map-location', MapLocationField::class, 'Visual & Spatial', 'array', 'Map-based location picker.', Maturity::Experimental)
            ->register('geographic-boundary', GeographicBoundaryField::class, 'Visual & Spatial', 'array', 'Draws a boundary on a map.', Maturity::Experimental)
            ->register('gradient-editor', GradientEditorField::class, 'Visual & Spatial', 'array', 'Multi-stop colour gradient editor.', Maturity::Stable)
            ->register('signature-pad', SignaturePadField::class, 'Visual & Spatial', 'string', 'Canvas-based signature capture.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('annotation', AnnotationField::class, 'Visual & Spatial', 'array', 'Draws bounding boxes/polygons/labels over an image.', Maturity::Experimental)
            ->register('drawing-sketch', DrawingSketchField::class, 'Visual & Spatial', 'string', 'Free-hand drawing/sketch canvas.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('canvas-manipulation', CanvasManipulationField::class, 'Visual & Spatial', 'array', 'Drag/resize/rotate objects on a canvas.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('dial-knob', DialKnobField::class, 'Visual & Spatial', 'decimal', 'Rotational dial/knob control.', Maturity::Stable)
            ->register('matrix-input', MatrixInputField::class, 'Visual & Spatial', 'array', 'Survey-style response grid.', Maturity::Stable)
            ->register('sortable-list', SortableListField::class, 'Structural', 'array', 'Drag-to-reorder list.', Maturity::Stable)
            ->register('kanban-board', KanbanBoardField::class, 'Structural', 'array', 'Drag-between-columns board input.', Maturity::Stable)
            ->register('ranking', RankingField::class, 'Structural', 'array', 'Drag-to-rank input.', Maturity::Stable)
            ->register('relationship-picker', RelationshipPickerField::class, 'Relationships & Lookup', 'array', 'Links this record to another record/event/entity.', Maturity::Stable)
            ->register('timeline-editor', TimelineEditorField::class, 'Visual & Spatial', 'array', 'Timeline editor for labelled segments.', Maturity::Experimental)
            ->register('image-comparison-slider', ImageComparisonSliderField::class, 'Visual & Spatial', 'decimal', 'Draggable divider comparing two images.', Maturity::Stable)
            ->register('camera-capture', CameraCaptureField::class, 'File & Media', 'string', 'Photo capture via the device camera.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('audio-recorder', AudioRecorderField::class, 'File & Media', 'string', 'Audio recording via the device microphone.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('video-recorder', VideoRecorderField::class, 'File & Media', 'string', 'Video recording via camera + microphone.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('document-scanner', DocumentScannerField::class, 'File & Media', 'string', 'Camera-based document edge detection and cleanup.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('barcode-qr-scanner', BarcodeQrScannerField::class, 'File & Media', 'string', 'Barcode/QR-code scanner.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('audio-waveform-selection', AudioWaveformSelectionField::class, 'Visual & Spatial', 'array', 'Selects a segment of an audio waveform.', Maturity::Planned) // Wave 3 hardware/media field, deferred — see ARCHITECT_IMPROVEMENT_PLAN.md
            ->register('formula-expression-editor', FormulaExpressionEditorField::class, 'Text & Structured Text', 'string', 'Formula/expression editor with field references.', Maturity::Stable)
            ->register('math-equation-editor', MathEquationEditorField::class, 'Text & Structured Text', 'string', 'Visual math equation editor.', Maturity::Stable)
            ->register('mention-editor', MentionEditorField::class, 'Text & Structured Text', 'string', 'Rich text editor supporting @mentions.', Maturity::Stable)
            ->register('template-editor', TemplateEditorField::class, 'Text & Structured Text', 'string', 'Text editor with {{ variable }} placeholders.', Maturity::Stable)
            ->register('block-editor', BlockEditor::class, 'Structural', 'array', 'Full page/layout builder (extends Builder).', Maturity::Experimental)
            ->register('diff-merge', DiffMergeField::class, 'Structural', 'array', 'Presents two versions for per-field merge selection.', Maturity::Stable)
            ->register('query-builder', QueryBuilderField::class, 'Structural', 'array', 'Visual nested AND/OR query builder.', Maturity::Stable)
            ->register('rules-workflow-builder', RulesWorkflowBuilderField::class, 'Structural', 'array', 'Visual workflow builder (nodes + edges).', Maturity::Stable)
            ->register('schema-driven-object-editor', SchemaDrivenObjectEditorField::class, 'Structural', 'array', 'Renders a form from a JSON Schema.', Maturity::Stable)
            ->register('node-graph-editor', NodeGraphEditorField::class, 'Structural', 'array', 'Node graph editor connecting ports between blocks.', Maturity::Stable)
            ->register('permission-matrix', PermissionMatrixField::class, 'Structural', 'array', 'Grid of resources x actions.', Maturity::Stable)
            ->register('role-builder', RoleBuilderField::class, 'Structural', 'array', 'Combines permissions and inheritance into a role.', Maturity::Stable)
            ->register('dependency-builder', DependencyBuilderField::class, 'Structural', 'array', 'Builds a conditional-visibility rule.', Maturity::Stable)
            ->register('data-mapping', DataMappingField::class, 'Relationships & Lookup', 'array', 'Maps incoming fields to application fields.', Maturity::Stable)
            ->register('api-request-builder', ApiRequestBuilderField::class, 'Structural', 'array', 'Structured HTTP request builder.', Maturity::Stable)
            ->register('cron-schedule-builder', CronScheduleBuilderField::class, 'Date & Time', 'string', 'Builds a cron expression from a friendly schedule.', Maturity::Stable)
            ->register('regex-builder-tester', RegexBuilderTesterField::class, 'Formatted & Validated Text', 'array', 'Regex pattern builder/tester.', Maturity::Stable)
            ->register('keyboard-shortcut-recorder', KeyboardShortcutRecorderField::class, 'Structural', 'string', 'Records a keyboard shortcut combination.', Maturity::Stable);
    }

    /**
     * Auto-registers the daily architect:storage:sweep job on Laravel's
     * scheduler — host apps only need their normal `schedule:run` cron, no
     * manual Console\Kernel wiring. Runs whenever either Storage Contracts or
     * File Retention is enabled, since the command itself independently gates
     * each of its 5 phases on the two `enabled` flags.
     */
    private function registerStorageContractsScheduler(): void
    {
        if (! (bool) config('architect.storage_contracts.enabled', false)
            && ! (bool) config('architect.file_retention.enabled', false)) {
            return;
        }

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(ArchitectStorageSweepCommand::class)
                ->daily()
                ->withoutOverlapping();
        });
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
